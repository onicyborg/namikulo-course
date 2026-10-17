<?php

namespace App\Http\Controllers\Member;

use App\Enums\EnrollmentStatus;
use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $enrollments = Enrollment::query()
            ->where('user_id', $request->user()->id)
            ->where('status', EnrollmentStatus::Active->value)
            ->where('expires_at', '>', now())
            ->whereHas('course')
            ->with(['course.category'])
            ->orderBy('expires_at')
            ->paginate(10);

        $firstReadyLessons = Lesson::query()
            ->whereIn('course_id', $enrollments->getCollection()->pluck('course_id'))
            ->whereHas('videoAsset', fn ($query) => $query->where('status', VideoStatus::Ready->value))
            ->orderBy('sort_order')
            ->get(['id', 'course_id'])
            ->unique('course_id')
            ->keyBy('course_id');

        return view('member.dashboard', compact('enrollments', 'firstReadyLessons'));
    }
}
