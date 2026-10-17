<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper" aria-label="Menu admin">
        <div class="sidebar-brand">
            <a href="{{ route('admin.dashboard') }}" class="brand-link">
                <span class="logo-name">{{ config('app.name') }}</span>
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="{{ route('admin.dashboard') }}" aria-label="{{ config('app.name') }}">N</a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-header">ADMIN</li>
            <li class="dropdown {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">
                    <i data-feather="home" aria-hidden="true"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="menu-header">KONTEN</li>
            <li class="dropdown {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><a href="{{ route('admin.categories.index') }}" class="nav-link"><i data-feather="grid" aria-hidden="true"></i><span>Kategori</span></a></li>
            <li class="dropdown {{ request()->routeIs('admin.courses.*') || request()->routeIs('admin.sections.*') ? 'active' : '' }}"><a href="{{ route('admin.courses.index') }}" class="nav-link"><i data-feather="book-open" aria-hidden="true"></i><span>Course</span></a></li>
            <li class="menu-header">TRANSAKSI</li>
            <li class="dropdown {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><a href="{{ route('admin.orders.index') }}" class="nav-link"><i data-feather="credit-card" aria-hidden="true"></i><span>Pesanan</span></a></li>
            <li class="menu-header">PENGGUNA</li>
            <li class="dropdown {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><a href="{{ route('admin.users.index') }}" class="nav-link"><i data-feather="users" aria-hidden="true"></i><span>User</span></a></li>
            <li class="menu-header">OPERASI</li>
            <li class="dropdown {{ request()->routeIs('admin.operations.*') ? 'active' : '' }}"><a href="{{ route('admin.operations.index') }}" class="nav-link"><i data-feather="activity" aria-hidden="true"></i><span>Tugas &amp; log</span></a></li>
        </ul>
    </aside>
</div>
