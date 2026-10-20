@extends('layouts.error')
@section('title', 'Halaman tidak ditemukan')
@section('content')
    <x-error-page code="404" title="Halaman tidak ditemukan" message="Alamat ini mungkin sudah berubah atau halaman yang Anda cari tidak lagi tersedia." icon="book-open" primary-label="Kembali ke beranda" primary-path="/" hint="Periksa kembali alamatnya, atau temukan course melalui katalog." :retry="false" />
@endsection
