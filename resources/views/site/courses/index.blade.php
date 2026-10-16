@extends('layouts.public')

@section('title', 'Katalog course')
@section('meta_description', 'Cari course berdasarkan topik, pilih kategori, dan bandingkan kurikulum serta harga sebelum belajar.')

@section('content')
<div class="container site-page">
    <header class="site-page-heading"><p class="site-eyebrow">KATALOG</p><h1>Course untuk langkah Anda berikutnya</h1><p>Telusuri topik, lihat susunan materi, dan pilih yang sesuai tujuan belajar.</p></header>
    <form class="site-filters" method="GET" action="{{ route('site.courses.index') }}" role="search">
        <div class="form-group"><label for="catalog-q">Cari course</label><input class="form-control" id="catalog-q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Judul course"></div>
        <div class="form-group"><label for="catalog-category">Kategori</label><select class="form-control" id="catalog-category" name="category"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="form-group"><label for="catalog-sort">Urutkan</label><select class="form-control" id="catalog-sort" name="sort"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Terbaru</option><option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Harga terendah</option><option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Harga tertinggi</option></select></div>
        <button class="btn btn-primary" type="submit">Tampilkan</button>
    </form>
    <div class="site-results-heading"><h2>{{ number_format($courses->total(), 0, ',', '.') }} course ditemukan</h2>@if(request()->hasAny(['q', 'category', 'sort']))<a href="{{ route('site.courses.index') }}">Hapus filter</a>@endif</div>
    @if($courses->isEmpty())
        <div class="site-empty"><h3>Belum ada course yang cocok</h3><p>Coba kata kunci lain atau lihat semua kategori.</p><a class="btn btn-outline-primary" href="{{ route('site.courses.index') }}">Lihat semua course</a></div>
    @else
        <div class="site-course-grid">@foreach($courses as $course)<x-course-card :course="$course" :is-active="in_array($course->id, $activeCourseIds, true)" />@endforeach</div>
        <div class="site-pagination">{{ $courses->links('pagination::bootstrap-4') }}</div>
    @endif
</div>
@endsection
