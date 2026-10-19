<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use App\Services\ServerTable;
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
    public function __invoke(Request $request, ServerTable $table): View
    {
        return $this->listing($request, $table, false);
    }

    public function data(Request $request, ServerTable $table): JsonResponse
    {
        return $this->listing($request, $table, true);
    }

    private function listing(Request $request, ServerTable $table, bool $asData): View|JsonResponse
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'per_page' => ['nullable', 'integer', 'in:10,25,50,100']]);
        $orderQuery = Order::query()->with('user:id,name,email');
        if ($filters['q'] ?? null) {
            $term = '%'.mb_strtolower(trim($filters['q'])).'%';
            $orderQuery->where(fn ($q) => $q->whereRaw('LOWER(order_number) LIKE ?', [$term])
                ->orWhereRaw('LOWER(course_title) LIKE ?', [$term])
                ->orWhereHas('user', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', [$term])));
        }
        if ($asData) return $table->response($request, $orderQuery,
            ['order', 'amount', 'status', 'created', 'actions'], 'admin.dashboard._order-cell', 'order',
            [0 => 'order_number', 1 => 'amount', 2 => 'status', 3 => 'created_at'], 'created_at', 'desc', Order::query()->count());
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
        $orders = $orderQuery->latest('created_at')->orderBy('id')->paginate((int) ($filters['per_page'] ?? 10))->withQueryString();
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

        return view('admin.dashboard', compact('kpis', 'orders', 'failedVideos', 'monthStart', 'filters'));
    }
}
