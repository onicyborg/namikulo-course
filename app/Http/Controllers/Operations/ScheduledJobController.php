<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Services\ExpireEnrollments;
use App\Services\PrunePlaybackSessions;
use App\Services\CleanupVideoUploads;
use App\Services\ExpirePendingOrders;
use App\Services\ReconcilePayments;
use App\Services\CreateAccessExpiryNotifications;
use App\Services\SendNotificationDigests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ScheduledJobController extends Controller
{
    public function store(string $task, ExpireEnrollments $expiry, PrunePlaybackSessions $playback, CleanupVideoUploads $uploads,
        ExpirePendingOrders $orders, ReconcilePayments $payments, CreateAccessExpiryNotifications $accessReminders,
        SendNotificationDigests $digests): JsonResponse
    {
        $services = [ExpireEnrollments::TASK => $expiry, PrunePlaybackSessions::TASK => $playback, CleanupVideoUploads::TASK => $uploads,
            ExpirePendingOrders::TASK => $orders, ReconcilePayments::TASK => $payments,
            CreateAccessExpiryNotifications::TASK => $accessReminders, SendNotificationDigests::TASK => $digests];
        abort_unless(isset($services[$task]), 404);
        $batchSize = max(1, min(100, (int) config('operations.batch_size', 20)));
        $maxSeconds = max(2, min(10, (int) config('operations.max_seconds', 10)));
        try {
            $lock = Cache::store('database')->lock('operations:'.$task, $maxSeconds + 10);
            if (! $lock->get()) {
                return response()->json(['status' => 'busy', 'processed' => 0, 'remaining' => true]);
            }
            try {
                return response()->json($services[$task]->run($batchSize, $maxSeconds));
            } finally {
                $lock->release();
            }
        } catch (\Throwable) {
            return response()->json(['message' => 'Operasi gagal; periksa log server dan coba lagi.'], 503);
        }
    }
}
