@extends('layouts.error')
@section('title', 'Silakan masuk terlebih dahulu')
@section('content')
    <x-error-page code="401" title="Silakan masuk terlebih dahulu" message="Halaman ini memerlukan akun. Masuk untuk melanjutkan ke area belajar Anda." icon="users" primary-label="Masuk ke akun" primary-path="/login" hint="Gunakan akun yang terdaftar di Namikulo Course." :retry="false" />
@endsection
