@extends('layouts.error')
@section('title', 'Tunggu sebentar sebelum mencoba lagi')
@section('content')
    <x-error-page code="429" title="Tunggu sebentar sebelum mencoba lagi" message="Terlalu banyak permintaan dalam waktu singkat. Beri jeda sebelum membuka kembali halaman ini." icon="clock-3" primary-label="Kembali ke beranda" primary-path="/" hint="Hindari memuat ulang halaman berulang kali selama menunggu." :retry="true" />
@endsection
