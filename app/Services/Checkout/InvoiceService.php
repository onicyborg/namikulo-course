<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderInvoice;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceService
{
    public function forPaidOrder(Order $order): OrderInvoice
    {
        return DB::transaction(function () use ($order): OrderInvoice {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== OrderStatus::Paid) {
                throw new RuntimeException('Invoice hanya tersedia untuk pesanan lunas.');
            }

            $existing = $locked->invoice()->first();
            if ($existing) return $existing;
            $payment = $locked->payments()->whereIn('status', ['settlement', 'capture'])
                ->orderByDesc('paid_at')->first();
            $paidAt = $locked->paid_at ?: $payment?->paid_at;
            if (! $paidAt) {
                throw new RuntimeException('Tanggal pembayaran belum tersedia.');
            }
            $timezone = $locked->user->timezone ?: 'UTC';
            if (! in_array($timezone, timezone_identifiers_list(), true)) $timezone = 'UTC';
            $channel = $payment?->channel ?: $payment?->payment_type;
            $method = match ($channel) {
                'bca', 'bni', 'bri', 'cimb' => 'Virtual Account '.strtoupper($channel),
                'permata' => 'Virtual Account Permata',
                'qris' => 'QRIS',
                'gopay' => 'GoPay',
                'shopeepay' => 'ShopeePay',
                default => null,
            };

            return $locked->invoice()->create([
                'invoice_number' => 'INV-'.$locked->order_number,
                'order_number' => $locked->order_number,
                'seller_name' => trim((string) config('invoice.seller_name')) ?: config('app.name'),
                'seller_address' => trim((string) config('invoice.seller_address')) ?: null,
                'seller_email' => trim((string) config('invoice.seller_email')) ?: null,
                'seller_tax_id' => trim((string) config('invoice.seller_tax_id')) ?: null,
                'buyer_name' => $locked->user->name,
                'buyer_email' => $locked->user->email,
                'course_title' => $locked->course_title,
                'payment_method' => $method,
                'amount' => $locked->amount,
                'display_timezone' => $timezone,
                'issued_at' => $paidAt,
            ]);
        });
    }
}
