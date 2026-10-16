@extends('layouts.public')

@section('title', 'Halaman tidak ditemukan')

@section('content')
    <x-error-page code="404" title="Halaman tidak ditemukan" message="Alamat yang Anda buka tidak tersedia atau sudah berubah." />
@endsection
