@extends('layouts.error')
@section('title', 'Permintaan tidak dapat diteruskan')
@section('content')
    <x-error-page code="405" title="Permintaan tidak dapat diteruskan" message="Halaman ini tidak mendukung cara akses yang digunakan. Buka kembali halaman melalui navigasi aplikasi." icon="rotate-ccw" primary-label="Kembali ke beranda" primary-path="/" hint="Jika Anda baru mengirim formulir, buka kembali formulirnya sebelum mencoba lagi." :retry="false" />
@endsection
