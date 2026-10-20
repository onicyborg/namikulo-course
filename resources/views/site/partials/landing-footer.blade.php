<footer class="public-footer nm-footer">
    <div class="container">
        <div class="nm-footer-main">
            <div><a class="public-brand" href="{{ route('site.home') }}"><span class="brand-mark"><x-dashboard.icon name="book-open" /></span><span>Namikulo<span class="nm-brand-caption">COURSE</span></span></a><p>Ruang untuk rasa ingin tahu.<br>Langkah untuk pemahaman baru.</p></div>
            <nav aria-label="Jelajahi Namikulo"><strong>Jelajahi</strong><a href="{{ route('site.courses.index') }}">Katalog course</a><a href="{{ request()->routeIs('site.home') ? '' : route('site.home') }}#pengalaman">Pengalaman belajar</a><a href="{{ request()->routeIs('site.home') ? '' : route('site.home') }}#cara-belajar">Cara belajar</a></nav>
            <nav aria-label="Akun Anda"><strong>Ruang Anda</strong>@auth<a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.dashboard') }}">Dashboard saya</a>@else<a href="{{ route('login') }}">Masuk ke akun</a><a href="{{ route('register') }}">Buat akun baru</a>@endauth</nav>
        </div>
        <div class="nm-footer-bottom"><small>&copy; {{ date('Y') }} {{ config('app.name') }}. Seluruh hak cipta dilindungi.</small><span>Belajar dengan arah yang jelas.</span></div>
    </div>
</footer>
