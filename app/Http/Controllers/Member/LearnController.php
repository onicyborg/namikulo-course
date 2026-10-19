<?php

namespace App\Http\Controllers\Member;

use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\LessonProgressService;
use App\Services\LessonDiscussionService;
use App\Services\LessonSequence;
use App\Enums\DiscussionPostStatus;
use App\Services\VideoDelivery;
use App\Models\LessonProgress;
use App\Models\LessonDiscussionPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class LearnController extends Controller
{
    public function show(Request $request, Lesson $lesson, LessonDiscussionService $discussion, LessonSequence $sequence): View
    {
        $lesson->load(['course', 'section', 'videoAsset']);
        $discussion->authorizeLesson($request->user(), $lesson);
        abort_unless($lesson->videoAsset?->status === VideoStatus::Ready, 404, 'Video belum siap.');

        $focusId = $request->query('focus');
        $focusedPost = is_string($focusId) && Str::isUuid($focusId)
            ? $lesson->discussionPosts()->whereKey($focusId)
                ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('status', DiscussionPostStatus::Visible->value))
                ->first() : null;
        $focusedRootId = $focusedPost?->parent_id ?? $focusedPost?->id;
        if ($focusedPost?->parent_id) {
            $rootVisible = $lesson->discussionPosts()->whereKey($focusedRootId)
                ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('status', DiscussionPostStatus::Visible->value))
                ->exists();
            if (! $rootVisible) $focusedPost = null;
        }

        $threadQuery = $lesson->discussionPosts()->whereNull('parent_id')
            ->where(fn ($query) => $query->where('status', DiscussionPostStatus::Visible->value)
                ->when($request->user()->isAdmin() && $focusedPost, fn ($query) => $query->orWhere('id', $focusedRootId)))
            ->with(['user:id,name,role', 'replies' => fn ($query) => $query
                ->where('status', DiscussionPostStatus::Visible->value)->with(['user:id,name,role', 'replyTo.user:id,name'])
                ->oldest('created_at')->limit(3)])
            ->withCount(['replies as visible_replies_count' => fn ($query) => $query->where('status', DiscussionPostStatus::Visible->value)]);
        $threadPage = null;
        if ($focusedPost && ! $request->has('discussion_page')) {
            $root = $lesson->discussionPosts()->whereKey($focusedRootId)->first();
            $beforeOrAt = (clone $threadQuery)->where(fn ($query) => $query
                ->where('created_at', '>', $root->created_at)
                ->orWhere(fn ($sameTime) => $sameTime->where('created_at', $root->created_at)
                    ->where('id', '>=', $root->id)))->count();
            $threadPage = max(1, (int) ceil($beforeOrAt / 10));
        }
        $discussionThreads = $threadQuery->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(10, ['*'], 'discussion_page', $threadPage);
        if ($focusedPost?->parent_id) {
            $focusedThread = $discussionThreads->getCollection()->firstWhere('id', $focusedRootId);
            if ($focusedThread && ! $focusedThread->replies->contains('id', $focusedPost->id)) {
                $focusedPost->load(['user:id,name,role', 'replyTo.user:id,name']);
                $focusedThread->setRelation('replies', $focusedThread->replies->push($focusedPost)
                    ->sort(fn ($a, $b) => $a->created_at->timestamp <=> $b->created_at->timestamp
                        ?: strcmp($a->id, $b->id))->values());
            }
        }

        $ready = $sequence->readyLessons($lesson->course);
        $sections = $lesson->course->sections;
        $position = $ready->search(fn ($item) => $item->id === $lesson->id);
        $progress = $request->user()->isMember()
            ? LessonProgress::query()->where('user_id', $request->user()->id)->where('course_id', $lesson->course_id)
                ->whereIn('lesson_id', $ready->pluck('id'))->get()->keyBy('lesson_id')
            : collect();
        $unlockedIds = $request->user()->isAdmin() ? $ready->pluck('id')->flip()->all()
            : $sequence->unlockedIds($ready, $progress);

        return view('member.learn.show', [
            'lesson' => $lesson, 'sections' => $sections, 'progress' => $progress, 'unlockedIds' => $unlockedIds,
            'discussionThreads' => $discussionThreads,
            'readyCount' => $ready->count(),
            'lessonNumber' => $position !== false ? $position + 1 : null,
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

    public function progress(Request $request, Lesson $lesson, LessonProgressService $progress, LessonSequence $sequence): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'position_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'watched_seconds' => ['required', 'integer', 'min:0', 'max:60'],
            'event' => ['required', 'in:tick,pause,ended'],
        ]);
        $saved = $progress->record($request->user(), $lesson, $data['token'], $data['position_seconds'],
            $data['watched_seconds'], $data['event']);

        $nextUnlockedId = null;
        if ($saved->completed_at) {
            $lesson->loadMissing('course');
            $ready = $sequence->readyLessons($lesson->course);
            $position = $ready->search(fn ($item) => $item->id === $lesson->id);
            $next = $position !== false ? $ready->get($position + 1) : null;
            if ($next) {
                $completed = LessonProgress::query()->where('user_id', $request->user()->id)
                    ->where('course_id', $lesson->course_id)->whereIn('lesson_id', $ready->pluck('id'))
                    ->get()->keyBy('lesson_id');
                if (isset($sequence->unlockedIds($ready, $completed)[$next->id])) $nextUnlockedId = $next->id;
            }
        }

        return response()->json(['message' => 'Progres tersimpan.',
            'last_position_seconds' => $saved->last_position_seconds,
            'completed' => (bool) $saved->completed_at,
            'next_unlocked_id' => $nextUnlockedId]);
    }
}
