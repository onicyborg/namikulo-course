<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UploadStatus;
use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Models\VideoAsset;
use App\Models\VideoUpload;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $zone = $request->user()->timezone ?: 'UTC';
        $monthStart = now($zone)->startOfMonth();
        $nextMonth = $monthStart->copy()->addMonth();
        $kpis = [
            'users' => User::query()->count(),
            'published' => Course::query()->where('status', CourseStatus::Published->value)->count(),
            'active_enrollments' => Enrollment::query()->where('status', EnrollmentStatus::Active->value)
                ->where('expires_at', '>', now())->count(),
            'revenue' => (int) Order::query()->where('status', OrderStatus::Paid->value)
                ->where('paid_at', '>=', $monthStart->copy()->utc())
                ->where('paid_at', '<', $nextMonth->copy()->utc())->sum('amount'),
            'pending' => Order::query()->where('status', OrderStatus::Pending->value)
                ->where('expires_at', '>', now())->count(),
        ];
        $orders = Order::query()->with('user:id,name,email')->latest()->limit(6)->get();
        $failedUploads = VideoUpload::query()->where('status', UploadStatus::Failed->value)
            ->with('lesson.course')->latest()->limit(6)->get()
            ->map(fn (VideoUpload $upload): array => [
                'lesson' => $upload->lesson, 'course' => $upload->lesson?->course,
                'error' => $upload->error_message, 'at' => $upload->updated_at,
            ]);
        $failedAssets = VideoAsset::query()->where('status', VideoStatus::Failed->value)
            ->with('lesson.course')->latest()->limit(6)->get()
            ->map(fn (VideoAsset $asset): array => [
                'lesson' => $asset->lesson, 'course' => $asset->lesson?->course,
                'error' => $asset->error_message, 'at' => $asset->updated_at,
            ]);
        $failedVideos = $failedUploads->concat($failedAssets)->sortByDesc('at')->take(6);

        return view('admin.dashboard', compact('kpis', 'orders', 'failedVideos', 'monthStart'));
    }
}
