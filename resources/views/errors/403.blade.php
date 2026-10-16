@extends('layouts.public')

@section('title', 'Akses ditolak')

@section('content')
    <x-error-page code="403" title="Akses ditolak" message="Akun Anda tidak memiliki izin untuk membuka halaman ini." />
@endsection
