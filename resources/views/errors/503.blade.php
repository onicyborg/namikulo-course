@extends('layouts.error')
@section('title', 'Layanan sementara tidak tersedia')
@section('content')
    <x-error-page code="503" title="Layanan sementara tidak tersedia" message="Layanan sedang dalam pemeliharaan atau belum dapat menerima permintaan. Silakan kembali beberapa saat lagi." icon="activity" primary-label="Kembali ke beranda" primary-path="/" hint="Halaman ini tidak perlu dimuat ulang terus-menerus." :retry="true" />
@endsection
