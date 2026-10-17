<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\UserNotificationType;
use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;

class NotificationService
{
    public function paymentPending(Order $order): void
    {
        $this->create($order->user_id, UserNotificationType::PaymentPending, 'payment:pending:'.$order->id,
            'Pembayaran menunggu', 'Instruksi pembayaran tersedia untuk pesanan '.$order->order_number.'.',
            orderId: $order->id, courseId: $order->course_id);
    }

    public function paymentPaid(Order $order): void
    {
        $this->create($order->user_id, UserNotificationType::PaymentPaid, 'payment:paid:'.$order->id,
            'Pembayaran berhasil', 'Pembayaran pesanan '.$order->order_number.' telah diterima.',
            orderId: $order->id, courseId: $order->course_id);

        User::query()->where('role', UserRole::Admin->value)->where('is_active', true)->select('id')->chunkById(100,
            function ($admins) use ($order): void {
                foreach ($admins as $admin) {
                    $this->create($admin->id, UserNotificationType::PaymentPaid, 'payment:paid:'.$order->id,
                        'Pembelian lunas', 'Pesanan '.$order->order_number.' telah lunas.',
                        orderId: $order->id, courseId: $order->course_id);
                }
            });
    }

    public function paymentExpired(Order $order): void
    {
        $this->create($order->user_id, UserNotificationType::PaymentExpired, 'payment:expired:'.$order->id,
            'Pesanan kedaluwarsa', 'Batas pembayaran pesanan '.$order->order_number.' telah berakhir.',
            orderId: $order->id, courseId: $order->course_id);
    }

    public function accessGranted(Enrollment $enrollment, User $actor): void
    {
        $this->create($enrollment->user_id, UserNotificationType::AccessGranted, 'access:granted:'.$enrollment->id,
            'Akses course diberikan', 'Anda dapat membuka course dari halaman Course Saya.',
            actorId: $actor->id, courseId: $enrollment->course_id, enrollmentId: $enrollment->id);
    }

    public function accessRevoked(Enrollment $enrollment, User $actor): void
    {
        $this->create($enrollment->user_id, UserNotificationType::AccessRevoked, 'access:revoked:'.$enrollment->id,
            'Akses course dicabut', 'Akses untuk course ini telah dicabut oleh admin.',
            actorId: $actor->id, courseId: $enrollment->course_id, enrollmentId: $enrollment->id);
    }

    public function accessExpiring(Enrollment $enrollment): void
    {
        $key = 'access:expiring:'.$enrollment->id.':'.$enrollment->expires_at->copy()->utc()->format('YmdHis');
        $this->create($enrollment->user_id, UserNotificationType::AccessExpiring, $key,
            'Akses segera berakhir', 'Akses course ini akan segera berakhir. Periksa tanggalnya di Course Saya.',
            courseId: $enrollment->course_id, enrollmentId: $enrollment->id);
    }

    public function target(UserNotification $notification, User $viewer, ?array $activeCourseIds = null): ?string
    {
        if ($notification->user_id !== $viewer->id) return null;
        if ($viewer->isAdmin()) {
            return $notification->order ? route('admin.orders.show', $notification->order) : null;
        }
        if ($notification->type === UserNotificationType::PaymentPending) {
            $order = $notification->order;
            return $order && $order->user_id === $viewer->id && $order->status === OrderStatus::Pending
                ? route('orders.pay', $order) : route('member.orders.index');
        }
        if ($notification->type === UserNotificationType::PaymentExpired || $notification->type === UserNotificationType::AccessRevoked) {
            return $notification->type === UserNotificationType::PaymentExpired ? route('member.orders.index') : route('member.courses.index');
        }
        if ($notification->course && ! $notification->course->trashed()
            && ($activeCourseIds !== null
                ? in_array($notification->course_id, $activeCourseIds, true)
                : (bool) app(EnrollmentService::class)->activeFor($viewer, $notification->course))) {
            return route('member.courses.show', $notification->course->slug);
        }

        return $notification->type === UserNotificationType::PaymentPaid ? route('member.orders.index') : null;
    }

    private function create(string $userId, UserNotificationType $type, string $key, string $title, string $summary,
        ?string $actorId = null, ?string $courseId = null, ?string $orderId = null, ?string $enrollmentId = null): void
    {
        UserNotification::query()->firstOrCreate(['user_id' => $userId, 'dedupe_key' => $key], [
            'actor_id' => $actorId, 'type' => $type, 'title' => $title, 'summary' => $summary,
            'course_id' => $courseId, 'order_id' => $orderId, 'enrollment_id' => $enrollmentId,
        ]);
    }
}
