@extends('layouts.public')

@section('title', $course->title)
@section('meta_description', \Illuminate\Support\Str::limit($course->summary ?: strip_tags($safeDescription ?: $course->title), 155))
@section('og_type', 'product')
@if($course->thumbnail_path)@section('og_image', url('storage/'.$course->thumbnail_path))@endif

@section('content')
<div class="container site-page site-detail">
    @if($preview)
        <div class="site-preview-banner" role="status"><strong>Pratinjau admin</strong><span>Course ini berstatus {{ $course->status->label() }}. Hanya admin yang dapat melihat halaman pratinjau. Tutup tab ini untuk kembali ke area admin.</span></div>
    @endif
    <nav class="site-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('site.home') }}">Beranda</a><span aria-hidden="true">/</span><a href="{{ route('site.courses.index') }}">Katalog</a><span aria-hidden="true">/</span><span aria-current="page">{{ $course->title }}</span></nav>
    <div class="site-detail-grid">
        <div class="site-detail-main">
            <span class="site-category">{{ $course->category?->name ?? 'Course' }}</span>
            <h1>{{ $course->title }}</h1>
            @if($course->summary)<p class="site-detail-summary">{{ $course->summary }}</p>@endif
            <div class="site-detail-facts"><span>{{ $course->sections_count }} bab</span><span>{{ $course->lessons_count }} video</span><span>{{ $course->lessons_duration_seconds ? floor($course->lessons_duration_seconds / 60).' menit' : 'Durasi belum tersedia' }}</span><span>Akses {{ $course->access_months }} bulan</span></div>
            @if($course->instructor_name)<p class="site-instructor">Pengajar: <strong>{{ $course->instructor_name }}</strong></p>@endif
            <div class="site-detail-image">@if($course->thumbnail_path)<img src="{{ url('storage/'.$course->thumbnail_path) }}" alt="Thumbnail course {{ $course->title }}" width="960" height="540">@else<div class="site-course-placeholder" aria-hidden="true"><span>N</span></div>@endif</div>

            <section class="site-detail-section" aria-labelledby="description-title"><h2 id="description-title">Tentang course ini</h2>@if($safeDescription)<x-rich-description :html="$safeDescription" />@else<p>Deskripsi lengkap belum tersedia. Lihat ringkasan dan daftar materi di bawah.</p>@endif</section>

            <section class="site-detail-section" aria-labelledby="curriculum-title"><div class="site-section-heading"><div><h2 id="curriculum-title">Kurikulum</h2><p>Judul materi dapat dilihat sebelum membeli. Video siap dapat diputar saat akses course aktif.</p></div></div>
                @forelse($course->sections as $section)
                    <div class="site-curriculum-section"><button class="site-curriculum-toggle" type="button" data-toggle="collapse" data-target="#section-{{ $section->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="section-{{ $section->id }}"><span><strong>{{ $section->title }}</strong><small>{{ $section->lessons->count() }} materi</small></span><span aria-hidden="true">⌄</span></button>
                        <div id="section-{{ $section->id }}" class="collapse {{ $loop->first ? 'show' : '' }}"><ul class="site-lesson-list">@forelse($section->lessons as $lesson)<li><span aria-hidden="true">▸</span><span>{{ $lesson->title }}</span><small>{{ $lesson->duration_seconds ? floor($lesson->duration_seconds / 60).' menit' : 'Durasi belum tersedia' }}</small><span class="sr-only">Video belum dapat diputar di halaman ini</span></li>@empty<li>Belum ada materi di bab ini.</li>@endforelse</ul></div>
                    </div>
                @empty
                    <div class="site-empty"><h3>Kurikulum belum tersedia</h3><p>Bab dan materi akan ditampilkan setelah disiapkan oleh pengajar.</p></div>
                @endforelse
            </section>
        </div>

        <aside class="site-purchase-card" aria-label="Harga dan akses"><div class="site-price">@if($course->discount_price !== null)<del>Rp {{ number_format($course->price, 0, ',', '.') }}</del>@endif<strong>{{ ($course->discount_price ?? $course->price) === 0 ? 'Gratis' : 'Rp '.number_format($course->discount_price ?? $course->price, 0, ',', '.') }}</strong></div><p>Satu pembelian memberi akses {{ $course->access_months }} bulan sejak pembayaran terkonfirmasi.</p>
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
        </aside>
    </div>
</div>
@endsection
