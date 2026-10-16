@extends('layouts.public')

@section('title', 'Belajar dengan arah yang jelas')
@section('meta_description', 'Jelajahi course video Namikulo, lihat kurikulumnya, dan belajar dengan akses enam bulan setelah pembelian.')

@section('content')
<section class="site-hero" aria-labelledby="hero-title"><div class="container site-hero-grid">
    <div><p class="site-eyebrow">NAMIKULO COURSE <span aria-hidden="true">/</span> BELAJAR SESUAI RITME ANDA</p><h1 id="hero-title">Pilih keahlian berikutnya. Mulai dari langkah yang jelas.</h1><p class="site-lead">Lihat isi course sebelum memutuskan, lalu pelajari setiap materi sesuai waktu Anda selama enam bulan akses.</p><div class="site-hero-actions"><a class="btn btn-primary btn-lg" href="{{ route('site.courses.index') }}">Jelajahi course</a><a class="btn btn-outline-primary btn-lg" href="#cara-belajar">Lihat cara belajar</a></div></div>
    <div class="site-hero-art" aria-hidden="true"><div class="site-art-frame"><span>01</span><strong>Pilih topik</strong><small>Mulai dari yang ingin Anda kuasai.</small></div><div class="site-art-frame"><span>02</span><strong>Ikuti urutannya</strong><small>Bab dan materi disusun jelas.</small></div><div class="site-art-orbit">6<span>bulan<br>akses</span></div></div>
</div></section>

<section class="site-stats" aria-label="Angka platform"><div class="container site-stats-grid"><div><strong>{{ number_format($stats['members'], 0, ',', '.') }}</strong><span>peserta dengan akses aktif</span></div><div><strong>{{ number_format($stats['courses'], 0, ',', '.') }}</strong><span>course tersedia</span></div><div><strong>{{ number_format($stats['videos'], 0, ',', '.') }}</strong><span>video siap dipelajari</span></div></div></section>

<section class="site-section" aria-labelledby="featured-title"><div class="container"><div class="site-section-heading"><div><p class="site-eyebrow">PILIHAN COURSE</p><h2 id="featured-title">Course unggulan</h2><p>Temukan materi yang sedang kami soroti.</p></div><a class="site-text-link" href="{{ route('site.courses.index') }}">Lihat semua course <span aria-hidden="true">→</span></a></div>
    @if($featured->isEmpty())<div class="site-empty"><h3>Belum ada course unggulan</h3><p>Jelajahi katalog untuk melihat course yang tersedia saat ini.</p><a class="btn btn-outline-primary" href="{{ route('site.courses.index') }}">Buka katalog</a></div>@else<div class="site-course-grid">@foreach($featured as $course)<x-course-card :course="$course" :is-active="in_array($course->id, $activeCourseIds, true)" />@endforeach</div>@endif
</div></section>

<section class="site-section site-section-tint" id="cara-belajar" aria-labelledby="steps-title"><div class="container"><div class="site-section-heading"><div><p class="site-eyebrow">CARA BELAJAR</p><h2 id="steps-title">Dari memilih hingga menuntaskan</h2></div></div><ol class="site-steps"><li><span>01</span><h3>Buat akun</h3><p>Daftar agar pilihan course dan akses Anda tersimpan.</p></li><li><span>02</span><h3>Pilih course</h3><p>Periksa ringkasan, kurikulum, durasi, dan harganya.</p></li><li><span>03</span><h3>Selesaikan pembelian</h3><p>Akses dimulai setelah pembayaran terkonfirmasi.</p></li><li><span>04</span><h3>Belajar enam bulan</h3><p>Ikuti materi sesuai urutan dan waktu Anda.</p></li></ol><p class="site-phase-note">Pembelian online dan pemutar video sedang disiapkan. Saat ini Anda dapat menelusuri course dan kurikulumnya.</p></div></section>

<section class="site-section" aria-labelledby="access-title"><div class="container site-access-panel"><div><p class="site-eyebrow">AKSES COURSE</p><h2 id="access-title">Waktu belajar yang cukup untuk kembali dan mengulang.</h2><p>Setiap pembelian memberi akses selama enam bulan sejak pembayaran terkonfirmasi. Tanggal akhirnya dapat Anda lihat pada halaman course setelah akses tersedia.</p></div><a class="btn btn-primary btn-lg" href="{{ route('site.courses.index') }}">Lihat katalog</a></div></section>
@endsection
