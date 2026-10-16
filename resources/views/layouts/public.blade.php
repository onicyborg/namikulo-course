<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <meta name="description" content="@yield('meta_description', 'Platform course video berbayar.')">
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    <link rel="stylesheet" href="{{ url('css/site.css') }}">
    @stack('styles')
</head>
<body class="public-page" @auth data-timezone-url="{{ route('account.timezone.update') }}" data-saved-timezone="{{ auth()->user()->timezone }}" @endauth>
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
    <header class="public-header">
        <nav class="container public-nav" aria-label="Navigasi utama">
            <a class="public-brand" href="{{ route('site.home') }}">
                <span class="brand-mark" aria-hidden="true">N</span>
                <span>{{ config('app.name') }}</span>
            </a>
            <div class="public-nav-actions">
                @auth
                    <a class="btn btn-primary" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.dashboard') }}">Buka Dashboard</a>
                @else
                    <a class="btn btn-link" href="{{ route('login') }}">Masuk</a>
                    <a class="btn btn-primary" href="{{ route('register') }}">Buat Akun</a>
                @endauth
            </div>
        </nav>
    </header>
    <main id="main-content" tabindex="-1">@yield('content')</main>
    <footer class="public-footer">
        <div class="container">&copy; {{ date('Y') }} {{ config('app.name') }}</div>
    </footer>
    <script src="{{ asset('js/app.min.js') }}"></script>
    <script src="{{ url('js/app-custom.js') }}?v=2"></script>
    @stack('scripts')
</body>
</html>
