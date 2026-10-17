<?php

namespace App\Services;

use App\Enums\NotificationDigestStatus;
use App\Mail\NotificationDigestMail;
use App\Models\NotificationDigest;
use App\Models\OperationState;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendNotificationDigests
{
    public const TASK = 'send-notification-digests';

    public function __construct(private readonly ActivityLogger $logger) {}

    public function run(int $batchSize, int $maxSeconds): array
    {
        $jakarta = now()->setTimezone('Asia/Jakarta');
        $date = $jakarta->toDateString();
        $cutoff = Carbon::parse($date.' 07:00:00', 'Asia/Jakarta')->utc();
        if (now()->lt($cutoff)) return ['status' => 'not_due', 'processed' => 0, 'remaining' => false];

        $state = OperationState::query()->firstOrCreate(['task' => self::TASK], ['cursor' => null]);
        if ($state->next_attempt_at?->isFuture()) return ['status' => 'partial', 'processed' => 0, 'remaining' => true];
        $state->update(['last_started_at' => now()]);
        $started = microtime(true);
        $processed = 0;
        $limit = max(1, min($batchSize, (int) config('operations.notification_digest_batch_size', 3)));
        $mailTimeout = (int) config('mail.mailers.smtp.timeout', 5);

        try {
            while ($processed < $limit && microtime(true) - $started + $mailTimeout + 2 < $maxSeconds) {
                $user = $this->eligible($date, $cutoff)->first();
                if (! $user) break;

                $claim = DB::transaction(function () use ($user, $date, $cutoff): ?NotificationDigest {
                    $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                    if (! $locked->is_active || NotificationDigest::query()->where('user_id', $locked->id)->where('digest_date', $date)->exists()) return null;
                    $count = $locked->userNotifications()->whereNull('read_at')->where('created_at', '<', $cutoff)->count();
                    if (! $count) return null;
                    return NotificationDigest::query()->create([
                        'user_id' => $locked->id, 'digest_date' => $date, 'cutoff_at' => $cutoff,
                        'status' => NotificationDigestStatus::Attempted, 'item_count' => $count, 'attempted_at' => now(),
                    ]);
                });
                if (! $claim) continue;

                $items = $user->userNotifications()->whereNull('read_at')->where('created_at', '<', $cutoff)
                    ->latest('created_at')->limit(20)->get(['id', 'title', 'summary', 'created_at']);
                $total = $user->userNotifications()->whereNull('read_at')->where('created_at', '<', $cutoff)->count();
                if ($total === 0 || ! $user->fresh()?->is_active) {
                    $claim->update(['status' => NotificationDigestStatus::Skipped, 'item_count' => 0]);
                } else {
                    $claim->update(['item_count' => $total]);
                    try {
                        Mail::to($user->email)->send(new NotificationDigestMail($user, $items, $total, $date));
                        $claim->update(['status' => NotificationDigestStatus::Sent, 'sent_at' => now()]);
                    } catch (\Throwable $exception) {
                        $claim->update(['status' => NotificationDigestStatus::Uncertain, 'error_class' => mb_substr($exception::class, 0, 120)]);
                    }
                }
                $processed++;
            }
            $remaining = $this->eligible($date, $cutoff)->exists();
            if (! $remaining) $state->update(['last_succeeded_at' => now(), 'last_error' => null, 'next_attempt_at' => null]);
            $this->logger->log('operation', null, 'operation_states', $state->id, null,
                ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed], 'Rekap notifikasi');
            return ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining];
        } catch (\Throwable $exception) {
            $state->update(['last_error' => $exception::class, 'next_attempt_at' => now()->addMinutes(5)]);
            $this->logger->log('operation_failed', null, 'operation_states', $state->id, null,
                ['error' => $exception::class, 'processed' => $processed], 'Rekap notifikasi gagal');
            throw $exception;
        }
    }

    private function eligible(string $date, Carbon $cutoff): \Illuminate\Database\Eloquent\Builder
    {
        return User::query()->where('is_active', true)
            ->whereHas('userNotifications', fn ($query) => $query->whereNull('read_at')->where('created_at', '<', $cutoff))
            ->whereDoesntHave('notificationDigests', fn ($query) => $query->where('digest_date', $date))
            ->orderBy('id');
    }
}
