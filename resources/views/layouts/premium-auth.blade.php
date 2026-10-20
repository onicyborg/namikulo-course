<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    <script src="{{ url('js/pages/admin-dashboard-theme.js') }}?v=3"></script>
    <link rel="stylesheet" href="{{ url('css/admin-dashboard.css') }}?v=3">
    <link rel="stylesheet" href="{{ url('css/admin-pages.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/login.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/premium-theme.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/premium-auth.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/interaction-motion.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/modal-controls.css') }}?v=2">
    <link rel="stylesheet" href="{{ url('css/file-upload.css') }}?v=1">
</head>
<body class="nd-app nd-legacy nl-page nm-premium nm-auth">
    <a class="nd-skip" href="#main-content">Lewati ke formulir akun</a>
    <header class="na-header">
        <a class="na-brand" href="{{ route('site.home') }}" aria-label="Namikulo Course, halaman utama"><span class="na-brand-mark"><x-dashboard.icon name="book-open" /></span><span>Namikulo<small>COURSE</small></span></a>
        <div class="na-header-actions"><a href="{{ route('site.courses.index') }}">Katalog course<x-dashboard.icon name="arrow-up-right" /></a><x-theme-toggle /></div>
    </header>
    <main class="na-layout" id="main-content" tabindex="-1">
        <aside class="na-story" aria-labelledby="auth-story-title">
            <div class="na-story-top"><span>RUANG UNTUK BERTUMBUH</span><x-dashboard.icon name="book-open" /></div>
            <div class="na-story-copy"><h2 id="auth-story-title">{{ request()->routeIs('register') ? 'Rasa ingin tahu.' : 'Langkah kecil.' }}<br><span>{{ request()->routeIs('register') ? 'Awal yang baru.' : 'Kemajuan berarti.' }}</span></h2><p>Materi yang terarah, progres yang tersimpan, dan percakapan yang membantu Anda memahami.</p></div>
            <div class="na-learning-map" aria-label="Pengalaman belajar Namikulo">
                <div><span class="na-map-icon"><x-dashboard.icon name="layers" /></span><div><strong>Pelajari dengan arah</strong><p>Ikuti kurikulum, satu materi demi satu.</p></div><span class="na-map-number">01</span></div>
                <div><span class="na-map-icon"><x-dashboard.icon name="activity" /></span><div><strong>Lanjutkan progres Anda</strong><p>Kembali ke posisi belajar terakhir.</p></div><span class="na-map-number">02</span></div>
                <div><span class="na-map-icon"><x-dashboard.icon name="message-circle" /></span><div><strong>Temukan pemahaman bersama</strong><p>Ajukan pertanyaan di ruang diskusi.</p></div><span class="na-map-number">03</span></div>
            </div>
            <div class="na-story-footer"><span aria-hidden="true"></span>Satu akun. Seluruh perjalanan belajar Anda.</div>
        </aside>
        <section class="na-form-panel" aria-labelledby="@yield('form_title_id', 'login-title')">
            <div class="na-form-top"><span>{{ request()->routeIs('register') ? 'AKUN BARU' : 'RUANG BELAJAR ANDA' }}</span><x-dashboard.icon name="graduation-cap" /></div>
            @yield('content')
        </section>
    </main>
    <footer class="na-footer"><span>&copy; {{ date('Y') }} {{ config('app.name') }}</span><a href="{{ route('site.home') }}">Kembali ke beranda</a></footer>
    <script src="{{ url('js/app-custom.js') }}?v=5"></script>
    <script src="{{ url('js/interaction-motion.js') }}?v=1"></script>
    <script src="{{ url('js/file-upload.js') }}?v=1"></script>
    @include('partials.global-toast')
</body>
</html>
