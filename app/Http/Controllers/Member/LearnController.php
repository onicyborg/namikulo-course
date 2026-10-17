<?php

namespace App\Http\Controllers\Member;

use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\EnrollmentService;
use App\Services\LessonProgressService;
use App\Services\VideoDelivery;
use App\Models\LessonProgress;
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
        $progress = $request->user()->isMember()
            ? LessonProgress::query()->where('user_id', $request->user()->id)->where('course_id', $lesson->course_id)
                ->whereIn('lesson_id', $ready->pluck('id'))->get()->keyBy('lesson_id')
            : collect();

        return view('member.learn.show', [
            'lesson' => $lesson, 'sections' => $sections, 'progress' => $progress,
            'previous' => $position > 0 ? $ready[$position - 1] : null,
            'next' => $position !== false ? $ready->get($position + 1) : null,
            'preview' => $request->user()->isAdmin(),
            'backUrl' => $request->user()->isAdmin()
                ? route('admin.courses.preview', $lesson->course)
                : route('member.courses.show', $lesson->course->slug),
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

    public function progress(Request $request, Lesson $lesson, LessonProgressService $progress): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'position_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'watched_seconds' => ['required', 'integer', 'min:0', 'max:60'],
            'event' => ['required', 'in:tick,pause,ended'],
        ]);
        $saved = $progress->record($request->user(), $lesson, $data['token'], $data['position_seconds'],
            $data['watched_seconds'], $data['event']);

        return response()->json(['message' => 'Progres tersimpan.',
            'last_position_seconds' => $saved->last_position_seconds,
            'completed' => (bool) $saved->completed_at]);
    }
}
