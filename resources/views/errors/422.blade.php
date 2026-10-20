@extends('layouts.error')
@section('title', 'Data belum dapat diproses')
@section('content')
    <x-error-page code="422" title="Data belum dapat diproses" message="Ada informasi yang belum lengkap atau belum sesuai. Kembali ke formulir dan periksa pesan pada setiap field." icon="pencil" primary-label="Kembali ke beranda" primary-path="/" hint="Gunakan tombol kembali pada browser untuk membuka formulir sebelumnya." :retry="false" />
@endsection
