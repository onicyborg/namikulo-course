<?php

namespace App\Services;

use App\Enums\VideoStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class LessonSequence
{
    public function readyLessons(Course $course): Collection
    {
        $course->loadMissing('sections.lessons.videoAsset');

        return $course->sections->flatMap->lessons
            ->filter(fn (Lesson $lesson) => $lesson->videoAsset?->status === VideoStatus::Ready)
            ->values();
    }

    public function unlockedIds(Collection $ready, Collection $progress): array
    {
        $unlocked = [];
        $priorCompleted = true;

        foreach ($ready as $lesson) {
            $completed = (bool) $progress->get($lesson->id)?->completed_at;
            if ($priorCompleted || $completed) $unlocked[$lesson->id] = true;
            $priorCompleted = $priorCompleted && $completed;
        }

        return $unlocked;
    }

    public function assertAccessible(User $user, Lesson $lesson): void
    {
        if ($user->isAdmin()) return;

        $lesson->loadMissing('course');
        $ready = $this->readyLessons($lesson->course);
        abort_unless($ready->contains('id', $lesson->id), 404, 'Video belum siap.');
        $progress = LessonProgress::query()->where('user_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->whereIn('lesson_id', $ready->pluck('id'))
            ->get()->keyBy('lesson_id');

        abort_unless(isset($this->unlockedIds($ready, $progress)[$lesson->id]), 403,
            'Selesaikan materi sebelumnya untuk membuka materi ini.');
    }

    public function isAccessible(User $user, Lesson $lesson): bool
    {
        if ($user->isAdmin()) return true;
        $lesson->loadMissing('course');
        if (! $lesson->course) return false;
        $ready = $this->readyLessons($lesson->course);
        if (! $ready->contains('id', $lesson->id)) return false;
        $progress = LessonProgress::query()->where('user_id', $user->id)
            ->where('course_id', $lesson->course_id)->whereIn('lesson_id', $ready->pluck('id'))
            ->get()->keyBy('lesson_id');

        return isset($this->unlockedIds($ready, $progress)[$lesson->id]);
    }
}
