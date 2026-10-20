@extends('layouts.public')

@section('title', $course->title)
@section('meta_description', \Illuminate\Support\Str::limit($course->summary ?: strip_tags($safeDescription ?: $course->title), 155))
@section('og_type', 'product')
@if($course->thumbnail_path)@section('og_image', url(\Illuminate\Support\Facades\Storage::disk('public')->url($course->thumbnail_path)))@endif

@section('content')
<div class="container site-page site-detail nm-detail">
    @if($preview)
        <div class="site-preview-banner" role="status"><strong>Pratinjau admin</strong><span>Course ini berstatus {{ $course->status->label() }}. Hanya admin yang dapat melihat halaman pratinjau. Tutup tab ini untuk kembali ke area admin.</span></div>
    @endif
    <nav class="site-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('site.home') }}">Beranda</a><span aria-hidden="true">/</span><a href="{{ route('site.courses.index') }}">Katalog</a><span aria-hidden="true">/</span><span aria-current="page">{{ $course->title }}</span></nav>
    <header class="nm-detail-heading">
        <span class="nm-eyebrow">{{ $course->category?->name ?? 'Course video' }}</span>
        <h1>{{ $course->title }}</h1>
        @if($course->summary)<p class="site-detail-summary">{{ $course->summary }}</p>@endif
        <div class="nm-detail-heading-bottom">
            @if($course->instructor_name)<div class="nm-teacher"><span class="nm-teacher-avatar" aria-hidden="true">{{ mb_substr($course->instructor_name, 0, 1) }}</span><span><small>Belajar bersama</small><strong>{{ $course->instructor_name }}</strong></span></div>@endif
            <div class="site-detail-facts"><span><x-dashboard.icon name="layers" />{{ $course->sections_count }} bab</span><span><x-dashboard.icon name="file-video" />{{ $course->lessons_count }} video</span><span><x-dashboard.icon name="clock-3" />{{ $course->lessons_duration_seconds ? floor($course->lessons_duration_seconds / 60).' menit' : 'Durasi belum tersedia' }}</span></div>
        </div>
    </header>
    <div class="site-detail-grid">
        <div class="site-detail-image">@if($course->thumbnail_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($course->thumbnail_path) }}" alt="Thumbnail course {{ $course->title }}" width="960" height="540">@else<div class="nm-course-art" aria-hidden="true"><x-dashboard.icon name="book-open" /><span>NAMIKULO / COURSE</span></div>@endif</div>
        <aside class="site-purchase-card" aria-label="Harga dan akses"><div class="nm-purchase-kicker"><span>AKSES COURSE</span><x-dashboard.icon name="graduation-cap" /></div><div class="site-price">@if($course->discount_price !== null)<del>Rp {{ number_format($course->price, 0, ',', '.') }}</del>@endif<strong>{{ ($course->discount_price ?? $course->price) === 0 ? 'Gratis' : 'Rp '.number_format($course->discount_price ?? $course->price, 0, ',', '.') }}</strong></div><p>Satu pembelian memberi akses {{ $course->access_months }} bulan sejak pembayaran terkonfirmasi.</p>
            @if($preview)
                <span class="site-state-tag">Mode pratinjau</span>@if($firstReadyLesson)<a class="btn btn-primary btn-block mt-2" href="{{ route('learn.show', $firstReadyLesson) }}" target="_blank" rel="noopener noreferrer">Pratinjau video di tab baru</a>@endif
            @elseif($accessState === 'guest')
                <a class="btn btn-primary btn-block" href="{{ route('checkout.show', $course->slug) }}">Masuk untuk membeli</a><a class="site-card-secondary" href="{{ route('register') }}">Belum punya akun? Daftar</a>
            @elseif($accessState === 'active')
                <span class="site-state-tag">Akses Anda aktif</span>@if($firstReadyLesson)<a class="btn btn-primary btn-block mt-2" href="{{ route('learn.show', $firstReadyLesson) }}">Mulai belajar</a>@else<p class="site-card-note">Video course belum siap diputar.</p>@endif
            @elseif($accessState === 'pending_order')
                <span class="site-state-tag">Pembayaran menunggu konfirmasi</span>@if($pendingOrder)<a class="btn btn-primary btn-block mt-2" href="{{ route('orders.pay', $pendingOrder) }}">Lihat instruksi bayar</a>@endif
            @elseif($accessState === 'expired')
                <span class="site-state-tag">Akses sebelumnya tidak aktif</span><a class="btn btn-primary btn-block mt-2" href="{{ route('checkout.show', $course->slug) }}">Beli akses lagi</a>
            @else
                <a class="btn btn-primary btn-block" href="{{ route('checkout.show', $course->slug) }}">Beli course</a>
            @endif
            <div class="nm-included"><strong>Dalam course ini</strong><ul><li><x-dashboard.icon name="circle-check" /><span>{{ $course->lessons_count }} video dalam {{ $course->sections_count }} bab</span></li><li><x-dashboard.icon name="circle-check" /><span>Akses selama {{ $course->access_months }} bulan</span></li><li><x-dashboard.icon name="circle-check" /><span>Progres belajar tersimpan</span></li><li><x-dashboard.icon name="circle-check" /><span>Diskusi pada setiap materi</span></li></ul></div>
            <p class="nm-purchase-footnote"><x-dashboard.icon name="book-open" />Pelajari kurikulumnya, tentukan langkah Anda.</p>
        </aside>
        <div class="site-detail-main">


            <nav class="nm-detail-tabs" aria-label="Navigasi detail course"><a href="#description-title">Tentang course<x-dashboard.icon name="book-open" /></a><a href="#curriculum-title">Kurikulum<x-dashboard.icon name="layers" /></a></nav>
            <section class="site-detail-section" aria-labelledby="description-title"><h2 id="description-title">Tentang course ini</h2>@if($safeDescription)<x-rich-description :html="$safeDescription" />@else<p>Deskripsi lengkap belum tersedia. Lihat ringkasan dan daftar materi di bawah.</p>@endif</section>

            <section class="site-detail-section" aria-labelledby="curriculum-title"><div class="site-section-heading"><div><h2 id="curriculum-title">Kurikulum</h2><p>Judul materi dapat dilihat sebelum membeli. Video siap dapat diputar saat akses course aktif.</p></div></div>
                @forelse($course->sections as $section)
                    <div class="site-curriculum-section"><button class="site-curriculum-toggle" type="button" data-toggle="collapse" data-target="#section-{{ $section->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="section-{{ $section->id }}"><span class="nm-chapter-number" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="nm-chapter-label"><strong>{{ $section->title }}</strong><small>{{ $section->lessons->count() }} materi</small></span><x-dashboard.icon name="chevron-down" /></button>
                        <div id="section-{{ $section->id }}" class="collapse {{ $loop->first ? 'show' : '' }}"><ul class="site-lesson-list">@forelse($section->lessons as $lesson)<li><x-dashboard.icon name="file-video" /><span>{{ $lesson->title }}</span><small>{{ $lesson->duration_seconds ? floor($lesson->duration_seconds / 60).' menit' : 'Durasi belum tersedia' }}</small><span class="sr-only">Video belum dapat diputar di halaman ini</span></li>@empty<li>Belum ada materi di bab ini.</li>@endforelse</ul></div>
                    </div>
                @empty
                    <div class="site-empty"><h3>Kurikulum belum tersedia</h3><p>Bab dan materi akan ditampilkan setelah disiapkan oleh pengajar.</p></div>
                @endforelse
            </section>
        </div>


    </div>
</div>
@endsection
