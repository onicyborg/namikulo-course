@extends('emails.layout')
@section('title', 'Pembayaran berhasil untuk '.$order->order_number)
@section('preheader', 'Pembayaran '.$order->order_number.' telah dikonfirmasi. Akses course Anda aktif.')
@section('heading', 'Pembayaran berhasil')
@section('content')
@php $timezone = $order->user->timezone ?: 'UTC'; @endphp
<p style="margin:0 0 18px;">Halo, {{ $order->user->name }}. Pembayaran Anda telah dikonfirmasi dan akses course sudah aktif.</p>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin:0 0 24px;border-top:1px solid #e6e9f2;">
    <tr><td style="padding:12px 0;color:#6b7690;width:38%;border-bottom:1px solid #e6e9f2;">Course</td><td style="padding:12px 0;font-weight:700;border-bottom:1px solid #e6e9f2;">{{ $order->course_title }}</td></tr>
    <tr><td style="padding:12px 0;color:#6b7690;border-bottom:1px solid #e6e9f2;">Nomor pesanan</td><td style="padding:12px 0;border-bottom:1px solid #e6e9f2;">{{ $order->order_number }}</td></tr>
    <tr><td style="padding:12px 0;color:#6b7690;border-bottom:1px solid #e6e9f2;">Dibayar</td><td style="padding:12px 0;border-bottom:1px solid #e6e9f2;">Rp {{ number_format($order->amount, 0, ',', '.') }}</td></tr>
    @if($order->paid_at)<tr><td style="padding:12px 0;color:#6b7690;border-bottom:1px solid #e6e9f2;">Waktu bayar</td><td style="padding:12px 0;border-bottom:1px solid #e6e9f2;">{{ $order->paid_at->copy()->timezone($timezone)->format('d M Y H:i') }} ({{ $timezone }})</td></tr>@endif
    @if($order->enrollment?->expires_at)<tr><td style="padding:12px 0;color:#6b7690;border-bottom:1px solid #e6e9f2;">Akses hingga</td><td style="padding:12px 0;border-bottom:1px solid #e6e9f2;">{{ $order->enrollment->expires_at->copy()->timezone($timezone)->format('d M Y H:i') }} ({{ $timezone }})</td></tr>@endif
</table>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;"><tr><td style="background:#4e5fd6;"><a href="{{ route('member.dashboard') }}" style="display:inline-block;padding:13px 22px;color:#ffffff;text-decoration:none;font-weight:700;">Buka course saya</a></td></tr></table>
<p style="margin:0 0 14px;"><a href="{{ route('member.orders.invoice', $order) }}" style="color:#4e5fd6;font-weight:700;">Unduh invoice PDF</a></p>
<p style="margin:0;color:#6b7690;font-size:13px;">Tautan tombol: <a href="{{ route('member.dashboard') }}" style="color:#4e5fd6;word-break:break-all;">{{ route('member.dashboard') }}</a></p>
@endsection
