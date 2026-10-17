<?php

namespace App\Http\Controllers\Member;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Services\MemberLearningSummary;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request, MemberLearningSummary $summary): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:active,ended']]);
        $userId = $request->user()->id;
        $active = fn ($query) => $query->where('user_id', $userId)
            ->where('status', EnrollmentStatus::Active->value)->where('expires_at', '>', now());
        $query = Course::withTrashed()->whereHas('enrollments', fn ($query) => $query->where('user_id', $userId))
            ->with(['category', 'enrollments' => fn ($query) => $query->where('user_id', $userId)
                ->orderByDesc('expires_at')]);
        if (($filters['status'] ?? null) === 'active') $query->whereHas('enrollments', $active);
        if (($filters['status'] ?? null) === 'ended') $query->whereDoesntHave('enrollments', $active);

        $courses = $query->orderBy('title')->paginate(12)->withQueryString();
        $progress = $summary->forCourses($request->user(), $courses->getCollection());
        $states = $courses->getCollection()->mapWithKeys(function (Course $course): array {
            $active = $course->enrollments->first(fn (Enrollment $item) => $item->status === EnrollmentStatus::Active
                && $item->expires_at->isFuture());
            return [$course->id => ['enrollment' => $active ?: $course->enrollments->first(), 'active' => (bool) $active]];
        });

        return view('member.courses.index', compact('courses', 'progress', 'filters', 'states'));
    }

    public function show(Request $request, Course $course, MemberLearningSummary $summary): View
    {
        $userId = $request->user()->id;
        $enrollments = Enrollment::query()->where('user_id', $userId)->where('course_id', $course->id)
            ->orderByDesc('expires_at')->get();
        abort_if($enrollments->isEmpty(), 404);
        $active = $enrollments->first(fn (Enrollment $item) => $item->status === EnrollmentStatus::Active
            && $item->expires_at->isFuture());
        $current = $active ?: $enrollments->first();
        $course->load(['category', 'sections.lessons.videoAsset']);
        $lessons = $course->sections->flatMap->lessons;
        $lessonIds = $lessons->pluck('id');
        $lessonProgress = LessonProgress::query()->where('user_id', $userId)
            ->where('course_id', $course->id)->whereIn('lesson_id', $lessonIds)->get()->keyBy('lesson_id');
        $summaryData = $summary->forCourses($request->user(), collect([$course]))->get($course->id,
            ['total' => 0, 'completed' => 0, 'percent' => 0]);
        $firstReady = $lessons->first(fn ($lesson) => $lesson->videoAsset?->status === VideoStatus::Ready);
        $pendingOrder = ! $active ? Order::query()->where('user_id', $userId)->where('course_id', $course->id)
            ->where('status', OrderStatus::Pending->value)->where('expires_at', '>', now())->latest()->first() : null;
        $canBuy = ! $active && ! $pendingOrder && ! $course->trashed()
            && $course->status === CourseStatus::Published;

        return view('member.courses.show', compact('course', 'current', 'active', 'lessonProgress',
            'summaryData', 'firstReady', 'pendingOrder', 'canBuy'));
    }
}
