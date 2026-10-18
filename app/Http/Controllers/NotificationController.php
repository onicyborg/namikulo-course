<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $service): View|JsonResponse
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            $filters = $request->validate([
                'q' => ['nullable', 'string', 'max:120'],
                'status' => ['nullable', 'in:unread,read'],
                'per_page' => ['nullable', 'integer', 'in:10,25,50,100'],
            ]);
            $query = $user->userNotifications()
                ->when(($filters['status'] ?? null) === 'unread', fn ($query) => $query->whereNull('read_at'))
                ->when(($filters['status'] ?? null) === 'read', fn ($query) => $query->whereNotNull('read_at'));

            if ($filters['q'] ?? null) {
                $term = '%'.mb_strtolower(trim($filters['q'])).'%';
                $query->where(fn ($match) => $match->whereRaw('LOWER(user_notifications.title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(user_notifications.summary) LIKE ?', [$term]));
            }

            $relations = ['course', 'order:id,user_id,status', 'discussionPost.lesson.course', 'discussionPost.root'];
            if ($request->has('draw')) {
                $page = $request->validate([
                    'draw' => ['required', 'integer', 'min:0'],
                    'start' => ['required', 'integer', 'min:0'],
                    'length' => ['required', 'integer', 'in:10,25,50,100'],
                ]);
                $columns = [0 => 'title', 1 => 'summary', 2 => 'created_at', 3 => 'read_at'];
                $sort = $columns[(int) $request->input('order.0.column')] ?? 'created_at';
                $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
                $filteredCount = (clone $query)->count();
                $items = $query->with($relations)->orderBy('user_notifications.'.$sort, $direction)
                    ->orderBy('user_notifications.id')->skip((int) $page['start'])->take((int) $page['length'])->get();

                return response()->json([
                    'draw' => (int) $page['draw'],
                    'recordsTotal' => $user->userNotifications()->count(),
                    'recordsFiltered' => $filteredCount,
                    'data' => $items->map(function ($item) use ($service, $user) {
                        $target = $service->target($item, $user);
                        return [
                            'title' => view('admin.notifications.cells.title', compact('item'))->render(),
                            'summary' => view('admin.notifications.cells.summary', compact('item'))->render(),
                            'created' => view('admin.notifications.cells.created', compact('item'))->render(),
                            'status' => view('admin.notifications.cells.status', compact('item'))->render(),
                            'actions' => view('admin.notifications.cells.actions', compact('item', 'target'))->render(),
                        ];
                    }),
                ]);
            }

            $items = $query->with($relations)->orderByDesc('user_notifications.created_at')
                ->orderBy('user_notifications.id')->paginate((int) ($filters['per_page'] ?? 10))->withQueryString();
            $targets = [];
            foreach ($items as $item) $targets[$item->id] = $service->target($item, $user);

            return view('notifications.index', [
                'items' => $items, 'targets' => $targets, 'filters' => $filters,
                'hasNotifications' => $user->userNotifications()->exists(),
                'layout' => 'layouts.admin', 'routePrefix' => 'admin.notifications', 'adminTable' => true,
            ]);
        }

        $items = $user->userNotifications()->with(['course', 'order:id,user_id,status', 'discussionPost.lesson.course', 'discussionPost.root'])
            ->latest('created_at')->paginate(15);
        $courseIds = $items->getCollection()->pluck('course_id')->filter()->unique()->all();
        $activeCourseIds = $user->isMember() ? Enrollment::query()->where('user_id', $user->id)
            ->whereIn('course_id', $courseIds)->where('status', EnrollmentStatus::Active->value)
            ->where('expires_at', '>', now())->pluck('course_id')->all() : [];
        $targets = [];
        foreach ($items as $item) $targets[$item->id] = $service->target($item, $user, $activeCourseIds);

        return view('notifications.index', [
            'items' => $items, 'targets' => $targets,
            'layout' => $user->isAdmin() ? 'layouts.admin' : 'layouts.member',
            'routePrefix' => $user->isAdmin() ? 'admin.notifications' : 'member.notifications',
            'adminTable' => false,
        ]);
    }

    public function read(Request $request, UserNotification $notification): RedirectResponse
    {
        $owned = $request->user()->userNotifications()->whereKey($notification->id)->firstOrFail();
        if (! $owned->read_at) $owned->update(['read_at' => now()]);
        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function open(Request $request, UserNotification $notification, NotificationService $service): RedirectResponse
    {
        $owned = $request->user()->userNotifications()
            ->with(['order:id,user_id,status', 'discussionPost.lesson.course', 'discussionPost.root'])
            ->whereKey($notification->id)->firstOrFail();
        $target = $service->target($owned, $request->user());

        if (! $owned->read_at) $owned->update(['read_at' => now()]);

        return redirect()->to($target ?: route($request->user()->isAdmin() ? 'admin.notifications.index' : 'member.notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->userNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
