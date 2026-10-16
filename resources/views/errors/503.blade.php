@extends('layouts.public')

@section('title', 'Layanan sementara tidak tersedia')

@section('content')
    <x-error-page code="503" title="Layanan sementara tidak tersedia" message="Kami sedang melakukan pemeliharaan. Silakan kembali beberapa saat lagi." />
@endsection
