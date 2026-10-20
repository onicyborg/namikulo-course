@extends('layouts.public')
@section('title', 'Belajar dengan arah yang jelas')
@section('meta_description', 'Temukan course video dengan kurikulum terstruktur. Pelajari materi, ikuti progres Anda, dan berdiskusi bersama peserta lainnya di Namikulo Course.')
@section('content')
@php($spotlight = $featured->first())
<section class="nm-hero" aria-labelledby="hero-title">
    <div class="container nm-hero-grid">
        <div class="nm-hero-copy">
            <p class="nm-eyebrow"><span aria-hidden="true"></span>RASA INGIN TAHU, PUNYA ARAH.</p>
            <h1 id="hero-title">Keahlian baru.<br>Dimulai dari<br><em>satu langkah.</em></h1>
            <p class="nm-lead">Dari menonton hingga memahami. Pelajari materi secara bertahap, ikuti progres Anda, dan temukan jawaban bersama di ruang diskusi.</p>
            <div class="nm-hero-actions"><a class="btn btn-primary btn-lg" href="{{ route('site.courses.index') }}">Temukan course Anda<x-dashboard.icon name="arrow-right" /></a><a class="nm-quiet-link" href="#cara-belajar">Lihat cara belajarnya<x-dashboard.icon name="chevron-down" /></a></div>
            <div class="nm-hero-assurance"><x-dashboard.icon name="layers" /><span>Kurikulum terbuka.<br><strong>Kenali materinya sebelum memilih.</strong></span></div>
        </div>
        @include('site.partials.landing-showcase')
    </div>
    <div class="container"><div class="nm-stats" aria-label="Platform dalam angka"><p>Ruang belajar.<br><strong>Kesempatan bertumbuh.</strong></p><div><strong>{{ number_format($stats['courses'], 0, ',', '.') }}</strong><span>course tersedia</span></div><div><strong>{{ number_format($stats['videos'], 0, ',', '.') }}</strong><span>video siap dipelajari</span></div><div><strong>{{ number_format($stats['members'], 0, ',', '.') }}</strong><span>peserta dengan akses aktif</span></div></div></div>
</section>
<section class="nm-section nm-featured" id="course-unggulan" aria-labelledby="featured-title"><div class="container">
    <div class="nm-section-heading"><div><p class="nm-eyebrow">01 / PILIHAN BELAJAR</p><h2 id="featured-title">Fokus baru.<br><span>Kemungkinan baru.</span></h2></div><div class="nm-heading-aside"><p>Temukan topik yang ingin Anda kuasai.<br>Mulai dengan kurikulum yang jelas.</p><a class="nm-text-link" href="{{ route('site.courses.index') }}">Lihat seluruh course<x-dashboard.icon name="arrow-right" /></a></div></div>
    @if($featured->isEmpty())
        <div class="nm-empty"><x-dashboard.icon name="book-open" /><div><h3>Temukan pilihan belajar di katalog.</h3><p>Course unggulan belum tersedia. Lihat course lain yang dapat Anda pelajari.</p></div><a class="btn btn-primary" href="{{ route('site.courses.index') }}">Buka katalog</a></div>
    @else
        <div class="nm-course-grid {{ $featured->count() === 1 ? 'nm-course-grid--single' : '' }}">@foreach($featured as $course)@include('site.partials.landing-course')@endforeach</div>
    @endif
</div></section>
<section class="nm-section nm-benefits" id="pengalaman" aria-labelledby="benefits-title"><div class="container">
    <div class="nm-section-heading"><div><p class="nm-eyebrow">02 / PENGALAMAN BELAJAR</p><h2 id="benefits-title">Satu ruang belajar.<br><span>Banyak cara memahami.</span></h2></div><p class="nm-heading-aside">Materi yang terarah, progres yang tercatat,<br>dan tempat untuk bertanya.</p></div>
    <div class="nm-bento">
        <article class="nm-benefit nm-benefit--curriculum"><div class="nm-benefit-copy"><span class="nm-feature-icon"><x-dashboard.icon name="layers" /></span><h3>Langkah kecil.<br>Pemahaman yang utuh.</h3><p>Ikuti bab dan materi secara berurutan. Bangun fondasi sebelum melangkah ke pembahasan berikutnya.</p></div><div class="nm-outline-demo" aria-hidden="true"><div><span>01</span><strong>Mulai dari dasar</strong><x-dashboard.icon name="circle-check" /></div><div><span>02</span><strong>Perdalam pemahaman</strong><x-dashboard.icon name="circle-check" /></div><div><span>03</span><strong>Lanjut ke materi berikutnya</strong><x-dashboard.icon name="arrow-right" /></div></div></article>
        <article class="nm-benefit nm-benefit--discussion"><span class="nm-feature-icon"><x-dashboard.icon name="message-circle" /></span><h3>Pertanyaan Anda<br>punya tempat.</h3><p>Tanyakan bagian yang belum jelas. Berdiskusi dengan peserta lain dan admin, tepat di bawah materi.</p><div class="nm-discussion-demo" aria-hidden="true"><x-dashboard.icon name="message-square" /><span>Materi dipelajari.<br><strong>Pemahaman dibagikan.</strong></span><span class="nm-discussion-dot"></span></div></article>
        <article class="nm-benefit nm-benefit--progress"><div class="nm-progress-art" aria-hidden="true"><x-dashboard.icon name="activity" /></div><div><h3>Setiap progres berarti.</h3><p>Lanjutkan dari posisi terakhir. Materi yang sudah selesai tetap tercatat dalam perjalanan belajar Anda.</p></div></article>
        <article class="nm-benefit nm-benefit--pace"><span class="nm-feature-icon"><x-dashboard.icon name="clock-3" /></span><div><h3>Ritme belajar milik Anda.</h3><p>Jeda, ulangi, dan pelajari kembali video selama masa akses course masih aktif.</p></div></article>
    </div>
</div></section>
<section class="nm-section nm-journey" id="cara-belajar" aria-labelledby="steps-title"><div class="container">
    <div class="nm-section-heading"><div><p class="nm-eyebrow">03 / LANGKAH PERTAMA</p><h2 id="steps-title">Dari penasaran,<br><span>menjadi paham.</span></h2></div><p class="nm-heading-aside">Satu alur sederhana.<br>Mulai dari tujuan belajar Anda.</p></div>
    <ol class="nm-steps">
        <li><div class="nm-step-top"><span>01</span><x-dashboard.icon name="book-open" /></div><h3>Temukan topiknya</h3><p>Lihat kurikulum, pengajar, dan harga. Pilih course yang sesuai dengan tujuan Anda.</p></li>
        <li><div class="nm-step-top"><span>02</span><x-dashboard.icon name="credit-card" /></div><h3>Aktifkan aksesnya</h3><p>Buat akun dan selesaikan pembayaran. Akses aktif setelah pembayaran terkonfirmasi.</p></li>
        <li><div class="nm-step-top"><span>03</span><x-dashboard.icon name="file-video" /></div><h3>Bangun pemahaman</h3><p>Pelajari video secara bertahap. Ulangi bagian yang dibutuhkan dan ikuti progres Anda.</p></li>
        <li><div class="nm-step-top"><span>04</span><x-dashboard.icon name="message-circle" /></div><h3>Buka percakapan</h3><p>Ajukan pertanyaan di materi dan berbagi pemahaman dengan peserta lainnya.</p></li>
    </ol>
</div></section>
<section class="nm-final-section" aria-labelledby="cta-title"><div class="container"><div class="nm-final">
    <div><p class="nm-eyebrow">LANGKAH SELANJUTNYA MILIK ANDA</p><h2 id="cta-title">Ada banyak yang bisa dipelajari.<br><span>Mulai dari satu course.</span></h2><p>Temukan kurikulum yang sesuai. Beri ruang untuk keahlian baru.</p><a class="btn btn-lg nm-final-button" href="{{ route('site.courses.index') }}">Jelajahi katalog course<x-dashboard.icon name="arrow-right" /></a></div>
    <div class="nm-final-art" aria-hidden="true"><span></span><span></span><span><x-dashboard.icon name="book-open" /></span></div>
</div></div></section>
@endsection
