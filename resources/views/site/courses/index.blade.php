@extends('layouts.public')

@section('title', 'Katalog course')
@section('meta_description', 'Cari course berdasarkan topik, pilih kategori, dan bandingkan kurikulum serta harga sebelum belajar.')

@section('content')
<div class="container site-page np-catalog nm-catalog">
    <header class="nm-catalog-heading">
        <div><p class="nm-eyebrow">KATALOG / NAMIKULO COURSE</p><h1>Rasa ingin tahu.<br><span>Temukan arahnya.</span></h1><p>Pilih topik yang ingin Anda kuasai. Kenali kurikulum dan pengajarnya sebelum memulai.</p></div>
        <div class="nm-catalog-note"><x-dashboard.icon name="book-open" /><span>Belajar lewat video.<br><strong>Pahami selangkah demi selangkah.</strong></span></div>
    </header>
    <form class="site-filters" method="GET" action="{{ route('site.courses.index') }}" role="search">
        <div class="form-group"><label for="catalog-q">Cari course</label><div class="np-search-field"><svg class="nd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg><input class="form-control" id="catalog-q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Topik atau judul course"></div></div>
        <div class="form-group"><label for="catalog-category">Kategori</label><select class="form-control" id="catalog-category" name="category"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(($filters['category'] ?? '') === $category->slug)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="form-group"><label for="catalog-sort">Urutkan</label><select class="form-control" id="catalog-sort" name="sort"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Terbaru</option><option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Harga terendah</option><option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Harga tertinggi</option></select></div>
        <button class="btn btn-primary" type="submit">Cari course<x-dashboard.icon name="arrow-right" /></button>
    </form>
    <div class="site-results-heading"><h2><strong>{{ number_format($courses->total(), 0, ',', '.') }}</strong> course ditemukan</h2>@if(request()->hasAny(['q', 'category', 'sort']))<a href="{{ route('site.courses.index') }}">Hapus filter</a>@endif</div>
    @if($courses->isEmpty())
        <div class="site-empty"><svg class="nd-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg><h3>Belum ada course yang cocok</h3><p>Coba kata kunci lain atau lihat semua kategori.</p><a class="btn btn-outline-primary" href="{{ route('site.courses.index') }}">Lihat semua course</a></div>
    @else
        <div class="nm-course-grid {{ $courses->count() === 1 ? 'nm-course-grid--single' : '' }}">@foreach($courses as $course)@include('site.partials.landing-course')@endforeach</div>
        <div class="site-pagination">{{ $courses->links('pagination::bootstrap-4') }}</div>
    @endif
</div>
@endsection
