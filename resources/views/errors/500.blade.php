@extends('layouts.error')
@section('title', 'Ada kendala pada sistem')
@section('content')
    <x-error-page code="500" title="Ada kendala pada sistem" message="Permintaan Anda belum dapat kami proses. Silakan coba kembali beberapa saat lagi." icon="alert-triangle" primary-label="Kembali ke beranda" primary-path="/" hint="Jika kendala muncul saat membayar, periksa status pesanan sebelum mencoba transaksi baru." :retry="true" />
@endsection
