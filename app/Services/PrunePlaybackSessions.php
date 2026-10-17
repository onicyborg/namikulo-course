<?php

namespace App\Services;

use App\Models\OperationState;
use App\Models\PlaybackSession;

class PrunePlaybackSessions
{
    public const TASK = 'prune-playback-sessions';

    public function run(int $batchSize, int $maxSeconds): array
    {
        $state = OperationState::query()->firstOrCreate(['task' => self::TASK], ['cursor' => null]);
        $state->update(['last_started_at' => now()]);
        $cutoff = now()->subDays(config('operations.playback_retention_days'));
        $started = microtime(true);
        $processed = 0;
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
        $state->update(['last_succeeded_at' => $remaining ? $state->last_succeeded_at : now(), 'last_error' => null]);
        return ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining];
    }
}
