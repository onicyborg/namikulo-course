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
        </ul>
    </aside>
</div>
