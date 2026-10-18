@extends('emails.layout')
@section('title', 'Pembayaran berhasil untuk '.$order->order_number)
@section('preheader', 'Pembayaran '.$order->order_number.' telah dikonfirmasi. Akses course Anda aktif.')
@section('eyebrow', 'PEMBAYARAN TERKONFIRMASI')
@section('heading', 'Pembayaran berhasil')
@section('footer', 'Simpan email ini sebagai catatan pembayaran Anda. Status dan invoice juga tersedia di halaman transaksi.')
@section('content')
@php($timezone = $order->user->timezone ?: 'UTC')
<p style="margin:0 0 22px;">Halo, {{ $order->user->name }}. Pembayaran Anda telah dikonfirmasi. Akses course sudah aktif.</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;margin:0 0 20px;border-top:1px solid #e2e8f0;">
    <x-email.detail-row label="Course">{{ $order->course_title }}</x-email.detail-row>
    <x-email.detail-row label="Nomor pesanan">{{ $order->order_number }}</x-email.detail-row>
    <x-email.detail-row label="Jumlah dibayar" :emphasis="true">Rp {{ number_format($order->amount, 0, ',', '.') }}</x-email.detail-row>
    @if($order->paid_at)<x-email.detail-row label="Waktu bayar">{{ $order->paid_at->copy()->timezone($timezone)->locale('id')->translatedFormat('d F Y H:i') }} ({{ $timezone }})</x-email.detail-row>@endif
    @if($order->enrollment?->expires_at)<x-email.detail-row label="Akses hingga">{{ $order->enrollment->expires_at->copy()->timezone($timezone)->locale('id')->translatedFormat('d F Y H:i') }} ({{ $timezone }})</x-email.detail-row>@endif
</table>

<x-email.button :url="route('member.dashboard')" label="Buka course saya" />
<p style="margin:0 0 20px;"><a href="{{ route('member.orders.invoice', $order) }}" style="color:#0f766e;font-size:13px;font-weight:700;text-decoration:underline;">Unduh invoice PDF</a></p>
<x-email.action-link :url="route('member.dashboard')" />
@endsection
