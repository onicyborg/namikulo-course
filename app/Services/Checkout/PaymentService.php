<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Services\ActivityLogger;
use App\Services\EnrollmentService;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly MidtransClient $client,
        private readonly EnrollmentService $enrollments,
        private readonly PaymentEmailService $email,
        private readonly PaymentInstructionEmailService $instructionsEmail,
        private readonly ActivityLogger $logger,
        private readonly NotificationService $notifications,
    ) {}

    public function sync(Payment $payment): string
    {
        try {
            $response = $this->client->status($payment);
        } catch (MidtransTransactionMissing $exception) {
            if (in_array($payment->status, [PaymentStatus::Unknown, PaymentStatus::Initiating], true)
                && $payment->created_at->lt(now()->subMinutes(10))) {
                $firstMissing = $payment->raw_response['first_not_found_at'] ?? null;
                if ($firstMissing && Carbon::parse($firstMissing)->lt(now()->subMinutes(2))) {
                    $payment->update(['status' => PaymentStatus::Failure]);
                    return 'not_found';
                }
                if (! $firstMissing) {
                    $payment->update(['raw_response' => ['first_not_found_at' => now()->utc()->toIso8601String()]]);
                }
            }
            throw $exception;
        }

        return $this->applyVerified($payment, $response);
    }

    public function recordCharge(Payment $payment, array $response): void
    {
        $this->assertResponse($payment, $response);
        $status = $response['transaction_status'] ?? null;
        if ($status !== 'pending' || empty($response['transaction_id'])) {
            throw new RuntimeException('Status awal pembayaran belum dapat dipastikan.');
        }

        $actions = collect($response['actions'] ?? []);
        $qr = $actions->firstWhere('name', 'generate-qr-code')['url'] ?? null;
        $deeplink = $actions->firstWhere('name', 'deeplink-redirect')['url'] ?? null;
        $va = $response['va_numbers'][0]['va_number'] ?? $response['permata_va_number'] ?? null;
        if ($payment->payment_type === 'bank_transfer' && ! $va) {
            throw new RuntimeException('Nomor virtual account belum tersedia.');
        }
        if ($payment->payment_type === 'qris' && ! $qr) {
            throw new RuntimeException('Kode QR belum tersedia.');
        }
        if ($payment->payment_type === 'shopeepay' && ! $deeplink) {
            throw new RuntimeException('Tautan ShopeePay belum tersedia.');
        }
        if ($payment->payment_type === 'gopay' && ! $qr && ! $deeplink) {
            throw new RuntimeException('Instruksi GoPay belum tersedia.');
        }
        if ($qr && ! $this->isTrustedQrUrl($qr)) throw new RuntimeException('Alamat QR pembayaran tidak valid.');
        if ($deeplink && ! $this->isTrustedDeeplink($deeplink)) throw new RuntimeException('Alamat aplikasi pembayaran tidak valid.');

        DB::transaction(function () use ($payment, $response, $va, $qr, $deeplink): void {
            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($order->status === OrderStatus::Paid
                || ! in_array($locked->status, [PaymentStatus::Initiating, PaymentStatus::Unknown, PaymentStatus::Pending], true)) return;
            $locked->update([
                'status' => PaymentStatus::Pending,
                'transaction_id' => (string) $response['transaction_id'],
                'va_number' => $va,
                'qr_url' => $qr,
                'deeplink_url' => $deeplink,
                'raw_response' => $this->safeResponse($response),
            ]);
            $this->notifications->paymentPending($order);
        });
    }

    public function applyVerified(Payment $payment, array $response): string
    {
        $this->assertResponse($payment, $response);
        $gatewayStatus = (string) ($response['transaction_status'] ?? '');
        if (! in_array($gatewayStatus, ['pending', 'settlement', 'capture', 'deny', 'failure', 'cancel', 'expire', 'refund', 'partial_refund', 'chargeback'], true)) {
            throw new RuntimeException('Status pembayaran dari gateway tidak dikenal.');
        }
        if ($gatewayStatus === 'capture' && ($response['fraud_status'] ?? null) !== 'accept') {
            throw new RuntimeException('Pembayaran masih menunggu pemeriksaan keamanan.');
        }
        if (in_array($gatewayStatus, ['settlement', 'capture'], true)
            && ((string) ($response['status_code'] ?? '') !== '200'
                || (isset($response['fraud_status']) && $response['fraud_status'] !== 'accept'))) {
            throw new RuntimeException('Konfirmasi pembayaran belum memenuhi syarat.');
        }

        $becamePaid = false;
        $result = DB::transaction(function () use ($payment, $response, $gatewayStatus, &$becamePaid): string {
            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $isRefund = in_array($gatewayStatus, ['refund', 'partial_refund', 'chargeback'], true);
            if ($isRefund && ! $payment->status?->isPaid() && $payment->status !== PaymentStatus::Refund) {
                $this->logger->log('payment_anomaly', null, 'payments', $payment->id, null,
                    ['reason' => 'refund_without_confirmed_payment']);
                return 'refund_review';
            }
            if ($order->status === OrderStatus::Paid && ! $isRefund) {
                return 'already_paid';
            }
            if ($order->status === OrderStatus::Refunded && ! $isRefund) return 'already_refunded';
            if ($gatewayStatus === 'pending' && in_array($payment->status,
                [PaymentStatus::Cancel, PaymentStatus::Expire, PaymentStatus::Deny, PaymentStatus::Failure, PaymentStatus::Refund], true)) {
                return 'stale_pending';
            }

            $mapped = match ($gatewayStatus) {
                'settlement' => PaymentStatus::Settlement,
                'capture' => PaymentStatus::Capture,
                'pending' => PaymentStatus::Pending,
                'deny' => PaymentStatus::Deny,
                'failure' => PaymentStatus::Failure,
                'cancel' => PaymentStatus::Cancel,
                'expire' => PaymentStatus::Expire,
                default => PaymentStatus::Refund,
            };
            $paidAt = in_array($gatewayStatus, ['settlement', 'capture'], true)
                ? $this->gatewayTime($response['settlement_time'] ?? $response['transaction_time'] ?? null) : null;
            $payment->update([
                'status' => $mapped,
                'transaction_id' => $response['transaction_id'] ?? $payment->transaction_id,
                'fraud_status' => $response['fraud_status'] ?? null,
                'va_number' => $payment->va_number ?: ($response['va_numbers'][0]['va_number'] ?? $response['permata_va_number'] ?? null),
                'qr_url' => $payment->qr_url ?: $this->trustedAction($response, 'generate-qr-code'),
                'deeplink_url' => $payment->deeplink_url ?: $this->trustedAction($response, 'deeplink-redirect'),
                'paid_at' => $paidAt,
                'raw_response' => $this->safeResponse($response),
            ]);

            if ($mapped->isPaid()) {
                $order->update(['status' => OrderStatus::Paid, 'paid_at' => $paidAt]);
                $this->enrollments->createFromOrder($order);
                $this->notifications->paymentPaid($order);
                $becamePaid = true;
            } elseif ($mapped === PaymentStatus::Refund) {
                $order->update(['status' => OrderStatus::Refunded]);
            } elseif ($order->status === OrderStatus::Pending && $mapped === PaymentStatus::Expire && $order->expires_at?->isPast()) {
                $order->update(['status' => OrderStatus::Expired]);
                $this->notifications->paymentExpired($order);
            } elseif ($order->status === OrderStatus::Pending && in_array($mapped, [PaymentStatus::Deny, PaymentStatus::Failure], true)) {
                $order->update(['status' => OrderStatus::Failed]);
            }
            if ($mapped === PaymentStatus::Pending && $order->status === OrderStatus::Pending) {
                $this->notifications->paymentPending($order);
            }
            $this->logger->log('updated', null, 'payments', $payment->id, null,
                ['status' => $mapped->value, 'order_id' => $order->id]);

            return $gatewayStatus;
        });

        if ($becamePaid) {
            try {
                $this->email->sendOnce($payment->order_id);
            } catch (\Throwable) {
                // Status pembayaran dan enrollment sudah tersimpan; email dapat ditinjau ulang oleh admin.
            }
        } elseif ($result === 'pending') {
            try {
                $this->instructionsEmail->sendOnce($payment->id);
            } catch (\Throwable) {
                // Rekonsiliasi pembayaran tetap berhasil meskipun email belum terkirim.
            }
        }

        return $result;
    }

    private function assertResponse(Payment $payment, array $response): void
    {
        $order = $payment->order;
        $rawAmount = $response['gross_amount'] ?? null;
        $amount = is_string($rawAmount) || is_int($rawAmount) ? (string) $rawAmount : '';
        $validAmount = preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)
            && (int) explode('.', $amount)[0] === $order->amount
            && (int) (explode('.', $amount)[1] ?? '0') === 0;
        if (($response['order_id'] ?? null) !== $payment->midtrans_order_id
            || ! $validAmount
            || (config('midtrans.merchant_id') && ($response['merchant_id'] ?? null) !== config('midtrans.merchant_id'))) {
            $this->logger->log('payment_anomaly', null, 'payments', $payment->id, null,
                ['order_id_match' => ($response['order_id'] ?? null) === $payment->midtrans_order_id,
                    'amount_match' => (bool) $validAmount]);
            throw new RuntimeException('Data pembayaran tidak cocok dengan pesanan.');
        }
    }

    private function gatewayTime(?string $time): Carbon
    {
        try {
            return $time ? Carbon::parse($time, 'Asia/Jakarta')->utc() : now()->utc();
        } catch (\Throwable) {
            return now()->utc();
        }
    }

    private function safeResponse(array $response): array
    {
        return array_intersect_key($response, array_flip([
            'order_id', 'transaction_id', 'transaction_status', 'transaction_time',
            'settlement_time', 'payment_type', 'gross_amount', 'fraud_status', 'status_code',
        ]));
    }

    private function isTrustedQrUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && in_array($host, ['api.midtrans.com', 'api.sandbox.midtrans.com', 'api.sandbox.veritrans.co.id'], true);
    }

    private function isTrustedDeeplink(string $url): bool
    {
        return in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'gojek'], true);
    }

    private function trustedAction(array $response, string $name): ?string
    {
        $url = collect($response['actions'] ?? [])->firstWhere('name', $name)['url'] ?? null;
        if (! is_string($url)) return null;
        return $name === 'generate-qr-code'
            ? ($this->isTrustedQrUrl($url) ? $url : null)
            : ($this->isTrustedDeeplink($url) ? $url : null);
    }
}
