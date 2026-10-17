<?php

namespace App\Services;

use App\Enums\VideoStatus;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LessonProgressService
{
    public function __construct(
        private readonly VideoDelivery $delivery,
        private readonly ActivityLogger $logger,
    ) {}

    public function record(User $user, Lesson $lesson, string $token, int $position, int $watchedSeconds, string $event): LessonProgress
    {
        abort_unless($user->isMember(), 403);

        return DB::transaction(function () use ($user, $lesson, $token, $position, $watchedSeconds, $event): LessonProgress {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $session = $this->delivery->assertSession($user, $token);
            abort_unless(! $session->is_preview && $session->lesson_id === $lesson->id, 403, 'Sesi video tidak sesuai.');

            $lesson->loadMissing(['section', 'videoAsset']);
            abort_unless(! $lesson->trashed() && $lesson->section && $lesson->section->course_id === $lesson->course_id
                && $lesson->videoAsset?->status === VideoStatus::Ready
                && $session->video_asset_id === $lesson->videoAsset->id, 404, 'Materi tidak tersedia.');

            $duration = (int) $lesson->videoAsset->duration_seconds;
            if ($duration < 1 || $position > $duration + 2 || ($event === 'ended' && $position < max(0, $duration - 2))) {
                throw ValidationException::withMessages(['position_seconds' => 'Posisi video tidak sesuai dengan materi.']);
            }
            $position = min($position, $duration);
            $sessionSeconds = max(0, (int) $session->created_at->diffInSeconds(now()));

            $progress = LessonProgress::query()->where('user_id', $user->id)->where('lesson_id', $lesson->id)
                ->lockForUpdate()->first();
            if (! $progress) {
                $progress = LessonProgress::query()->create([
                    'user_id' => $user->id, 'course_id' => $lesson->course_id, 'lesson_id' => $lesson->id,
                    'last_position_seconds' => $position,
                    'watched_seconds' => min($duration, $watchedSeconds, $sessionSeconds * 2 + 2),
                    'completed_at' => $event === 'ended' ? now() : null,
                ]);
                if ($progress->completed_at) $this->logCompletion($user, $progress);
                return $progress;
            }

            abort_unless($progress->course_id === $lesson->course_id, 409, 'Data progres tidak sesuai.');
            $elapsed = min(60, max(0, (int) $progress->updated_at->diffInSeconds(now())));
            $watched = min($watchedSeconds, $elapsed * 2 + 2);
            $completed = $event === 'ended' && ! $progress->completed_at;
            $progress->update([
                'last_position_seconds' => $position,
                'watched_seconds' => min($duration, $progress->watched_seconds + $watched),
                'completed_at' => $completed ? now() : $progress->completed_at,
            ]);
            if ($completed) $this->logCompletion($user, $progress);

            return $progress;
        });
    }

    private function logCompletion(User $user, LessonProgress $progress): void
    {
        $this->logger->log('completed', $user, 'lesson_progress', $progress->id, null,
            ['course_id' => $progress->course_id, 'lesson_id' => $progress->lesson_id]);
    }
}
