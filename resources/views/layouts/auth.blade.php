<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ url('css/site.css') }}">
    @stack('styles')
</head>
<body class="auth-page">
    <a class="skip-link" href="#main-content">Lewati ke formulir</a>
    <main id="main-content" class="auth-shell" tabindex="-1">
        <div class="auth-brand">
            <a href="{{ route('site.home') }}" aria-label="Kembali ke halaman utama {{ config('app.name') }}">
                <span class="brand-mark" aria-hidden="true">N</span>
                <span>{{ config('app.name') }}</span>
            </a>
        </div>
        <div class="auth-content">
            @yield('content')
        </div>
    </main>
    <script src="{{ asset('js/app.min.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ url('js/app-custom.js') }}?v=3"></script>
    @stack('scripts')
</body>
</html>
