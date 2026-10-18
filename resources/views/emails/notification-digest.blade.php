@extends('emails.layout')
@section('title', 'Ringkasan notifikasi')
@section('preheader', $total.' notifikasi belum dibaca di Namikulo Course')
@section('eyebrow', 'REKAP HARIAN')
@section('heading', 'Ringkasan notifikasi Anda')
@section('footer', 'Rekap ini memuat notifikasi yang belum dibaca sebelum pukul 07.00 WIB. Buka aplikasi untuk melihat status terbaru.')
@section('content')
@php($displayDate = \Illuminate\Support\Carbon::parse($date, 'Asia/Jakarta')->locale('id')->translatedFormat('d F Y'))
<p style="margin:0 0 22px;">Halo, {{ $recipient->name }}. Ada <strong style="color:#0f172a;">{{ $total }} notifikasi</strong> yang belum Anda baca hingga pukul 07.00 WIB, {{ $displayDate }}.</p>

<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;border-top:1px solid #e2e8f0;">
    @foreach($items as $item)
        <tr><td style="padding:15px 0;border-bottom:1px solid #e2e8f0;">
            <strong style="display:block;margin:0 0 4px;color:#0f172a;font-size:13px;line-height:1.5;">{{ $item->title }}</strong>
            <span style="color:#475569;font-size:13px;line-height:1.6;">{{ $item->summary }}</span>
        </td></tr>
    @endforeach
</table>
@if($total > $items->count())<p style="margin:16px 0 0;font-size:13px;">Dan {{ $total - $items->count() }} notifikasi lainnya.</p>@endif

<x-email.button :url="$recipient->isAdmin() ? route('admin.notifications.index') : route('member.notifications.index')" label="Lihat semua notifikasi" />
<x-email.action-link :url="$recipient->isAdmin() ? route('admin.notifications.index') : route('member.notifications.index')" />
@endsection
