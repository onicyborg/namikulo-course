<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransClient
{
    public function charge(Order $order, Payment $payment): array
    {
        $channel = $payment->channel;
        $type = $payment->payment_type;
        $body = [
            'payment_type' => $type,
            'transaction_details' => ['order_id' => $payment->midtrans_order_id, 'gross_amount' => $order->amount],
            'item_details' => [[
                'id' => $order->course_id,
                'price' => $order->amount,
                'quantity' => 1,
                'name' => mb_substr($order->course_title, 0, 50),
            ]],
            'customer_details' => [
                'first_name' => mb_substr($order->user->name, 0, 50),
                'email' => $order->user->email,
            ],
            'custom_expiry' => [
                'order_time' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s O'),
                'expiry_duration' => max(1, (int) floor(now()->diffInMinutes($order->expires_at, false))),
                'unit' => 'minute',
            ],
        ];
        if ($type === 'bank_transfer') $body['bank_transfer'] = ['bank' => $channel];
        if ($type === 'shopeepay') $body['shopeepay'] = ['callback_url' => route('orders.pay', $order)];

        return $this->request('post', '/v2/charge', $body);
    }

    public function status(Payment $payment): array
    {
        return $this->request('get', '/v2/'.rawurlencode($payment->midtrans_order_id).'/status');
    }

    public function cancel(Payment $payment): array
    {
        return $this->request('post', '/v2/'.rawurlencode($payment->midtrans_order_id).'/cancel');
    }

    public function expire(Payment $payment): array
    {
        return $this->request('post', '/v2/'.rawurlencode($payment->midtrans_order_id).'/expire');
    }

    private function request(string $method, string $path, ?array $body = null): array
    {
        $key = (string) config('midtrans.server_key');
        if ($key === '') throw new RuntimeException('Kredensial pembayaran belum dikonfigurasi.');

        $base = config('midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $request = Http::withBasicAuth($key, '')->asJson()->acceptJson()
            ->timeout((int) config('midtrans.timeout_seconds', 8))->connectTimeout(4);
        $response = $method === 'get'
            ? $request->get($base.$path)
            : $request->post($base.$path, $body ?? []);

        if ($method === 'get' && $response->status() === 404) {
            throw new MidtransTransactionMissing('Transaksi belum ditemukan pada gateway.');
        }

        if (! $response->successful() || ! is_array($response->json())) {
            $gatewayCode = $response->json('status_code');
            throw new MidtransRequestException($response->status(),
                is_scalar($gatewayCode) && preg_match('/^\d{3}$/', (string) $gatewayCode) ? (string) $gatewayCode : null);
        }

        return $response->json();
    }
}
