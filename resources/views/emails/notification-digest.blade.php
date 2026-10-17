@extends('emails.layout')
@section('title', 'Ringkasan notifikasi')
@section('preheader', $total.' notifikasi belum dibaca di Namikulo Course')
@section('heading', 'Ringkasan notifikasi Anda')
@section('footer', 'Email otomatis dari Namikulo Course. Buka aplikasi untuk membaca notifikasi dan memeriksa status terbaru.')
@section('content')
<p style="margin:0 0 18px;">Halo, {{ $recipient->name }}. Ada <strong>{{ $total }}</strong> notifikasi yang belum Anda baca hingga pukul 07.00 WIB, {{ $date }}.</p>
@foreach($items as $item)
<div style="padding:14px 0;border-top:1px solid #e6e9f2;">
    <strong style="color:#1b2437;">{{ $item->title }}</strong><br>
    <span style="color:#56627a;">{{ $item->summary }}</span>
</div>
@endforeach
@if($total > $items->count())<p>Dan {{ $total - $items->count() }} notifikasi lainnya.</p>@endif
<p style="margin:24px 0 0;"><a href="{{ $recipient->isAdmin() ? route('admin.notifications.index') : route('member.notifications.index') }}" style="display:inline-block;background:#6777ef;color:#ffffff;text-decoration:none;padding:11px 18px;border-radius:4px;">Lihat semua notifikasi</a></p>
<p style="margin:16px 0 0;color:#6b7690;font-size:13px;">Email instruksi dan konfirmasi pembayaran tetap dikirim terpisah saat tersedia.</p>
@endsection
