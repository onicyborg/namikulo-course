<aside class="nd-sidebar" id="dashboard-sidebar" aria-label="Navigasi member">
    <div class="nd-brand-row">
        <a class="nd-brand" href="{{ route('member.dashboard') }}" aria-label="Namikulo Course, dashboard member">
            <span class="nd-brand-mark"><x-dashboard.icon name="book-open" /></span>
            <span>NAMIKULO<span class="nd-brand-subtitle">COURSE</span></span>
        </a>
        <button type="button" class="nd-icon-button nd-sidebar-close" data-sidebar-close aria-label="Tutup navigasi"><x-dashboard.icon name="x" /></button>
    </div>
    <nav class="nd-navigation">
        @foreach([
            'Belajar' => [['Dashboard', 'member.dashboard', 'layout-dashboard'], ['Course saya', 'member.courses.index', 'book-open']],
            'Akun' => [['Transaksi', 'member.orders.index', 'credit-card'], ['Notifikasi', 'member.notifications.index', 'bell'], ['Profil saya', 'member.profile.edit', 'users']],
        ] as $group => $links)
            <div class="nd-nav-group">
                <p class="nd-nav-label">{{ $group }}</p>
                <ul>
                    @foreach($links as [$label, $route, $icon])
                        @php($active = $route === 'member.dashboard' ? request()->routeIs($route) : request()->routeIs(str($route)->beforeLast('.').'.*') || ($route === 'member.orders.index' && request()->routeIs('orders.*', 'checkout.*')))
                        <li><a class="nd-nav-link{{ $active ? ' is-active' : '' }}" href="{{ route($route) }}" @if($active) aria-current="page" @endif><x-dashboard.icon :name="$icon" /><span>{{ $label }}</span></a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
        <div class="nd-nav-group"><p class="nd-nav-label">Jelajahi</p><ul><li><a class="nd-nav-link" href="{{ route('site.courses.index') }}"><x-dashboard.icon name="graduation-cap" /><span>Katalog course</span></a></li></ul></div>
    </nav>
    <div class="nd-sidebar-footer"><span class="nd-role-dot" aria-hidden="true"></span>Area belajar member</div>
</aside>
