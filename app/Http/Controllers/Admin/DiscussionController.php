<?php

namespace App\Http\Controllers\Admin;

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
    public function index(Request $request): View|JsonResponse
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

        if ($request->has('draw')) {
            $page = $request->validate([
                'draw' => ['required', 'integer', 'min:0'],
                'start' => ['required', 'integer', 'min:0'],
                'length' => ['required', 'integer', 'in:10,25,50,100'],
            ]);
            $filteredCount = (clone $query)->count();
            $posts = $query->with(['user:id,name', 'lesson.course', 'root'])
                ->orderBy('created_at', $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc')
                ->orderBy('id')->skip((int) $page['start'])->take((int) $page['length'])->get();

            return response()->json([
                'draw' => (int) $page['draw'],
                'recordsTotal' => LessonDiscussionPost::query()->withTrashed()->count(),
                'recordsFiltered' => $filteredCount,
                'data' => $posts->map(fn ($post) => [
                    'lesson' => view('admin.discussions.cells.lesson', compact('post'))->render(),
                    'author' => view('admin.discussions.cells.author', compact('post'))->render(),
                    'body' => view('admin.discussions.cells.body', compact('post'))->render(),
                    'status' => view('admin.discussions.cells.status', compact('post'))->render(),
                    'actions' => view('admin.discussions.cells.actions', compact('post'))->render(),
                ]),
            ]);
        }

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
