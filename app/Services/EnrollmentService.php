<?php

namespace App\Services;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\PlaybackSession;
use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollmentService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function activeFor(User $user, Course $course): ?Enrollment
    {
        return Enrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)
            ->where('status', EnrollmentStatus::Active->value)->where('expires_at', '>', now())
            ->latest('expires_at')->first();
    }

    public function createFromOrder(Order $order): Enrollment
    {
        return DB::transaction(function () use ($order): Enrollment {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status !== OrderStatus::Paid || ! $order->paid_at) {
                throw new \LogicException('Pesanan belum terkonfirmasi lunas.');
            }
            $existing = Enrollment::query()->where('order_id', $order->id)->first();
            if ($existing) return $existing;

            $user = User::query()->findOrFail($order->user_id);
            $start = $order->paid_at->copy()->utc();
            $course = Course::withTrashed()->findOrFail($order->course_id);
            $end = $start->copy()->setTimezone($user->timezone ?: 'UTC')
                ->addMonthsNoOverflow($course->access_months)->utc();
            $enrollment = Enrollment::query()->create([
                'user_id' => $order->user_id,
                'course_id' => $order->course_id,
                'order_id' => $order->id,
                'source' => EnrollmentSource::Purchase,
                'status' => EnrollmentStatus::Active,
                'starts_at' => $start,
                'expires_at' => $end,
            ]);
            $this->logger->log('assigned', null, 'enrollments', $enrollment->id, null,
                ['order_id' => $order->id, 'course_id' => $order->course_id, 'expires_at' => $end->toIso8601String()]);

            return $enrollment;
        });
    }

    public function stateFor(?User $user, Course $course): string
    {
        if (! $user) return 'guest';
        if ($user->isAdmin()) return 'active';
        if ($this->activeFor($user, $course)) return 'active';
        if (Order::query()->where('user_id', $user->id)->where('course_id', $course->id)
            ->where('status', OrderStatus::Pending->value)->where('expires_at', '>', now())->exists()) {
            return 'pending_order';
        }
        if (Enrollment::query()->where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
            return 'expired';
        }

        return 'can_buy';
    }

    public function grantManual(User $user, Course $course, User $actor, Carbon $startsAt, ?Carbon $expiresAt = null, ?string $note = null): Enrollment
    {
        return DB::transaction(function () use ($user, $course, $actor, $startsAt, $expiresAt, $note): Enrollment {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $lockedUser->is_active || ! $lockedUser->isMember()) {
                throw ValidationException::withMessages(['user_id' => 'Pilih user member yang masih aktif.']);
            }
            $expiresAt ??= $startsAt->copy()->setTimezone($lockedUser->timezone ?: 'UTC')
                ->addMonthsNoOverflow($course->access_months)->utc();
            if ($startsAt->isFuture() || $expiresAt->lessThanOrEqualTo($startsAt) || $expiresAt->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['expires_at' => 'Tanggal akses harus dimulai sekarang atau sebelumnya dan berakhir di masa depan.']);
            }
            if ($this->activeFor($user, $course)) {
                throw ValidationException::withMessages(['user_id' => 'User ini masih memiliki akses aktif ke course. Gunakan perpanjangan akses.']);
            }
            $enrollment = Enrollment::query()->create([
                'user_id' => $user->id, 'course_id' => $course->id, 'order_id' => null,
                'source' => EnrollmentSource::Admin, 'status' => EnrollmentStatus::Active,
                'starts_at' => $startsAt, 'expires_at' => $expiresAt, 'granted_by' => $actor->id, 'note' => $note,
            ]);
            $this->logger->log('assigned', $actor, 'enrollments', $enrollment->id, null, $enrollment->getAttributes());
            return $enrollment;
        });
    }

    public function changeExpiry(Enrollment $enrollment, User $actor, Carbon $expiresAt, string $note): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $actor, $expiresAt, $note): Enrollment {
            User::query()->whereKey($enrollment->user_id)->lockForUpdate()->firstOrFail();
            $enrollment = Enrollment::query()->whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            if ($enrollment->status === EnrollmentStatus::Revoked) {
                throw ValidationException::withMessages(['expires_at' => 'Akses yang sudah dicabut tidak dapat diperpanjang. Berikan akses baru.']);
            }
            if ($expiresAt->lessThanOrEqualTo($enrollment->starts_at)) {
                throw ValidationException::withMessages(['expires_at' => 'Tanggal akhir harus setelah tanggal mulai.']);
            }
            if ($expiresAt->isFuture() && Enrollment::query()->where('user_id', $enrollment->user_id)
                ->where('course_id', $enrollment->course_id)->whereKeyNot($enrollment->id)
                ->where('status', EnrollmentStatus::Active->value)->where('expires_at', '>', now())->exists()) {
                throw ValidationException::withMessages(['expires_at' => 'User sudah memiliki akses aktif lain ke course ini.']);
            }
            $old = $enrollment->getAttributes();
            $enrollment->update(['expires_at' => $expiresAt, 'status' => $expiresAt->isFuture() ? EnrollmentStatus::Active : EnrollmentStatus::Expired, 'note' => $note]);
            if (! $expiresAt->isFuture()) $this->revokePlayback($enrollment);
            $this->logger->log('updated', $actor, 'enrollments', $enrollment->id, $old, $enrollment->getAttributes(), 'Tanggal akhir akses diubah');
            return $enrollment;
        });
    }

    public function revoke(Enrollment $enrollment, User $actor, string $note): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $actor, $note): Enrollment {
            User::query()->whereKey($enrollment->user_id)->lockForUpdate()->firstOrFail();
            $enrollment = Enrollment::query()->whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            if ($enrollment->status === EnrollmentStatus::Revoked) {
                return $enrollment;
            }
            $old = $enrollment->getAttributes();
            $enrollment->update(['status' => EnrollmentStatus::Revoked, 'revoked_at' => now(), 'revoked_by' => $actor->id, 'note' => $note]);
            $this->revokePlayback($enrollment);
            $this->logger->log('unassigned', $actor, 'enrollments', $enrollment->id, $old, $enrollment->getAttributes(), 'Akses dicabut');
            return $enrollment;
        });
    }

    private function revokePlayback(Enrollment $enrollment): void
    {
        if ($this->activeFor($enrollment->user, $enrollment->course)) return;
        PlaybackSession::query()->where('user_id', $enrollment->user_id)->whereNull('revoked_at')
            ->whereHas('lesson', fn ($lesson) => $lesson->where('course_id', $enrollment->course_id))
            ->update(['revoked_at' => now()]);
    }
}
