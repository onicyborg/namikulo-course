@extends('layouts.error')
@section('title', 'Sesi halaman telah berakhir')
@section('content')
    <x-error-page code="419" title="Sesi halaman telah berakhir" message="Sesi formulir tidak lagi berlaku. Masuk kembali, lalu buka ulang formulir yang ingin Anda kirim." icon="clock-3" primary-label="Masuk kembali" primary-path="/login" hint="Isian yang belum dikirim mungkin perlu diisi kembali." :retry="false" />
@endsection
