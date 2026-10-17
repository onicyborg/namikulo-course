<?php

namespace App\Http\Controllers\Member;

use App\Enums\EnrollmentStatus;
use App\Enums\CourseStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Services\MemberLearningSummary;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MemberLearningSummary $summary): View
    {
        $enrollments = Enrollment::query()
            ->where('user_id', $request->user()->id)
            ->where('status', EnrollmentStatus::Active->value)
            ->where('expires_at', '>', now())
            ->whereHas('course')
            ->with(['course.category'])
            ->orderBy('expires_at')
            ->paginate(10);

        $progress = $summary->forCourses($request->user(), $enrollments->getCollection()->pluck('course'));
        $activeCourseIds = Enrollment::query()->where('user_id', $request->user()->id)
            ->where('status', EnrollmentStatus::Active->value)->where('expires_at', '>', now())
            ->select('course_id');
        $ended = Enrollment::query()->where('user_id', $request->user()->id)
            ->whereNotIn('course_id', $activeCourseIds)
            ->where(fn ($query) => $query->where('status', '!=', EnrollmentStatus::Active->value)
                ->orWhere('expires_at', '<=', now()))
            ->whereHas('course')->with('course')->orderByDesc('expires_at')->limit(3)->get();
        $pendingOrders = Order::query()->where('user_id', $request->user()->id)
            ->where('status', OrderStatus::Pending->value)->where('expires_at', '>', now())
            ->latest()->limit(3)->get();
        $recommendations = Course::query()->where('status', CourseStatus::Published->value)
            ->whereDoesntHave('enrollments', fn ($query) => $query->where('user_id', $request->user()->id)
                ->where('status', EnrollmentStatus::Active->value)->where('expires_at', '>', now()))
            ->latest('published_at')->limit(3)->get();

        return view('member.dashboard', compact('enrollments', 'progress', 'ended', 'pendingOrders', 'recommendations'));
    }
}
