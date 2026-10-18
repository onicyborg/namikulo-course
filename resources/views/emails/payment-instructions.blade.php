@extends('emails.layout')
@section('title', 'Instruksi pembayaran '.$payment->order->order_number)
@section('preheader', 'Selesaikan pembayaran untuk '.$payment->order->course_title.' sebelum batas waktu.')
@section('eyebrow', 'PESANAN MENUNGGU PEMBAYARAN')
@section('heading', 'Selesaikan pembayaran')
@section('footer', 'Instruksi ini tidak berlaku jika pesanan dibatalkan, kedaluwarsa, atau metode pembayaran diganti. Periksa status terbaru di halaman pembayaran.')
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
    $qrUrl = $payment->qr_url;
    $trustedQrUrl = is_string($qrUrl)
        && parse_url($qrUrl, PHP_URL_SCHEME) === 'https'
        && in_array(parse_url($qrUrl, PHP_URL_HOST), ['api.midtrans.com', 'api.sandbox.midtrans.com', 'api.sandbox.veritrans.co.id'], true);
@endphp
<p style="margin:0 0 22px;">Halo, {{ $order->user->name }}. Pesanan Anda sudah dibuat. Gunakan rincian berikut untuk menyelesaikan pembayaran.</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;margin:0 0 20px;border-top:1px solid #e2e8f0;">
    <x-email.detail-row label="Course">{{ $order->course_title }}</x-email.detail-row>
    <x-email.detail-row label="Nomor pesanan">{{ $order->order_number }}</x-email.detail-row>
    <x-email.detail-row label="Metode">{{ $channel }}</x-email.detail-row>
    <x-email.detail-row label="Total bayar" :emphasis="true">Rp {{ number_format($order->amount, 0, ',', '.') }}</x-email.detail-row>
    <x-email.detail-row label="Batas bayar">{{ $payment->expires_at?->copy()->timezone($timezone)->locale('id')->translatedFormat('d F Y H:i') }} ({{ $timezone }})</x-email.detail-row>
</table>

@if($payment->va_number)
    <p style="margin:0 0 8px;color:#0f172a;font-size:13px;font-weight:700;">Nomor Virtual Account</p>
    <p style="margin:0 0 12px;padding:16px;border:1px solid #cce8e2;border-radius:7px;background:#f0fdfa;color:#0f172a;font-size:21px;line-height:1.4;font-weight:700;letter-spacing:1px;word-break:break-all;">{{ $payment->va_number }}</p>
    <p style="margin:0 0 20px;font-size:13px;">Bayar melalui menu Virtual Account bank yang dipilih. Pastikan nominalnya sesuai.</p>
@elseif($trustedQrUrl)
    <p style="margin:0 0 12px;color:#0f172a;font-size:13px;font-weight:700;">Kode QR pembayaran</p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 14px;"><tr><td align="center" style="padding:20px;background:#ffffff;border:1px solid #e2e8f0;border-radius:7px;">
        <img src="{{ $qrImage ? $message->embedData($qrImage, 'payment-qr.png', 'image/png') : $qrUrl }}" alt="Kode QR {{ $channel }} untuk pesanan {{ $order->order_number }}" width="240" height="240" style="display:block;width:100%;max-width:240px;height:auto;margin:0 auto;border:0;">
    </td></tr></table>
    @if(! $qrImage)<p style="margin:0 0 12px;font-size:12px;"><a href="{{ $qrUrl }}" style="color:#0f766e;text-decoration:underline;word-break:break-all;">Buka gambar QR pembayaran</a></p>@endif
    <p style="margin:0 0 20px;font-size:13px;">Pindai kode QR dengan aplikasi pembayaran yang mendukung {{ $channel }}. Jika gambar tidak tampil, buka halaman pembayaran melalui tombol di bawah.</p>
@elseif($payment->deeplink_url)
    <p style="margin:0 0 20px;font-size:13px;">Buka halaman pembayaran untuk melanjutkan melalui {{ $channel }} di perangkat Anda.</p>
@endif

<x-email.button :url="route('orders.pay', $order)" label="Lihat detail pembayaran" />
<x-email.action-link :url="route('orders.pay', $order)" />
@endsection
