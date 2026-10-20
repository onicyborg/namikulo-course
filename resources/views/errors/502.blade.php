@extends('layouts.error')
@section('title', 'Layanan belum dapat dihubungi')
@section('content')
    <x-error-page code="502" title="Layanan belum dapat dihubungi" message="Salah satu layanan belum memberikan respons yang sesuai. Silakan coba kembali beberapa saat lagi." icon="activity" primary-label="Kembali ke beranda" primary-path="/" hint="Jika Anda sedang membayar, periksa kembali status pesanan sebelum mengulang transaksi." :retry="true" />
@endsection
