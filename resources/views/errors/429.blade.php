@extends('layouts.public')

@section('title', 'Terlalu banyak permintaan')

@section('content')
    <x-error-page code="429" title="Terlalu banyak permintaan" message="Tunggu sebentar sebelum mencoba kembali." />
@endsection
