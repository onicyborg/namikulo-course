<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UploadStatus;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\OperationState;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PlaybackSession;
use App\Models\SystemLog;
use App\Models\VideoUpload;
use App\Models\NotificationDigest;
use App\Models\User;
use App\Enums\NotificationDigestStatus;
use App\Services\CleanupVideoUploads;
use App\Services\ExpireEnrollments;
use App\Services\ExpirePendingOrders;
use App\Services\PrunePlaybackSessions;
use App\Services\ReconcilePayments;
use App\Services\CreateAccessExpiryNotifications;
use App\Services\SendNotificationDigests;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:40'],
            'table' => ['nullable', 'string', 'max:80'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $digestDate = now()->setTimezone('Asia/Jakarta')->toDateString();
        $digestCutoff = Carbon::parse($digestDate.' 07:00:00', 'Asia/Jakarta')->utc();
        $digestBacklog = now()->gte($digestCutoff) ? User::query()->where('is_active', true)
            ->whereHas('userNotifications', fn ($query) => $query->whereNull('read_at')->where('created_at', '<', $digestCutoff))
            ->whereDoesntHave('notificationDigests', fn ($query) => $query->where('digest_date', $digestDate))->count() : 0;
        $tasks = [
            ExpireEnrollments::TASK => ['name' => 'Kedaluwarsa akses', 'minutes' => 60,
                'remaining' => Enrollment::query()->where('status', EnrollmentStatus::Active->value)->where('expires_at', '<=', now())->count()],
            PrunePlaybackSessions::TASK => ['name' => 'Bersihkan sesi video', 'minutes' => 15,
                'remaining' => PlaybackSession::query()->where('created_at', '<=', now()->subDays(config('operations.playback_retention_days')))
                    ->where(fn ($query) => $query->whereNotNull('revoked_at')->orWhere('hard_expires_at', '<=', now()))->count()],
            CleanupVideoUploads::TASK => ['name' => 'Bersihkan unggahan video', 'minutes' => 15,
                'remaining' => VideoUpload::query()->whereNull('cleaned_at')
                    ->where('last_activity_at', '<=', now()->subHours(config('operations.upload_stale_hours')))
                    ->where('status', '!=', UploadStatus::Ready->value)->count()],
            ExpirePendingOrders::TASK => ['name' => 'Kedaluwarsa pesanan', 'minutes' => 10,
                'remaining' => Order::query()->where('status', OrderStatus::Pending->value)->where('expires_at', '<=', now())->count()],
            ReconcilePayments::TASK => ['name' => 'Sinkron pembayaran', 'minutes' => 15,
                'remaining' => Payment::query()->whereIn('status', [PaymentStatus::Initiating->value, PaymentStatus::Unknown->value, PaymentStatus::Pending->value])
                    ->where('created_at', '<=', now()->subMinutes(10))->count()],
            CreateAccessExpiryNotifications::TASK => ['name' => 'Pengingat akses', 'minutes' => 60,
                'remaining' => Enrollment::query()->where('status', EnrollmentStatus::Active->value)
                    ->where('expires_at', '>', now())->where('expires_at', '<=', now()->addDays(9))->count()],
            SendNotificationDigests::TASK => ['name' => 'Rekap notifikasi', 'minutes' => 5, 'remaining' => $digestBacklog],
        ];
        $states = OperationState::query()->whereIn('task', array_keys($tasks))->get()->keyBy('task');
        $actionLabels = ['created' => 'Dibuat', 'updated' => 'Diubah', 'deleted' => 'Dihapus',
            'assigned' => 'Diberi akses', 'unassigned' => 'Akses dicabut', 'completed' => 'Materi selesai',
            'playback_started' => 'Pemutaran dimulai', 'operation' => 'Operasi selesai',
            'operation_failed' => 'Operasi gagal', 'login' => 'Login', 'logout' => 'Logout'];
        $tableLabels = ['users' => 'User', 'courses' => 'Course', 'orders' => 'Pesanan',
            'payments' => 'Pembayaran', 'enrollments' => 'Akses course', 'video_uploads' => 'Unggahan video',
            'lesson_discussion_posts' => 'Diskusi materi',
            'playback_sessions' => 'Sesi pemutaran', 'lesson_progress' => 'Progres belajar',
            'operation_states' => 'Tugas berkala'];
        $logs = SystemLog::query()->with('user:id,name,email');
        if ($filters['action'] ?? null) $logs->where('action', $filters['action']);
        if ($filters['table'] ?? null) $logs->where('table_name', $filters['table']);
        if ($filters['q'] ?? null) {
            $term = '%'.mb_strtolower($filters['q']).'%';
            $logs->where(fn ($query) => $query->whereRaw('LOWER(record_id) LIKE ?', [$term])
                ->orWhereRaw('LOWER(description) LIKE ?', [$term]));
        }

        return view('admin.operations.index', [
            'tasks' => $tasks, 'states' => $states, 'filters' => $filters,
            'digestStats' => NotificationDigest::query()->where('digest_date', $digestDate)
                ->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'digestDate' => $digestDate,
            'actionLabels' => $actionLabels, 'tableLabels' => $tableLabels,
            'logs' => $logs->latest()->paginate(25)->withQueryString(),
        ]);
    }
}
