<?php

namespace App\Http\Controllers\Member;

use App\Enums\VideoStatus;
use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\EnrollmentService;
use App\Services\VideoDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearnController extends Controller
{
    public function show(Request $request, Lesson $lesson, EnrollmentService $enrollments): View
    {
        $lesson->load(['course', 'section', 'videoAsset']);
        abort_unless($request->user()->isAdmin() || $enrollments->activeFor($request->user(), $lesson->course), 403, 'Akses course tidak aktif.');
        abort_unless($lesson->videoAsset?->status === VideoStatus::Ready, 404, 'Video belum siap.');

        $sections = $lesson->course->sections()->with(['lessons' => fn ($query) => $query->with('videoAsset')])->get();
        $ready = $sections->flatMap->lessons->filter(fn ($item) => $item->videoAsset?->status === VideoStatus::Ready)->values();
        $position = $ready->search(fn ($item) => $item->id === $lesson->id);

        return view('member.learn.show', [
            'lesson' => $lesson, 'sections' => $sections,
            'previous' => $position > 0 ? $ready[$position - 1] : null,
            'next' => $position !== false ? $ready->get($position + 1) : null,
            'preview' => $request->user()->isAdmin(),
            'backUrl' => $request->user()->isAdmin()
                ? route('admin.courses.preview', $lesson->course)
                : ($lesson->course->status === CourseStatus::Published
                    ? route('site.courses.show', $lesson->course->slug) : route('member.dashboard')),
        ]);
    }

    public function playback(Request $request, Lesson $lesson, VideoDelivery $delivery): JsonResponse
    {
        return response()->json($delivery->startSession($request->user(), $lesson));
    }

    public function heartbeat(Request $request, string $token, VideoDelivery $delivery): JsonResponse
    {
        return response()->json($delivery->heartbeat($request->user(), $token));
    }
}
