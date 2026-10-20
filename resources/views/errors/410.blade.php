@extends('layouts.error')
@section('title', 'Halaman ini sudah tidak tersedia')
@section('content')
    <x-error-page code="410" title="Halaman ini sudah tidak tersedia" message="Konten pada alamat ini telah dihapus atau tidak lagi dapat diakses." icon="book-open" primary-label="Jelajahi katalog" primary-path="/courses" hint="Anda tetap dapat menjelajahi course lain yang tersedia." :retry="false" secondary-label="Kembali ke beranda" secondary-path="/" />
@endsection
