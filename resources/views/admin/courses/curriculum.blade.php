@extends('layouts.admin')
@section('title', 'Kurikulum · '.$course->title)
@push('styles')
<link rel="stylesheet" href="{{ url('css/lexical-editor.css') }}?v=1">
<link rel="stylesheet" href="{{ url('css/content-admin.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-courses.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-curriculum.css') }}">@endpush
@section('content')
<x-page-header title="Kurikulum course" parent="Course" :parentUrl="route('admin.courses.index')" />
<div class="nc-curriculum" data-curriculum>
    <x-flash-message />
    @if($errors->any())<div class="alert alert-danger" role="alert" data-validation-summary>Perubahan belum disimpan: {{ $errors->first() }}</div>@endif
    <div class="nc-grid">
        <div class="nc-main">
            <header class="nc-intro">
                <div><p class="nc-eyebrow">Kurikulum</p><h2>{{ $course->title }}</h2><p>Susun bab dan materi untuk perjalanan belajar yang terarah.</p></div>
                <div class="nc-actions"><a class="nc-button" href="{{ route('admin.courses.members', $course) }}"><x-dashboard.icon name="users" />Anggota</a><button class="nc-button nc-primary" type="button" data-toggle="modal" data-target="#section-create"><x-dashboard.icon name="plus" />Tambah bab</button></div>
            </header>
            <div class="nc-order-note"><span id="curriculum-order-help">Geser pegangan untuk mengurutkan, atau fokuskan pegangan lalu tekan ↑ / ↓. Urutan otomatis disimpan.</span><span role="status" aria-live="polite" data-order-status></span><a href="{{ route('admin.courses.curriculum', $course) }}" data-order-reload hidden>Muat ulang halaman</a></div>
            <div class="nc-chapters" data-curriculum-list data-reorder-url="{{ route('admin.courses.sections.reorder', $course) }}">
                @forelse($course->sections as $section)
                    @include('admin.courses._curriculum-chapter', ['chapterNumber' => $loop->iteration])
                @empty
                    <div class="nc-panel nc-empty"><x-dashboard.icon name="book-open" /><h3>Mulai dengan bab pertama</h3><p>Kelompokkan materi ke dalam bab agar mudah diikuti.</p><button class="nc-button nc-primary" type="button" data-toggle="modal" data-target="#section-create"><x-dashboard.icon name="plus" />Tambah bab</button></div>
                @endforelse
            </div>
        </div>
        <aside class="nc-settings" aria-label="Pengaturan course">
            @include('admin.courses._curriculum-settings')
        </aside>
    </div>
</div>
@endsection
@push('modals')
@include('admin.courses._curriculum-modals')
@endpush
@push('plugin-scripts')<script src="{{ url('vendor/lexical/namikulo-editor.js') }}?v=1" defer></script>@endpush
@push('scripts')
<script src="{{ url('js/pages/admin-content.js') }}"></script>
<script src="{{ url('js/pages/admin-curriculum.js') }}"></script>
@if($errors->any() && old('_modal'))<script>window.contentModalToOpen = @json(old('_modal'));</script>@endif
@endpush
