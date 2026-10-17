<?php

namespace App\Services\Checkout;

use App\Enums\CourseStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Course;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\EnrollmentService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
        private readonly MidtransClient $client,
        private readonly PaymentService $payments,
        private readonly ActivityLogger $logger,
        private readonly NotificationService $notifications,
    ) {}

    public function start(User $user, Course $course, string $channel): Order
    {
        if (! in_array($channel, config('midtrans.enabled_channels', []), true)) {
            throw ValidationException::withMessages(['channel' => 'Metode pembayaran tidak tersedia.']);
        }
        if (! config('midtrans.server_key')) {
            throw ValidationException::withMessages(['channel' => 'Pembayaran belum dapat digunakan.']);
        }

        return $this->locked($user->id, $course->id, function () use ($user, $course, $channel): Order {
            $order = DB::transaction(function () use ($user, $course): ?Order {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                if ($course->status !== CourseStatus::Published || $course->trashed()) {
                    throw ValidationException::withMessages(['course' => 'Course ini belum tersedia untuk dibeli.']);
                }
                if ($this->enrollments->activeFor($user, $course)) {
                    throw ValidationException::withMessages(['course' => 'Akses course Anda masih aktif.']);
                }
                return Order::query()->where('user_id', $user->id)->where('course_id', $course->id)
                    ->where('status', OrderStatus::Pending->value)->lockForUpdate()->latest()->first();
            });

            if ($order) {
                $last = $order->payments()->latest()->first();
                if ($last && in_array($last->status, [PaymentStatus::Initiating, PaymentStatus::Unknown], true)) return $order;
                if ($order->expires_at?->isPast()) {
                    if ($last && $last->status === PaymentStatus::Pending) {
                        try {
                            $this->payments->sync($last);
                        } catch (\Throwable) {
                            throw ValidationException::withMessages(['course' => 'Status pembayaran lama belum dapat dipastikan. Coba lagi setelah sinkronisasi.']);
                        }
                        $order->refresh();
                        if ($order->status === OrderStatus::Pending && $last->fresh()->status === PaymentStatus::Pending) {
                            throw ValidationException::withMessages(['course' => 'Status pembayaran lama sedang dikonfirmasi. Coba lagi setelah beberapa saat.']);
                        }
                    } else {
                        DB::transaction(function () use ($order): void {
                            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                            if ($locked->status !== OrderStatus::Pending || ! $locked->expires_at?->isPast()) return;
                            $locked->update(['status' => OrderStatus::Expired]);
                            $this->notifications->paymentExpired($locked);
                        });
                        $order->refresh();
                    }
                    if ($order->status === OrderStatus::Paid) {
                        throw ValidationException::withMessages(['course' => 'Pembayaran sebelumnya sudah lunas.']);
                    }
                    $order = null;
                } elseif ($last && $last->status === PaymentStatus::Pending) {
                    if (($last->channel ?: $last->payment_type) === $channel) return $order;
                    $this->cancelAttempt($last);
                    $order->refresh();
                    if ($order->status === OrderStatus::Paid) {
                        throw ValidationException::withMessages(['course' => 'Pembayaran sebelumnya sudah lunas.']);
                    }
                    if ($order->status !== OrderStatus::Pending) $order = null;
                }
            }

            $order = DB::transaction(function () use ($user, $course, $order, $channel): Order {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $course = Course::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();
                if ($course->status !== CourseStatus::Published) {
                    throw ValidationException::withMessages(['course' => 'Course ini belum tersedia untuk dibeli.']);
                }
                if ($this->enrollments->activeFor($user, $course)) {
                    throw ValidationException::withMessages(['course' => 'Akses course Anda sudah aktif.']);
                }
                if (! $order) {
                    $amount = $course->discount_price ?? $course->price;
                    if ($amount < 1) throw ValidationException::withMessages(['course' => 'Course gratis belum memakai alur pembayaran ini.']);
                    $order = Order::query()->create([
                        'order_number' => 'ORD-'.now()->utc()->format('Ymd').'-'.Str::upper(Str::random(10)),
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'course_title' => $course->title,
                        'amount' => $amount,
                        'status' => OrderStatus::Pending,
                        'expires_at' => now()->addHours(max(1, (int) config('course.pending_order_ttl_hours', 24))),
                    ]);
                    $this->logger->log('created', $user, 'orders', $order->id, null,
                        ['course_id' => $course->id, 'amount' => $amount, 'status' => 'pending']);
                } else {
                    $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                    if ($order->status !== OrderStatus::Pending) {
                        throw ValidationException::withMessages(['course' => 'Status pesanan telah berubah. Buka kembali halaman course.']);
                    }
                }
                $attempt = $order->payments()->count() + 1;
                $type = in_array($channel, ['bca', 'bni', 'bri', 'permata', 'cimb'], true) ? 'bank_transfer' : $channel;
                $payment = $order->payments()->create([
                    'gateway' => 'midtrans',
                    'midtrans_order_id' => $order->order_number.'-'.$attempt,
                    'payment_type' => $type,
                    'channel' => $type === 'bank_transfer' ? $channel : null,
                    'status' => PaymentStatus::Initiating,
                    'gross_amount' => $order->amount,
                    'expires_at' => $order->expires_at,
                ]);
                $order->setRelation('newPayment', $payment);
                return $order;
            });

            $payment = $order->getRelation('newPayment');
            try {
                $this->payments->recordCharge($payment, $this->client->charge($order->load('user'), $payment));
            } catch (\Throwable) {
                DB::transaction(function () use ($payment, $user): void {
                    $lockedOrder = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
                    $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                    if ($lockedOrder->status === OrderStatus::Paid || $lockedPayment->status !== PaymentStatus::Initiating) return;
                    $lockedPayment->update(['status' => PaymentStatus::Unknown]);
                    $this->logger->log('failed', $user, 'payments', $payment->id, null,
                        ['status' => 'unknown'], 'Hasil permintaan pembayaran belum dapat dipastikan.');
                });
            }

            return $order;
        });
    }

    public function cancel(User $user, Order $order): void
    {
        $this->locked($user->id, $order->course_id, function () use ($user, $order): void {
            $order->refresh();
            if ($order->user_id !== $user->id || $order->status !== OrderStatus::Pending) {
                throw ValidationException::withMessages(['order' => 'Pesanan ini tidak dapat dibatalkan.']);
            }
            $last = $order->payments()->latest()->first();
            if ($last && in_array($last->status, [PaymentStatus::Initiating, PaymentStatus::Unknown], true)) {
                throw ValidationException::withMessages(['order' => 'Status pembayaran belum pasti. Tunggu sinkronisasi sebelum membatalkan.']);
            }
            if ($last && $last->status === PaymentStatus::Pending) {
                $this->cancelAttempt($last);
                $order->refresh();
                if ($order->status !== OrderStatus::Pending) {
                    throw ValidationException::withMessages(['order' => 'Status pesanan telah berubah. Muat ulang halaman untuk melihat status terbaru.']);
                }
            }
            DB::transaction(function () use ($user, $order): void {
                $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                if ($locked->status !== OrderStatus::Pending) {
                    throw ValidationException::withMessages(['order' => 'Status pesanan telah berubah.']);
                }
                $locked->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
                $this->logger->log('updated', $user, 'orders', $locked->id, ['status' => 'pending'], ['status' => 'cancelled']);
            });
        });
    }

    private function cancelAttempt(Payment $payment): void
    {
        try {
            $response = $this->client->cancel($payment);
            if (($response['order_id'] ?? null) !== $payment->midtrans_order_id
                || ($response['transaction_status'] ?? null) !== 'cancel'
                || (string) ($response['status_code'] ?? '') !== '200'
                || (isset($response['gross_amount'])
                    && (int) explode('.', (string) $response['gross_amount'])[0] !== $payment->gross_amount)) {
                throw new \RuntimeException('Status pembatalan tidak pasti.');
            }
            DB::transaction(function () use ($payment): void {
                $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
                $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if ($order->status === OrderStatus::Paid || $locked->status?->isPaid()) {
                    throw new \RuntimeException('Pembayaran telah lunas.');
                }
                $locked->update(['status' => PaymentStatus::Cancel]);
            });
            return;
        } catch (\Throwable $cancelError) {
            try {
                $this->payments->sync($payment);
            } catch (\Throwable) {
                $this->logger->log('payment_cancel_failed', null, 'payments', $payment->id, null,
                    ['step' => 'cancel_and_status'] + $this->gatewayFailureDetails($cancelError));
                throw ValidationException::withMessages(['channel' => 'Status pembayaran lama belum dapat dipastikan. Buka kembali pesanan atau hubungi admin sebelum mencoba metode lain.']);
            }

            $payment->refresh();
            $payment->order->refresh();
            if ($payment->order->status === OrderStatus::Paid || $payment->status?->isPaid()) {
                throw ValidationException::withMessages(['channel' => 'Pembayaran sebelumnya sudah lunas. Periksa pesanan Anda.']);
            }
            if (in_array($payment->status, [PaymentStatus::Cancel, PaymentStatus::Expire, PaymentStatus::Deny, PaymentStatus::Failure], true)) {
                return;
            }

            if ($payment->status === PaymentStatus::Pending && $payment->order->status === OrderStatus::Pending) {
                try {
                    $response = $this->client->expire($payment);
                    if (($response['order_id'] ?? null) !== $payment->midtrans_order_id
                        || ($response['transaction_status'] ?? null) !== 'expire'
                        || (string) ($response['status_code'] ?? '') !== '407'
                        || (isset($response['gross_amount'])
                            && (int) explode('.', (string) $response['gross_amount'])[0] !== $payment->gross_amount)) {
                        throw new \RuntimeException('Status kedaluwarsa tidak pasti.');
                    }
                    DB::transaction(function () use ($payment): void {
                        $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
                        $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                        if ($order->status !== OrderStatus::Pending || $locked->status !== PaymentStatus::Pending) {
                            throw new \RuntimeException('Status pembayaran telah berubah.');
                        }
                        $locked->update(['status' => PaymentStatus::Expire]);
                    });
                    $this->logger->log('payment_expired', null, 'payments', $payment->id, null,
                        ['reason' => 'cancel_not_confirmed']);
                    return;
                } catch (\Throwable $expireError) {
                    try {
                        $this->payments->sync($payment);
                    } catch (\Throwable) {
                        $this->logger->log('payment_cancel_failed', null, 'payments', $payment->id, null,
                            ['step' => 'expire_and_status'] + $this->gatewayFailureDetails($expireError));
                        throw ValidationException::withMessages(['channel' => 'Status pembayaran lama belum dapat dipastikan. Buka kembali pesanan atau hubungi admin sebelum mencoba metode lain.']);
                    }
                    $payment->refresh();
                    $payment->order->refresh();
                    if ($payment->order->status === OrderStatus::Paid || $payment->status?->isPaid()) {
                        throw ValidationException::withMessages(['channel' => 'Pembayaran sebelumnya sudah lunas. Periksa pesanan Anda.']);
                    }
                    if (in_array($payment->status, [PaymentStatus::Cancel, PaymentStatus::Expire, PaymentStatus::Deny, PaymentStatus::Failure], true)) {
                        return;
                    }
                    $this->logger->log('payment_cancel_failed', null, 'payments', $payment->id, null,
                        ['step' => 'still_pending', 'cancel' => $this->gatewayFailureDetails($cancelError),
                            'expire' => $this->gatewayFailureDetails($expireError)]);
                }
            }

            throw ValidationException::withMessages(['channel' => 'Pembatalan pembayaran lama belum pasti. Periksa kembali pesanan sebelum mencoba metode lain.']);
        }
    }

    private function gatewayFailureDetails(\Throwable $error): array
    {
        $details = ['error_type' => $error::class];
        if ($error instanceof MidtransRequestException) {
            $details['http_status'] = $error->httpStatus;
            $details['gateway_status_code'] = $error->gatewayStatusCode;
        }

        return $details;
    }

    private function locked(string $userId, string $courseId, callable $callback): mixed
    {
        $lock = Cache::store('database')->lock('checkout:'.$userId.':'.$courseId, 90);
        if (! $lock->get()) {
            throw ValidationException::withMessages(['course' => 'Permintaan pembayaran sedang diproses. Tunggu sebentar.']);
        }
        try { return $callback(); } finally { $lock->release(); }
    }
}
