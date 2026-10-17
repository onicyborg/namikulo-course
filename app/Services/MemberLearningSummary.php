<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class MemberLearningSummary
{
    public function forCourses(User $user, Collection $courses): Collection
    {
        $courseIds = $courses->pluck('id')->filter()->unique()->values();
        if ($courseIds->isEmpty()) return collect();

        $lessons = Lesson::query()->whereIn('course_id', $courseIds)->whereHas('section')
            ->get(['id', 'course_id']);
        $completed = LessonProgress::query()->where('user_id', $user->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))->whereNotNull('completed_at')
            ->pluck('lesson_id')->flip();

        return $lessons->groupBy('course_id')->map(function (Collection $items) use ($completed): array {
            $total = $items->count();
            $done = $items->filter(fn (Lesson $lesson) => $completed->has($lesson->id))->count();
            return ['total' => $total, 'completed' => $done,
                'percent' => $total ? (int) round($done * 100 / $total) : 0];
        });
    }
}
