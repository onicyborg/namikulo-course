@extends('layouts.public')

@section('title', 'Belajar dengan arah yang jelas')
@section('meta_description', 'Temukan course video dengan kurikulum terstruktur. Pelajari materi, ikuti progres Anda, dan berdiskusi bersama peserta lainnya di Namikulo Course.')

@push('styles')
    <link rel="stylesheet" href="{{ url('css/landing-minimal.css') }}?v=1">
@endpush

@section('content')
<section class="site-hero" aria-labelledby="hero-title">
    <div class="container site-hero-grid">
        <div class="np-hero-copy">
            <p class="site-eyebrow">RUANG UNTUK BELAJAR DAN BERTUMBUH</p>
            <h1 id="hero-title">Keahlian baru.<br><span>Langkah yang jelas.</span></h1>
            <p class="site-lead">Bangun pemahaman dari satu materi ke materi berikutnya. Belajar lewat video, ulangi bagian yang belum dipahami, dan ajukan pertanyaan di ruang diskusi.</p>
            <div class="site-hero-actions">
                <a class="btn btn-primary btn-lg" href="{{ route('site.courses.index') }}">Jelajahi course<x-dashboard.icon name="arrow-right" /></a>
                <a class="btn np-secondary btn-lg" href="#cara-belajar">Cara belajar</a>
            </div>
            <p class="np-hero-note"><x-dashboard.icon name="book-open" />Lihat kurikulum sebelum memilih course.</p>
        </div>
        <aside class="np-spotlight" aria-label="Sorotan course">
            @if($spotlight = $featured->first())
                <div class="np-spotlight-top"><span>COURSE PILIHAN</span><x-dashboard.icon name="arrow-up-right" /></div>
                <a href="{{ route('site.courses.show', $spotlight->slug) }}" class="np-spotlight-link">
                    <div class="site-course-image">
                        @if($spotlight->thumbnail_path)<img src="{{ url('storage/'.$spotlight->thumbnail_path) }}" alt="{{ $spotlight->title }}" width="640" height="360" fetchpriority="high">@else<div class="site-course-placeholder"><x-dashboard.icon name="book-open" /></div>@endif
                    </div>
                    <div class="np-spotlight-copy">
                        <span class="site-category">{{ $spotlight->category?->name ?? 'Course' }}</span>
                        <h2>{{ $spotlight->title }}</h2>
                        <p>{{ $spotlight->sections_count }} bab <span aria-hidden="true">·</span> {{ $spotlight->lessons_count }} video <span aria-hidden="true">·</span> Akses {{ $spotlight->access_months }} bulan</p>
                        <span class="np-spotlight-action">Lihat kurikulum<x-dashboard.icon name="arrow-right" /></span>
                    </div>
                </a>
            @else
                <div class="np-spotlight-copy np-spotlight-empty"><x-dashboard.icon name="book-open" /><h2>Mulai dari rasa ingin tahu.</h2><p>Lihat topik yang tersedia dan temukan course untuk tujuan belajar Anda.</p><a class="btn btn-primary" href="{{ route('site.courses.index') }}">Buka katalog</a></div>
            @endif
        </aside>
    </div>
</section>

<section class="site-stats" aria-label="Platform dalam angka"><div class="container site-stats-grid">
    <div><strong>{{ number_format($stats['courses'], 0, ',', '.') }}</strong><span>course tersedia</span></div>
    <div><strong>{{ number_format($stats['videos'], 0, ',', '.') }}</strong><span>video siap dipelajari</span></div>
    <div><strong>{{ number_format($stats['members'], 0, ',', '.') }}</strong><span>peserta dengan akses aktif</span></div>
</div></section>

<section class="site-section" aria-labelledby="featured-title"><div class="container">
    <div class="site-section-heading"><div><p class="site-eyebrow">PILIHAN BELAJAR</p><h2 id="featured-title">Temukan fokus berikutnya.</h2><p>Course unggulan dengan materi yang bisa Anda pelajari bertahap.</p></div><a class="site-text-link" href="{{ route('site.courses.index') }}">Semua course<x-dashboard.icon name="arrow-right" /></a></div>
    @if($featured->isEmpty())
        <div class="site-empty"><x-dashboard.icon name="book-open" /><h3>Belum ada course unggulan</h3><p>Course lainnya dapat Anda temukan di katalog.</p><a class="btn btn-outline-primary" href="{{ route('site.courses.index') }}">Buka katalog</a></div>
    @else
        <div class="site-course-grid">@foreach($featured as $course)<x-course-card :course="$course" :is-active="in_array($course->id, $activeCourseIds, true)" />@endforeach</div>
    @endif
</div></section>

<section class="site-section site-section-tint" id="cara-belajar" aria-labelledby="steps-title"><div class="container">
    <div class="site-section-heading"><div><p class="site-eyebrow">CARA BELAJAR</p><h2 id="steps-title">Dari memilih hingga memahami.</h2><p>Satu tempat untuk materi, progres, dan pertanyaan Anda.</p></div></div>
    <ol class="site-steps">
        <li><span>01 / PILIH</span><h3>Kenali materinya</h3><p>Periksa kurikulum, pengajar, dan harga sebelum menentukan course.</p></li>
        <li><span>02 / AKSES</span><h3>Selesaikan pembayaran</h3><p>Daftar atau masuk. Akses tersedia setelah pembayaran terkonfirmasi.</p></li>
        <li><span>03 / PELAJARI</span><h3>Bangun ritme belajar</h3><p>Putar materi dan lanjutkan dari posisi terakhir selama akses aktif.</p></li>
        <li><span>04 / DISKUSIKAN</span><h3>Tanyakan yang belum jelas</h3><p>Ajukan pertanyaan di materi dan berbagi pemahaman dengan peserta lain.</p></li>
    </ol>
</div></section>

<section class="site-section" aria-labelledby="access-title"><div class="container"><div class="site-access-panel">
    <div><p class="site-eyebrow">BELAJAR SESUAI RITME ANDA</p><h2 id="access-title">Pilih satu topik.<br>Mulai dari materi pertama.</h2><p>Lihat masa akses pada setiap course dan temukan kurikulum yang sesuai dengan kebutuhan Anda.</p></div>
    <a class="btn btn-primary btn-lg" href="{{ route('site.courses.index') }}">Temukan course<x-dashboard.icon name="arrow-right" /></a>
</div></div></section>
@endsection
