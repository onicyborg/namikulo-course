@php($redesignedSite = request()->routeIs('site.home', 'site.courses.*', 'admin.courses.preview'))
@php($premiumSite = $redesignedSite)
<!DOCTYPE html>
<html lang="id" @if(request()->routeIs('site.home')) data-nc-default-theme="dark" @endif>
<head>
    @include('partials.head')
    <meta name="description" content="{{ $__env->yieldContent('meta_description', 'Course video dengan kurikulum yang jelas.') }}">
    <meta property="og:type" content="{{ $__env->yieldContent('og_type', 'website') }}">
    <meta property="og:title" content="{{ $__env->yieldContent('title', config('app.name')) }} | {{ config('app.name') }}">
    <meta property="og:description" content="{{ $__env->yieldContent('meta_description', 'Course video dengan kurikulum yang jelas.') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')<meta property="og:image" content="{{ $__env->yieldContent('og_image') }}">@endif
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    @if($redesignedSite)
        <script src="{{ url('js/pages/admin-dashboard-theme.js') }}?v=2"></script>
        <link rel="stylesheet" href="{{ url('css/admin-dashboard.css') }}">
    @endif
    <link rel="stylesheet" href="{{ url('css/site.css') }}">
    @if($redesignedSite)<link rel="stylesheet" href="{{ url('css/public-site.css') }}?v=1">@endif
    @if($premiumSite)
        <link rel="stylesheet" href="{{ url('css/premium-theme.css') }}?v=1">
        <link rel="stylesheet" href="{{ url('css/landing-minimal.css') }}?v=3">
        @if(!request()->routeIs('site.home'))<link rel="stylesheet" href="{{ url('css/premium-courses.css') }}?v=1">@endif
    @endif
    @stack('styles')
    <link rel="stylesheet" href="{{ url('css/interaction-motion.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/modal-controls.css') }}?v=1">
</head>
<body class="public-page {{ $redesignedSite ? 'nd-app np-page' : '' }} {{ $premiumSite ? 'nm-premium nm-public' : '' }} {{ request()->routeIs('site.home') ? 'nm-home' : '' }}" @auth data-timezone-url="{{ route('account.timezone.update') }}" data-saved-timezone="{{ auth()->user()->timezone }}" @endauth>
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
    @if($premiumSite)
        @include('site.partials.landing-nav')
    @else
    <header class="public-header">
        <nav class="container public-nav" aria-label="Navigasi utama">
            <a class="public-brand" href="{{ route('site.home') }}">
                <span class="brand-mark" aria-hidden="true">@if($redesignedSite)<x-dashboard.icon name="book-open" />@else N @endif</span>
                <span>{{ config('app.name') }}</span>
            </a>
            @if($redesignedSite)<div class="np-theme-switch"><x-theme-toggle /></div>@endif
            <button class="public-menu-toggle" type="button" aria-controls="public-menu" aria-expanded="false" aria-label="Buka menu" data-site-menu-toggle><span></span><span></span><span></span></button>
            <div class="public-nav-actions" id="public-menu" data-site-menu>
                <a class="public-nav-link {{ request()->routeIs('site.courses.*') ? 'active' : '' }}" href="{{ route('site.courses.index') }}" @if(request()->routeIs('site.courses.*')) aria-current="page" @endif>Katalog</a>
                @auth
                    <a class="btn btn-outline-primary" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.dashboard') }}">Dashboard</a>
                @else
                    <a class="btn btn-link" href="{{ route('login') }}">Masuk</a>
                    <a class="btn btn-primary" href="{{ route('register') }}">Buat Akun</a>
                @endauth
            </div>
        </nav>
    </header>
    @endif
    <main id="main-content" tabindex="-1">@yield('content')</main>
    @if($premiumSite)
        @include('site.partials.landing-footer')
    @else
    <footer class="public-footer">
        <div class="container site-footer-inner"><div><strong>{{ config('app.name') }}</strong><p>Belajar sesuai ritme Anda, dengan kurikulum yang dapat dilihat sejak awal.</p></div><nav aria-label="Tautan footer"><a href="{{ route('site.courses.index') }}">Katalog</a><a href="{{ route('site.home') }}#cara-belajar">Cara belajar</a>@guest<a href="{{ route('register') }}">Daftar</a>@endguest</nav><small>&copy; {{ date('Y') }} {{ config('app.name') }}</small></div>
    </footer>
    @endif
    <script src="{{ asset('js/app.min.js') }}"></script>
    <script src="{{ url('js/app-custom.js') }}?v=5"></script>
    <script src="{{ url('js/interaction-motion.js') }}?v=1"></script>
    <script src="{{ url('js/pages/site.js') }}"></script>
    @if($premiumSite)<script src="{{ url('js/pages/landing.js') }}?v=2" defer></script>@endif
    @stack('scripts')
    @include('partials.global-toast')
</body>
</html>
