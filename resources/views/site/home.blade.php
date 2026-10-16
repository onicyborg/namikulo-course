@extends('layouts.public')

@section('title', 'Beranda')
@section('meta_description', 'Platform course video berbayar dengan akses pembelajaran terkelola.')

@section('content')
<section class="foundation-hero">
    <div class="container">
        <div class="foundation-copy">
            <p class="auth-eyebrow">Platform course</p>
            <h1>Fondasi pembelajaran sedang disiapkan.</h1>
            <p>Fitur katalog dan pembelian course akan hadir pada phase pengembangan berikutnya. Anda sudah dapat membuat akun atau masuk ke area yang sesuai.</p>
            <div class="foundation-actions">
                @auth
                    <a class="btn btn-primary btn-lg" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.dashboard') }}">Buka Dashboard</a>
                @else
                    <a class="btn btn-primary btn-lg" href="{{ route('register') }}">Daftar sebagai Member</a>
                    <a class="btn btn-outline-primary btn-lg" href="{{ route('login') }}">Masuk</a>
                @endauth
            </div>
        </div>
    </div>
</section>
@endsection
