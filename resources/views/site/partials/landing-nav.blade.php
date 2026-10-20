<header class="public-header nm-header">
    <nav class="container public-nav" aria-label="Navigasi utama">
        <a class="public-brand" href="{{ route('site.home') }}"><span class="brand-mark"><x-dashboard.icon name="book-open" /></span><span>Namikulo<span class="nm-brand-caption">COURSE</span></span></a>
        <div class="np-theme-switch"><x-theme-toggle /></div>
        <button class="public-menu-toggle" type="button" aria-controls="public-menu" aria-expanded="false" aria-label="Buka menu" data-site-menu-toggle><span></span><span></span><span></span></button>
        <div class="public-nav-actions" id="public-menu" data-site-menu>
            <a class="public-nav-link {{ request()->routeIs('site.courses.*') ? 'active' : '' }}" @if(request()->routeIs('site.courses.index')) aria-current="page" @endif href="{{ route('site.courses.index') }}">Katalog course</a>
            <a class="public-nav-link" href="{{ request()->routeIs('site.home') ? '' : route('site.home') }}#pengalaman">Pengalaman belajar</a>
            <a class="public-nav-link" href="{{ request()->routeIs('site.home') ? '' : route('site.home') }}#cara-belajar">Cara belajar</a>
            <span class="nm-nav-divider" aria-hidden="true"></span>
            @auth
                <a class="btn btn-primary" href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.dashboard') }}">Dashboard saya<x-dashboard.icon name="arrow-up-right" /></a>
            @else
                <a class="public-nav-link" href="{{ route('login') }}">Masuk</a>
                <a class="btn btn-primary" href="{{ route('register') }}">Buat akun</a>
            @endauth
        </div>
    </nav>
</header>
