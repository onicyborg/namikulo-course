<div class="navbar-bg"></div>
<nav class="navbar navbar-expand-lg main-navbar sticky" aria-label="Navigasi aplikasi">
    <div class="form-inline mr-auto">
        <ul class="navbar-nav mr-3">
            <li>
                <a href="#" data-toggle="sidebar" class="nav-link nav-link-lg collapse-btn" aria-label="Buka atau tutup menu samping">
                    <i data-feather="menu" aria-hidden="true"></i>
                </a>
            </li>
            <li>
                <a href="#" class="nav-link nav-link-lg fullscreen-btn" aria-label="Aktifkan layar penuh">
                    <i data-feather="maximize" aria-hidden="true"></i>
                </a>
            </li>
        </ul>
    </div>
    <ul class="navbar-nav navbar-right">
        <li class="dropdown">
            <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user" aria-haspopup="true" aria-expanded="false">
                @if(auth()->user()->avatar_path)
                    <img class="nav-user-avatar nav-user-avatar-image" src="{{ url('storage/'.auth()->user()->avatar_path) }}" alt="" width="32" height="32">
                @else
                    <span class="nav-user-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                @endif
                <span class="nav-user-name d-none d-sm-inline-block" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-title">{{ auth()->user()->role->label() }}</div>
                <div class="dropdown-divider"></div>
                @if(auth()->user()->isMember())<a href="{{ route('member.profile.edit') }}" class="dropdown-item">Profil saya</a>@endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item text-danger">Keluar</button>
                </form>
            </div>
        </li>
    </ul>
</nav>
