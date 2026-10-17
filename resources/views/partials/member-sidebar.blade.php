<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper" aria-label="Menu member">
        <div class="sidebar-brand">
            <a href="{{ route('member.dashboard') }}" class="brand-link">
                <span class="logo-name">{{ config('app.name') }}</span>
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="{{ route('member.dashboard') }}" aria-label="{{ config('app.name') }}">N</a>
        </div>
        <ul class="sidebar-menu">
            <li class="menu-header">AREA MEMBER</li>
            <li class="dropdown {{ request()->routeIs('member.dashboard') ? 'active' : '' }}">
                <a href="{{ route('member.dashboard') }}" class="nav-link">
                    <i data-feather="home" aria-hidden="true"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="dropdown {{ request()->routeIs('member.courses.*') ? 'active' : '' }}">
                <a href="{{ route('member.courses.index') }}" class="nav-link">
                    <i data-feather="book-open" aria-hidden="true"></i><span>Course Saya</span>
                </a>
            </li>
            <li class="dropdown {{ request()->routeIs('member.orders.*') ? 'active' : '' }}">
                <a href="{{ route('member.orders.index') }}" class="nav-link">
                    <i data-feather="file-text" aria-hidden="true"></i><span>Transaksi</span>
                </a>
            </li>
            <li class="dropdown {{ request()->routeIs('member.profile.*') ? 'active' : '' }}">
                <a href="{{ route('member.profile.edit') }}" class="nav-link">
                    <i data-feather="user" aria-hidden="true"></i><span>Profil</span>
                </a>
            </li>
        </ul>
    </aside>
</div>
