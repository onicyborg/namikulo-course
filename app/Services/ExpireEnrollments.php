<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\OperationState;
use App\Models\PlaybackSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExpireEnrollments
{
    public const TASK = 'expire-enrollments';

    public function __construct(private readonly ActivityLogger $logger, private readonly EnrollmentService $enrollments) {}

    public function run(int $batchSize, int $maxSeconds): array
    {
        $started = microtime(true);
        $state = OperationState::query()->firstOrCreate(['task' => self::TASK], ['cursor' => null]);
        if ($state->next_attempt_at?->isFuture()) {
            return ['status' => 'partial', 'processed' => 0, 'remaining' => true];
        }
        $state->update(['last_started_at' => now()]);
        $processed = 0;
        $scanned = 0;
        $afterId = $state->cursor['after_id'] ?? null;

        try {
            while ($scanned < $batchSize && microtime(true) - $started < $maxSeconds - 0.5) {
                $query = Enrollment::query()->where('status', EnrollmentStatus::Active->value)
                    ->where('expires_at', '<=', now())->orderBy('id');
                if ($afterId) $query->where('id', '>', $afterId);
                $next = $query->first();
                if (! $next) {
                    if ($afterId !== null) {
                        $afterId = null;
                        $state->update(['cursor' => null]);
                    }
                    break;
                }
                $changed = DB::transaction(function () use ($next): bool {
                    $locked = Enrollment::query()->whereKey($next->id)->lockForUpdate()->firstOrFail();
                    if ($locked->status !== EnrollmentStatus::Active || $locked->expires_at->isFuture()) return false;
                    $old = $locked->getAttributes();
                    $locked->update(['status' => EnrollmentStatus::Expired]);
                    if (! $this->enrollments->activeFor($locked->user, $locked->course)) {
                        PlaybackSession::query()->where('user_id', $locked->user_id)->whereNull('revoked_at')
                            ->whereHas('lesson', fn ($lesson) => $lesson->where('course_id', $locked->course_id))
                            ->update(['revoked_at' => now()]);
                    }
                    $this->logger->log('updated', null, 'enrollments', $locked->id, $old, $locked->getAttributes(), 'Akses kedaluwarsa');
                    return true;
                });
                $afterId = $next->id;
                $state->update(['cursor' => ['after_id' => $afterId], 'last_error' => null, 'next_attempt_at' => null]);
                $scanned++;
                if ($changed) $processed++;
            }
            $remaining = Enrollment::query()->where('status', EnrollmentStatus::Active->value)->where('expires_at', '<=', now())->exists();
            if (! $remaining) $state->update(['cursor' => null, 'last_succeeded_at' => now(), 'last_error' => null, 'next_attempt_at' => null]);
            $this->logger->log('operation', null, 'operation_states', $state->id, null, ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining], 'Batch kedaluwarsa akses');
            return ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining];
        } catch (\Throwable $exception) {
            $state->update(['last_error' => Str::limit($exception::class, 255, ''), 'next_attempt_at' => now()->addMinutes(5)]);
            $this->logger->log('operation_failed', null, 'operation_states', $state->id, null, ['error' => $exception::class, 'processed' => $processed], 'Batch kedaluwarsa akses gagal');
            throw $exception;
        }
    }
}
