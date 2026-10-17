@extends('emails.layout')
@section('title', 'Instruksi pembayaran '.$payment->order->order_number)
@section('preheader', 'Selesaikan pembayaran untuk '.$payment->order->course_title.' sebelum batas waktu.')
@section('heading', 'Selesaikan pembayaran')
@section('content')
@php
    $order = $payment->order;
    $timezone = $order->user->timezone ?: 'UTC';
    $channel = match ($payment->channel ?: $payment->payment_type) {
        'bca', 'bni', 'bri', 'cimb' => 'Virtual Account '.strtoupper($payment->channel),
        'permata' => 'Virtual Account Permata',
        'qris' => 'QRIS',
        'gopay' => 'GoPay',
        'shopeepay' => 'ShopeePay',
        default => 'Pembayaran',
    };
@endphp
<p style="margin:0 0 18px;">Halo, {{ $order->user->name }}. Pesanan Anda sudah dibuat. Gunakan rincian berikut untuk menyelesaikan pembayaran.</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 24px;border-top:1px solid #e6e9f2;">
    <tr><td style="padding:12px 0;color:#6b7690;width:38%;border-bottom:1px solid #e6e9f2;">Course</td><td style="padding:12px 0;font-weight:700;border-bottom:1px solid #e6e9f2;">{{ $order->course_title }}</td></tr>
    <tr><td style="padding:12px 0;color:#6b7690;border-bottom:1px solid #e6e9f2;">Nomor pesanan</td><td style="padding:12px 0;border-bottom:1px solid #e6e9f2;">{{ $order->order_number }}</td></tr>
    <tr><td style="padding:12px 0;color:#6b7690;border-bottom:1px solid #e6e9f2;">Metode</td><td style="padding:12px 0;border-bottom:1px solid #e6e9f2;">{{ $channel }}</td></tr>
    <tr><td style="padding:12px 0;color:#6b7690;border-bottom:1px solid #e6e9f2;">Total bayar</td><td style="padding:12px 0;font-size:19px;font-weight:700;color:#1b2437;border-bottom:1px solid #e6e9f2;">Rp {{ number_format($order->amount, 0, ',', '.') }}</td></tr>
    <tr><td style="padding:12px 0;color:#6b7690;border-bottom:1px solid #e6e9f2;">Batas bayar</td><td style="padding:12px 0;border-bottom:1px solid #e6e9f2;">{{ $payment->expires_at?->copy()->timezone($timezone)->format('d M Y H:i') }} ({{ $timezone }})</td></tr>
</table>
@if($payment->va_number)
    <p style="margin:0 0 8px;font-weight:700;">Nomor Virtual Account</p>
    <p style="margin:0 0 10px;padding:16px;background:#eef0fe;border-left:3px solid #6777ef;font-size:22px;line-height:1.4;font-weight:700;letter-spacing:1px;word-break:break-all;">{{ $payment->va_number }}</p>
    <p style="margin:0 0 24px;color:#4f5d75;">Bayar ke nomor tersebut melalui menu Virtual Account bank yang dipilih. Pastikan nominalnya sesuai.</p>
@elseif($payment->qr_url)
    <p style="margin:0 0 12px;font-weight:700;">Kode QR pembayaran</p>
    <p style="margin:0 0 16px;text-align:center;"><img src="{{ $qrImage ? $message->embedData($qrImage, 'payment-qr.png', 'image/png') : $payment->qr_url }}" alt="Kode QR {{ $channel }} untuk pesanan {{ $order->order_number }}" width="240" height="240" style="display:block;width:240px;height:240px;margin:0 auto;border:1px solid #e6e9f2;"></p>
    <p style="margin:0 0 24px;color:#4f5d75;">Pindai QR dengan aplikasi pembayaran yang mendukung QRIS. Jika gambar tidak tampil di email, buka halaman pembayaran melalui tombol di bawah.</p>
@elseif($payment->deeplink_url)
    <p style="margin:0 0 24px;color:#4f5d75;">Buka halaman pembayaran untuk melanjutkan melalui {{ $channel }} di perangkat Anda.</p>
@endif
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;"><tr><td style="background:#4e5fd6;"><a href="{{ route('orders.pay', $order) }}" style="display:inline-block;padding:13px 22px;color:#ffffff;text-decoration:none;font-weight:700;">Lihat detail pembayaran</a></td></tr></table>
<p style="margin:0;color:#6b7690;font-size:13px;">Tautan tombol: <a href="{{ route('orders.pay', $order) }}" style="color:#4e5fd6;word-break:break-all;">{{ route('orders.pay', $order) }}</a></p>
<p style="margin:22px 0 0;color:#4f5d75;font-size:13px;">Instruksi ini tidak berlaku jika pesanan dibatalkan, kedaluwarsa, atau metode pembayaran diganti. Status pembayaran akan dikonfirmasi oleh sistem.</p>
@endsection
