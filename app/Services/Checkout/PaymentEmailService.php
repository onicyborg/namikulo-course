<?php

namespace App\Services\Checkout;

use App\Mail\PaymentConfirmed;
use App\Models\Order;
use App\Enums\OrderStatus;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class PaymentEmailService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function sendOnce(string $orderId, bool $retry = false): bool
    {
        $lock = Cache::store('database')->lock('payment-email:'.$orderId, 20);
        if (! $lock->get()) return false;

        try {
            $order = Order::query()->with(['user', 'enrollment'])->findOrFail($orderId);
            if ($order->status !== OrderStatus::Paid || $order->payment_email_sent_at) return false;
            if ($order->payment_email_last_attempt_at && ! $retry) return false;
            if (in_array(config('mail.default'), ['log', 'array'], true)) {
                $this->logger->log('email_skipped', null, 'orders', $order->id, null,
                    ['reason' => 'mailer_not_delivering']);
                return false;
            }

            $order->update(['payment_email_last_attempt_at' => now()]);
            try {
                Mail::to($order->user->email)->send(new PaymentConfirmed($order));
                $order->update(['payment_email_sent_at' => now()]);
                $this->logger->log('sent', null, 'orders', $order->id, null, ['email' => 'payment_confirmed']);
                return true;
            } catch (\Throwable) {
                $this->logger->log('failed', null, 'orders', $order->id, null, ['email' => 'payment_confirmed'], 'Pengiriman email perlu ditinjau.');
                return false;
            }
        } finally {
            $lock->release();
        }
    }
}
