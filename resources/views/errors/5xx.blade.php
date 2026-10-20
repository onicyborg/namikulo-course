@extends('layouts.error')
@section('title', 'Layanan mengalami kendala')
@section('content')
    <x-error-page :code="isset($exception) && $exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $exception->getStatusCode() : 500" title="Layanan mengalami kendala" message="Permintaan Anda belum dapat diproses. Silakan kembali beberapa saat lagi." icon="activity" />
@endsection
