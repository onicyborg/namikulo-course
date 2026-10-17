<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DiscussionPostStatus;
use App\Http\Controllers\Controller;
use App\Models\LessonDiscussionPost;
use App\Services\LessonDiscussionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:visible,hidden,deleted']]);
        $posts = LessonDiscussionPost::query()->withTrashed()->with(['user:id,name', 'lesson.course', 'root'])
            ->when(($filters['status'] ?? null) === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when(($filters['status'] ?? null) === 'visible', fn ($query) => $query->whereNull('deleted_at')->where('status', DiscussionPostStatus::Visible->value))
            ->when(($filters['status'] ?? null) === 'hidden', fn ($query) => $query->whereNull('deleted_at')->where('status', DiscussionPostStatus::Hidden->value))
            ->latest('created_at')->paginate(25)->withQueryString();

        return view('admin.discussions.index', ['posts' => $posts, 'filters' => $filters]);
    }

    public function hide(Request $request, LessonDiscussionPost $post, LessonDiscussionService $discussion): RedirectResponse
    {
        $discussion->moderate($request->user(), $post, DiscussionPostStatus::Hidden);
        return back()->with('success', 'Kiriman disembunyikan.');
    }

    public function restore(Request $request, LessonDiscussionPost $post, LessonDiscussionService $discussion): RedirectResponse
    {
        $discussion->moderate($request->user(), $post, DiscussionPostStatus::Visible);
        return back()->with('success', 'Kiriman ditampilkan kembali.');
    }
}
