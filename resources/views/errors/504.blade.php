@extends('layouts.error')
@section('title', 'Respons layanan terlalu lama')
@section('content')
    <x-error-page code="504" title="Respons layanan terlalu lama" message="Kami belum menerima respons dari layanan yang dibutuhkan. Silakan coba kembali beberapa saat lagi." icon="clock-3" primary-label="Kembali ke beranda" primary-path="/" hint="Untuk pembayaran, periksa status pesanan sebelum membuat transaksi baru." :retry="true" />
@endsection
