<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentNotification;
use App\Services\Checkout\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments): JsonResponse
    {
        if (strlen($request->getContent()) > 65536) return response()->json(['message' => 'Payload terlalu besar.'], 413);
        $payload = $request->json()->all();
        $orderId = is_string($payload['order_id'] ?? null) ? $payload['order_id'] : '';
        $code = is_string($payload['status_code'] ?? null) ? $payload['status_code'] : '';
        $amount = is_string($payload['gross_amount'] ?? null) ? $payload['gross_amount'] : '';
        $signature = is_string($payload['signature_key'] ?? null) ? $payload['signature_key'] : '';
        $secret = (string) config('midtrans.server_key');
        $valid = $secret !== '' && $orderId !== '' && $code !== '' && $amount !== ''
            && hash_equals(hash('sha512', $orderId.$code.$amount.$secret), $signature);

        $notification = PaymentNotification::query()->create([
            'midtrans_order_id' => mb_substr($orderId, 0, 50),
            'transaction_status' => mb_substr(is_string($payload['transaction_status'] ?? null) ? $payload['transaction_status'] : '', 0, 30),
            'signature_valid' => $valid,
            'payload' => $payload,
            'process_result' => $valid ? 'received' : 'invalid_signature',
        ]);
        if (! $valid) return response()->json(['message' => 'Tanda tangan tidak valid.'], 403);

        $merchantId = (string) config('midtrans.merchant_id');
        $testPrefix = 'payment_notif_test_'.$merchantId.'_';
        if ($merchantId !== ''
            && ($payload['merchant_id'] ?? null) === $merchantId
            && str_starts_with($orderId, $testPrefix)
            && strlen($orderId) > strlen($testPrefix)) {
            $notification->update(['process_result' => 'dashboard_test', 'processed_at' => now()]);

            return response()->json(['message' => 'Notifikasi uji diterima.']);
        }

        $payment = Payment::query()->where('midtrans_order_id', $orderId)->first();
        if (! $payment) {
            $notification->update(['process_result' => 'payment_not_found']);
            return response()->json(['message' => 'Pembayaran tidak ditemukan.'], 404);
        }
        $notification->update(['payment_id' => $payment->id]);
        try {
            $result = $payments->sync($payment);
            $notification->update(['process_result' => $result, 'processed_at' => now()]);
            return response()->json(['message' => 'Diterima.']);
        } catch (\Throwable) {
            $notification->update(['process_result' => 'retry_required']);
            return response()->json(['message' => 'Verifikasi belum berhasil.'], 503);
        }
    }
}
