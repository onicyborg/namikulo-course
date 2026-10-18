@extends('emails.layout')
@section('title', 'Atur ulang password')
@section('preheader', 'Tautan untuk mengatur ulang password akun Namikulo Course Anda.')
@section('eyebrow', 'KEAMANAN AKUN')
@section('heading', 'Atur ulang password')
@section('footer', 'Jika Anda tidak meminta perubahan password, abaikan email ini. Password akun Anda tetap sama.')
@section('content')
<p style="margin:0 0 18px;">Halo, {{ $recipient->name }}. Kami menerima permintaan untuk mengatur ulang password akun Anda.</p>
<p style="margin:0 0 18px;">Tautan ini berlaku selama <strong style="color:#0f172a;">{{ $expiresMinutes }} menit</strong>. Setelah itu, Anda dapat meminta tautan baru dari halaman lupa password.</p>

<x-email.button :url="$resetUrl" label="Atur ulang password" />
<x-email.action-link :url="$resetUrl" />
@endsection
