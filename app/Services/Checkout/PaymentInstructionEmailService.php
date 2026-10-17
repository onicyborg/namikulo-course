<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Mail\PaymentInstructions;
use App\Models\Payment;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class PaymentInstructionEmailService
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function sendOnce(string $paymentId, bool $retry = false): bool
    {
        $lock = Cache::store('database')->lock('payment-instructions-email:'.$paymentId, 60);
        if (! $lock->get()) return false;

        try {
            $payment = Payment::query()->with('order.user')->findOrFail($paymentId);
            if ($payment->status !== PaymentStatus::Pending
                || $payment->order->status !== OrderStatus::Pending
                || $payment->expires_at?->isPast()
                || $payment->instruction_email_sent_at
                || ($payment->instruction_email_last_attempt_at && ! $retry)) return false;

            if (in_array(config('mail.default'), ['log', 'array'], true)) {
                $this->logger->log('email_skipped', null, 'payments', $payment->id, null,
                    ['reason' => 'mailer_not_delivering', 'email' => 'payment_instructions']);
                return false;
            }

            $qrImage = $this->qrImage($payment);
            $payment->refresh();
            if ($payment->status !== PaymentStatus::Pending
                || $payment->expires_at?->isPast()
                || $payment->order()->value('status') !== OrderStatus::Pending) return false;

            $payment->update(['instruction_email_last_attempt_at' => now()]);
            try {
                $sent = Mail::to($payment->order->user->email)->send(new PaymentInstructions($payment, $qrImage));
                if (! $sent) {
                    $this->logger->log('failed', null, 'payments', $payment->id, null,
                        ['email' => 'payment_instructions', 'reason' => 'no_delivery_confirmation']);
                    return false;
                }
                $payment->update(['instruction_email_sent_at' => now()]);
                $this->logger->log('sent', null, 'payments', $payment->id, null, ['email' => 'payment_instructions']);
                return true;
            } catch (\Throwable $exception) {
                $this->logger->log('failed', null, 'payments', $payment->id, null,
                    ['email' => 'payment_instructions', 'exception_type' => $exception::class], 'Pengiriman email perlu ditinjau.');
                return false;
            }
        } finally {
            $lock->release();
        }
    }

    private function qrImage(Payment $payment): ?string
    {
        if (! in_array($payment->payment_type, ['qris', 'gopay'], true) || ! $payment->qr_url) return null;

        $url = $payment->qr_url;
        $expectedHost = config('midtrans.is_production') ? 'api.midtrans.com' : 'api.sandbox.midtrans.com';
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || parse_url($url, PHP_URL_HOST) !== $expectedHost) return null;

        try {
            $response = Http::timeout(4)->connectTimeout(2)->withOptions(['allow_redirects' => false])->get($url);
            $image = $response->body();
            if ($response->successful()
                && str_starts_with(strtolower((string) $response->header('Content-Type')), 'image/png')
                && strlen($image) <= 250000
                && str_starts_with($image, "\x89PNG\r\n\x1a\n")) return $image;
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
