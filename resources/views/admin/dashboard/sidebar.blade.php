<aside class="nd-sidebar" id="dashboard-sidebar" aria-label="Navigasi admin">
    <div class="nd-brand-row">
        <a class="nd-brand" href="{{ route('admin.dashboard') }}" aria-label="Namikulo Course, dashboard">
            <span class="nd-brand-mark"><x-dashboard.icon name="book-open" /></span>
            <span>NAMIKULO<span class="nd-brand-subtitle">COURSE</span></span>
        </a>
        <button type="button" class="nd-icon-button nd-sidebar-close" data-sidebar-close aria-label="Tutup navigasi"><x-dashboard.icon name="x" /></button>
    </div>
    <nav class="nd-navigation">
        @foreach([
            'Admin' => [['Dashboard', 'admin.dashboard', 'layout-dashboard']],
            'Konten' => [['Kategori', 'admin.categories.index', 'layers'], ['Course', 'admin.courses.index', 'book-open'], ['Diskusi materi', 'admin.discussions.index', 'message-square']],
            'Transaksi' => [['Notifikasi', 'admin.notifications.index', 'bell'], ['Pesanan', 'admin.orders.index', 'credit-card']],
            'Pengguna' => [['User', 'admin.users.index', 'users']],
            'Operasi' => [['Tugas & log', 'admin.operations.index', 'activity']],
        ] as $group => $links)
            <div class="nd-nav-group">
                <p class="nd-nav-label">{{ $group }}</p>
                <ul>
                    @foreach($links as [$label, $route, $icon])
                        @php($active = $route === 'admin.dashboard'
                            ? request()->routeIs('admin.dashboard')
                            : (request()->routeIs($route, str($route)->beforeLast('.').'.*')
                                || ($route === 'admin.courses.index' && request()->routeIs('admin.sections.*', 'admin.lessons.*'))))
                        <li><a class="nd-nav-link{{ $active ? ' is-active' : '' }}" href="{{ route($route) }}" @if($active) aria-current="page" @endif><x-dashboard.icon :name="$icon" /><span>{{ $label }}</span></a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
    <div class="nd-sidebar-footer"><span class="nd-role-dot" aria-hidden="true"></span>Area administrator</div>
</aside>
