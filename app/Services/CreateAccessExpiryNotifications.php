<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\OperationState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CreateAccessExpiryNotifications
{
    public const TASK = 'create-access-expiry-notifications';

    public function __construct(private readonly NotificationService $notifications, private readonly ActivityLogger $logger) {}

    public function run(int $batchSize, int $maxSeconds): array
    {
        $state = OperationState::query()->firstOrCreate(['task' => self::TASK], ['cursor' => null]);
        if ($state->next_attempt_at?->isFuture()) return ['status' => 'partial', 'processed' => 0, 'remaining' => true];
        $state->update(['last_started_at' => now()]);
        $started = microtime(true);
        $processed = 0;
        $afterId = $state->cursor['after_id'] ?? null;

        try {
            while ($processed < $batchSize && microtime(true) - $started < $maxSeconds - 1) {
                $query = Enrollment::query()->with('user:id,timezone,is_active')->where('status', EnrollmentStatus::Active->value)
                    ->where('expires_at', '>', now())->where('expires_at', '<=', now()->addDays(9))->orderBy('id');
                if ($afterId) $query->where('id', '>', $afterId);
                $candidate = $query->first();
                if (! $candidate) break;

                DB::transaction(function () use ($candidate): void {
                    $enrollment = Enrollment::query()->with('user:id,timezone,is_active')->whereKey($candidate->id)->lockForUpdate()->first();
                    if (! $enrollment || $enrollment->status !== EnrollmentStatus::Active || ! $enrollment->expires_at->isFuture()
                        || ! $enrollment->user?->is_active) return;
                    $zone = $enrollment->user->timezone ?: 'UTC';
                    try {
                        $today = now()->setTimezone($zone)->toDateString();
                        $endDate = $enrollment->expires_at->copy()->setTimezone($zone)->toDateString();
                    } catch (\Throwable) {
                        $today = now()->utc()->toDateString();
                        $endDate = $enrollment->expires_at->copy()->utc()->toDateString();
                    }
                    $remainingDays = Carbon::parse($today, 'UTC')->diffInDays(Carbon::parse($endDate, 'UTC'), false);
                    if ($remainingDays >= 0 && $remainingDays <= 7) $this->notifications->accessExpiring($enrollment);
                });
                $afterId = $candidate->id;
                $processed++;
                $state->update(['cursor' => ['after_id' => $afterId], 'last_error' => null, 'next_attempt_at' => null]);
            }
            $remaining = Enrollment::query()->where('status', EnrollmentStatus::Active->value)
                ->where('expires_at', '>', now())->where('expires_at', '<=', now()->addDays(9))
                ->when($afterId, fn ($query) => $query->where('id', '>', $afterId))->exists();
            if (! $remaining) $state->update(['cursor' => null, 'last_succeeded_at' => now(), 'last_error' => null, 'next_attempt_at' => null]);
            $this->logger->log('operation', null, 'operation_states', $state->id, null,
                ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed], 'Pengingat akses');
            return ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining];
        } catch (\Throwable $exception) {
            $state->update(['last_error' => $exception::class, 'next_attempt_at' => now()->addMinutes(5)]);
            $this->logger->log('operation_failed', null, 'operation_states', $state->id, null,
                ['error' => $exception::class, 'processed' => $processed], 'Pengingat akses gagal');
            throw $exception;
        }
    }
}
