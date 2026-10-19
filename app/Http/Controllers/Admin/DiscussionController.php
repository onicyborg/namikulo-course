<?php

namespace App\Http\Controllers\Admin;

use App\Services\ServerTable;
use App\Enums\DiscussionPostStatus;
use App\Http\Controllers\Controller;
use App\Models\LessonDiscussionPost;
use App\Services\LessonDiscussionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    public function index(Request $request, ServerTable $table): View
    {
        return $this->listing($request, $table, false);
    }

    public function data(Request $request, ServerTable $table): JsonResponse
    {
        return $this->listing($request, $table, true);
    }

    private function listing(Request $request, ServerTable $table, bool $asData): View|JsonResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:visible,hidden,deleted'],
            'q' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
        ]);
        $query = LessonDiscussionPost::query()->withTrashed()
            ->when(($filters['status'] ?? null) === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when(($filters['status'] ?? null) === 'visible', fn ($query) => $query->whereNull('deleted_at')->where('status', DiscussionPostStatus::Visible->value))
            ->when(($filters['status'] ?? null) === 'hidden', fn ($query) => $query->whereNull('deleted_at')->where('status', DiscussionPostStatus::Hidden->value));

        if ($filters['q'] ?? null) {
            $term = '%'.mb_strtolower($filters['q']).'%';
            $query->where(function ($match) use ($term): void {
                $match->whereRaw('LOWER(lesson_discussion_posts.body) LIKE ?', [$term])
                    ->orWhereHas('user', fn ($user) => $user->whereRaw('LOWER(users.name) LIKE ?', [$term]))
                    ->orWhereHas('lesson', fn ($lesson) => $lesson->whereRaw('LOWER(lessons.title) LIKE ?', [$term])
                        ->orWhereHas('course', fn ($course) => $course->whereRaw('LOWER(courses.title) LIKE ?', [$term])));
            });
        }

        if ($asData) return $table->response($request,
            $query->with(['user:id,name', 'lesson.course', 'root']),
            ['lesson', 'author', 'body', 'status', 'actions'], 'admin.discussions._cell', 'post',
            [1 => 'lesson_discussion_posts.created_at'], 'lesson_discussion_posts.created_at', 'desc',
            LessonDiscussionPost::query()->withTrashed()->count());

        $posts = $query->with(['user:id,name', 'lesson.course', 'root'])
            ->orderByDesc('created_at')->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 10))->withQueryString();

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
