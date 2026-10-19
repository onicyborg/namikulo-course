<?php

namespace App\Http\Controllers\Member;

use App\Enums\DiscussionPostStatus;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonDiscussionPost;
use App\Services\LessonDiscussionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class LessonDiscussionController extends Controller
{
    public function thread(Request $request, Lesson $lesson, LessonDiscussionPost $post, LessonDiscussionService $discussion): View|RedirectResponse
    {
        $root = $discussion->visibleRoot($lesson, $post, $request->user());
        if (! $request->boolean('dialog')) {
            $focusId = $request->query('focus');
            $focus = is_string($focusId) && Str::isUuid($focusId)
                ? $root->replies()->whereKey($focusId)
                    ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('status', DiscussionPostStatus::Visible->value))
                    ->first() : null;
            $target = $focus?->id ?? $root->id;
            return redirect()->to(route('learn.show', $lesson).'?focus='.$target.'#discussion-post-'.$target);
        }
        $root->load('user:id,name,role');
        $replyQuery = $root->replies()->when(! $request->user()->isAdmin(),
            fn ($query) => $query->where('status', DiscussionPostStatus::Visible->value));
        $page = null;
        $focusId = $request->query('focus');
        if (! $request->has('page') && is_string($focusId) && Str::isUuid($focusId) && $focusId !== $root->id) {
            $focus = (clone $replyQuery)->whereKey($focusId)->first();
            if ($focus) {
                $position = (clone $replyQuery)->where(fn ($query) => $query
                    ->where('created_at', '<', $focus->created_at)
                    ->orWhere(fn ($sameTime) => $sameTime->where('created_at', $focus->created_at)->where('id', '<=', $focus->id)))
                    ->count();
                $page = max(1, (int) ceil($position / 20));
            }
        }
        $replies = $replyQuery->with(['user:id,name,role', 'replyTo.user:id,name'])
            ->orderBy('created_at')->orderBy('id')->paginate(20, ['*'], 'page', $page);
        $replyToId = $request->query('reply_to');
        $replyTo = is_string($replyToId) && Str::isUuid($replyToId) ? LessonDiscussionPost::query()->whereKey($replyToId)
            ->where('lesson_id', $lesson->id)->where('status', DiscussionPostStatus::Visible->value)
            ->where(fn ($query) => $query->whereKey($root->id)->orWhere('parent_id', $root->id))
            ->with('user:id,name')->first() : null;

        return view('member.learn._discussion-thread-content', [
            'lesson' => $lesson, 'root' => $root, 'replies' => $replies,
            'replyTo' => $replyTo, 'inDialog' => true,
        ]);
    }

    public function store(Request $request, Lesson $lesson, LessonDiscussionService $discussion): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'body_format' => ['nullable', 'in:plain,html'],
            'parent_id' => ['nullable', 'uuid'],
            'reply_to_id' => ['nullable', 'uuid'],
        ]);
        $post = $discussion->create($request->user(), $lesson, $data['body'], $data['parent_id'] ?? null, $data['reply_to_id'] ?? null, $data['body_format'] ?? 'plain');
        $dialogTarget = $post->parent_id
            ? route('learn.discussion.thread', [$lesson, $post->parent_id]).'?focus='.$post->id.'#discussion-post-'.$post->id
            : route('learn.show', $lesson).'#discussion-post-'.$post->id;

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Balasan tersimpan.', 'url' => $dialogTarget,
                'post_id' => $post->id]);
        }

        return redirect()->to(route('learn.show', $lesson).'?focus='.$post->id.'#discussion-post-'.$post->id)
            ->with('success', 'Kiriman diskusi tersimpan.');
    }

    public function destroy(Request $request, Lesson $lesson, LessonDiscussionPost $post, LessonDiscussionService $discussion): RedirectResponse
    {
        $discussion->delete($request->user(), $lesson, $post);
        return back()->with('success', 'Kiriman diskusi dihapus.');
    }
}
