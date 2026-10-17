<?php

namespace App\Services;

use App\Enums\UploadStatus;
use App\Models\OperationState;
use App\Models\PlaybackSession;
use App\Models\VideoAsset;
use App\Models\VideoUpload;
use Illuminate\Support\Facades\Storage;

class CleanupVideoUploads
{
    public const TASK = 'cleanup-video-uploads';

    public function __construct(private readonly ActivityLogger $logger) {}

    public function run(int $batchSize, int $maxSeconds): array
    {
        $state = OperationState::query()->firstOrCreate(['task' => self::TASK], ['cursor' => null]);
        if ($state->next_attempt_at?->isFuture()) return ['status' => 'partial', 'processed' => 0, 'remaining' => true];
        $state->update(['last_started_at' => now()]);
        $cutoff = now()->subHours(config('operations.upload_stale_hours'));
        $started = microtime(true);
        $processed = 0;
        $scanned = 0;
        $afterId = $state->cursor['after_id'] ?? null;

        try {
            while ($scanned < $batchSize && microtime(true) - $started < $maxSeconds - 0.5) {
                $query = VideoUpload::query()->whereNull('cleaned_at')->where('last_activity_at', '<=', $cutoff)
                    ->where('status', '!=', UploadStatus::Ready->value)->orderBy('id');
                if ($afterId) $query->where('id', '>', $afterId);
                $upload = $query->first();
                if (! $upload) {
                    $afterId = null;
                    $state->update(['cursor' => null]);
                    break;
                }
                if ($upload->status === UploadStatus::Validating && $upload->last_activity_at->greaterThan(now()->subMinutes(2))) {
                    $afterId = $upload->id;
                    $scanned++;
                    $state->update(['cursor' => ['after_id' => $afterId]]);
                    continue;
                }
                if ($upload->status === UploadStatus::Replaced && PlaybackSession::query()
                    ->where('hls_path', $upload->storage_path)->whereNull('revoked_at')->where('expires_at', '>', now())->exists()) {
                    $afterId = $upload->id;
                    $scanned++;
                    $state->update(['cursor' => ['after_id' => $afterId]]);
                    continue;
                }
                if (VideoAsset::query()->where('hls_path', $upload->storage_path)->exists()
                    || ! preg_match('~^[0-9a-f-]{36}/[0-9a-f-]{36}/hls/source$~', $upload->storage_path)) {
                    $afterId = $upload->id;
                    $scanned++;
                    $state->update(['cursor' => ['after_id' => $afterId]]);
                    continue;
                }
                $disk = Storage::disk(config('video.disk'));
                $folder = dirname(dirname($upload->storage_path));
                if ($disk->directoryExists($folder) && ! $disk->deleteDirectory($folder)) {
                    throw new \RuntimeException('Folder unggahan gagal dibersihkan.');
                }
                $upload->update(['status' => $upload->status === UploadStatus::Replaced ? UploadStatus::Replaced : UploadStatus::Expired,
                    'cleaned_at' => now(), 'encryption_key' => '', 'manifest' => null]);
                $afterId = $upload->id;
                $scanned++;
                $state->update(['cursor' => ['after_id' => $afterId]]);
                $processed++;
            }

            $remaining = VideoUpload::query()->whereNull('cleaned_at')->where('last_activity_at', '<=', $cutoff)
                ->where('status', '!=', UploadStatus::Ready->value)->exists();
            $state->update(['last_succeeded_at' => $remaining ? $state->last_succeeded_at : now(), 'last_error' => null, 'next_attempt_at' => null]);
            $this->logger->log('operation', null, 'operation_states', $state->id, null,
                ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining], 'Batch pembersihan unggahan video');
            return ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining];
        } catch (\Throwable $exception) {
            $state->update(['last_error' => $exception::class, 'next_attempt_at' => now()->addMinutes(5)]);
            $this->logger->log('operation_failed', null, 'operation_states', $state->id, null,
                ['error' => $exception::class, 'processed' => $processed], 'Batch pembersihan unggahan video gagal');
            throw $exception;
        }
    }
}
