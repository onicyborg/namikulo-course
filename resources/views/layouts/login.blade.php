<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    <script src="{{ url('js/pages/admin-dashboard-theme.js') }}?v=3"></script>
    <link rel="stylesheet" href="{{ url('css/admin-dashboard.css') }}?v=3">
    <link rel="stylesheet" href="{{ url('css/admin-pages.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/login.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/interaction-motion.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/modal-controls.css') }}?v=2">
    <link rel="stylesheet" href="{{ url('css/file-upload.css') }}?v=1">
</head>
<body class="nd-app nd-legacy nl-page">
    <a class="nd-skip" href="#main-content">Lewati ke formulir akun</a>
    <header class="nl-header">
        <a class="nd-brand" href="{{ route('site.home') }}" aria-label="{{ config('app.name') }}, halaman utama">
            <span class="nd-brand-mark"><x-dashboard.icon name="book-open" /></span>
            <span>NAMIKULO<span class="nd-brand-subtitle">COURSE</span></span>
        </a>
        <div class="nl-header-actions"><a href="{{ route('site.courses.index') }}">Jelajahi course<x-dashboard.icon name="arrow-up-right" /></a><x-theme-toggle /></div>
    </header>
    <main class="nl-layout" id="main-content" tabindex="-1">
        <aside class="nl-story" aria-labelledby="login-story-title">
            <div class="nl-story-copy">
                <span class="nl-eyebrow">RUANG BELAJAR ANDA</span>
                <h2 id="login-story-title">Belajar bertahap.<br><span>Tumbuh lewat praktik.</span></h2>
                <p>Materi yang terstruktur, progres yang tersimpan, dan ruang untuk bertanya saat Anda membutuhkannya.</p>
            </div>
            <dl class="nl-learning-notes">
                <div><dt><x-dashboard.icon name="book-open" />Ikuti alur materi</dt><dd>Pelajari video per bab dan lanjutkan dari posisi terakhir Anda.</dd></div>
                <div><dt><x-dashboard.icon name="message-square" />Diskusikan yang belum jelas</dt><dd>Ajukan pertanyaan dan bertukar pemahaman bersama member dan admin.</dd></div>
            </dl>
            <div class="nl-story-footer"><x-dashboard.icon name="graduation-cap" /><span>Satu akun untuk seluruh perjalanan belajar Anda.</span></div>
        </aside>
        <section class="nl-form-panel" aria-labelledby="@yield('form_title_id', 'login-title')">@yield('content')</section>
    </main>
    <footer class="nl-footer"><span>&copy; {{ date('Y') }} {{ config('app.name') }}</span><a href="{{ route('site.home') }}">Kembali ke beranda</a></footer>
    <script src="{{ url('js/app-custom.js') }}?v=5"></script>
    <script src="{{ url('js/interaction-motion.js') }}?v=1"></script>
    <script src="{{ url('js/file-upload.js') }}?v=1"></script>
    @include('partials.global-toast')
</body>
</html>
