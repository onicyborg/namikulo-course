@extends('layouts.admin')
@section('title', 'Dashboard Admin')

@section('content')
    <x-flash-message />
    <header class="nd-page-header">
        <div>
            <nav class="nd-breadcrumb" aria-label="Breadcrumb"><span>Namikulo</span><x-dashboard.icon name="chevron-right" /><span>Area Admin</span></nav>
            <h1>Dashboard</h1>
            <p>Pantau aktivitas belajar, transaksi, dan kesiapan materi.</p>
        </div>
        <a class="nd-button nd-button-primary" href="{{ route('admin.courses.index') }}"><x-dashboard.icon name="book-open" />Kelola course<x-dashboard.icon name="arrow-up-right" /></a>
    </header>

    @include('admin.dashboard.metrics')
    <div class="nd-bottom-grid">
        @include('admin.dashboard.orders')
        @include('admin.dashboard.videos')
    </div>
@endsection
