<?php

namespace App\Services;

use App\Models\OperationState;
use App\Models\PlaybackSession;

class PrunePlaybackSessions
{
    public const TASK = 'prune-playback-sessions';

    public function __construct(private readonly ActivityLogger $logger) {}

    public function run(int $batchSize, int $maxSeconds): array
    {
        $state = OperationState::query()->firstOrCreate(['task' => self::TASK], ['cursor' => null]);
        if ($state->next_attempt_at?->isFuture()) return ['status' => 'partial', 'processed' => 0, 'remaining' => true];
        $state->update(['last_started_at' => now()]);
        $cutoff = now()->subDays(config('operations.playback_retention_days'));
        $started = microtime(true);
        $processed = 0;
        try {
            while ($processed < $batchSize && microtime(true) - $started < $maxSeconds - 0.5) {
                $session = PlaybackSession::query()->where('created_at', '<=', $cutoff)
                    ->where(fn ($query) => $query->whereNotNull('revoked_at')->orWhere('hard_expires_at', '<=', now()))
                    ->orderBy('id')->first();
                if (! $session) break;
                $session->delete();
                $processed++;
            }
            $remaining = PlaybackSession::query()->where('created_at', '<=', $cutoff)
                ->where(fn ($query) => $query->whereNotNull('revoked_at')->orWhere('hard_expires_at', '<=', now()))->exists();
            $state->update(['last_succeeded_at' => $remaining ? $state->last_succeeded_at : now(), 'last_error' => null, 'next_attempt_at' => null]);
            $this->logger->log('operation', null, 'operation_states', $state->id, null,
                ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining], 'Batch pembersihan sesi video');
            return ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining];
        } catch (\Throwable $exception) {
            $state->update(['last_error' => $exception::class, 'next_attempt_at' => now()->addMinutes(5)]);
            $this->logger->log('operation_failed', null, 'operation_states', $state->id, null,
                ['error' => $exception::class, 'processed' => $processed], 'Batch pembersihan sesi video gagal');
            throw $exception;
        }
    }
}
