@extends('layouts.public')

@section('title', 'Terjadi kendala')

@section('content')
    <x-error-page code="500" title="Terjadi kendala" message="Permintaan belum dapat diproses. Silakan coba kembali beberapa saat lagi." />
@endsection
