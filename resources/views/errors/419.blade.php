@extends('layouts.public')

@section('title', 'Sesi telah berakhir')

@section('content')
    <x-error-page code="419" title="Sesi telah berakhir" message="Muat ulang halaman lalu kirim kembali formulir Anda." />
@endsection
