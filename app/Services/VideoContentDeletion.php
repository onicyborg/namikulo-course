<?php

namespace App\Services;

use App\Enums\UploadStatus;
use App\Enums\VideoStatus;
use App\Models\Lesson;
use App\Models\PlaybackSession;
use App\Models\User;
use App\Models\VideoUpload;
use Illuminate\Support\Facades\Storage;

class VideoContentDeletion
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function archiveLesson(Lesson $lesson, User $actor): array
    {
        $old = $lesson->getAttributes();
        $uploads = $this->detachVideo($lesson);
        $lesson->delete();
        $this->logger->log('deleted', $actor, 'lessons', $lesson->id, $old);
        return $uploads;
    }

    public function removeVideo(Lesson $lesson, User $actor): array
    {
        $old = $lesson->videoAsset?->getAttributes();
        $uploads = $this->detachVideo($lesson);
        $this->logger->log('deleted', $actor, 'video_assets', $lesson->videoAsset?->id, $old,
            ['lesson_id' => $lesson->id, 'status' => 'pending']);
        return $uploads;
    }

    private function detachVideo(Lesson $lesson): array
    {
        PlaybackSession::query()->where('lesson_id', $lesson->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        $asset = $lesson->videoAsset;
        if ($asset) {
            $asset->update(['status' => VideoStatus::Pending, 'hls_path' => null, 'encryption_key' => null,
                'renditions' => null, 'duration_seconds' => null, 'size_bytes' => null, 'processed_at' => null]);
        }
        $uploads = VideoUpload::query()->where('lesson_id', $lesson->id)->whereNull('cleaned_at')->get();
        foreach ($uploads as $upload) {
            $upload->update(['status' => $upload->status === UploadStatus::Ready ? UploadStatus::Replaced : UploadStatus::Cancelled,
                'last_activity_at' => now()->subHours(config('operations.upload_stale_hours') + 1)]);
        }
        $lesson->update(['duration_seconds' => 0]);
        return $uploads->pluck('id')->all();
    }

    public function removeFiles(array $uploadIds): bool
    {
        $allRemoved = true;
        foreach (array_unique($uploadIds) as $id) {
            $upload = VideoUpload::query()->find($id);
            if (! $upload || ! preg_match('~^[0-9a-f-]{36}/[0-9a-f-]{36}/hls/source$~', $upload->storage_path)) continue;
            try {
                $removed = Storage::disk(config('video.disk'))->deleteDirectory(dirname(dirname($upload->storage_path)));
                if ($removed) $upload->update(['cleaned_at' => now(), 'encryption_key' => '', 'manifest' => null]);
                else $allRemoved = false;
            } catch (\Throwable) { $allRemoved = false; }
        }
        return $allRemoved;
    }
}
