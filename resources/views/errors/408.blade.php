@extends('layouts.error')
@section('title', 'Permintaan memerlukan waktu terlalu lama')
@section('content')
    <x-error-page code="408" title="Permintaan memerlukan waktu terlalu lama" message="Koneksi terputus sebelum permintaan selesai. Periksa koneksi internet Anda sebelum mencoba kembali." icon="clock-3" primary-label="Kembali ke beranda" primary-path="/" hint="Untuk transaksi, periksa status pesanan sebelum mengirim pembayaran kembali." :retry="true" />
@endsection
