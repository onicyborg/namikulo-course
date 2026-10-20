@extends('layouts.error')
@section('title', 'Halaman belum dapat dibuka')
@section('content')
    <x-error-page :code="isset($exception) && $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $exception->getStatusCode() : 400" title="Halaman belum dapat dibuka" message="Permintaan ini tidak dapat diproses. Periksa alamat atau kembali melalui navigasi aplikasi." icon="book-open" />
@endsection
