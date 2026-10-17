<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, NotificationService $service): View
    {
        $user = $request->user();
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
        ]);
    }

    public function read(Request $request, UserNotification $notification): RedirectResponse
    {
        $owned = $request->user()->userNotifications()->whereKey($notification->id)->firstOrFail();
        if (! $owned->read_at) $owned->update(['read_at' => now()]);
        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->userNotifications()->whereNull('read_at')->update(['read_at' => now()]);
        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
