@extends('layouts.error')
@section('title', 'Halaman ini belum dapat Anda akses')
@section('content')
    <x-error-page code="403" title="Halaman ini belum dapat Anda akses" message="Akun Anda tidak memiliki izin untuk membuka halaman ini. Jika Anda memiliki akun lain yang sesuai, silakan masuk dengan akun tersebut." icon="eye-off" primary-label="Kembali ke beranda" primary-path="/" hint="Untuk materi course, periksa kembali akses Anda melalui dashboard." :retry="false" />
@endsection
