@php($notificationPrefix = auth()->user()->isAdmin() ? 'admin.notifications' : 'member.notifications')
<header class="nd-topbar">
    <div class="nd-topbar-start">
        <button class="nd-icon-button nd-menu-toggle" type="button" data-sidebar-toggle aria-controls="dashboard-sidebar" aria-expanded="false" aria-label="Buka navigasi"><x-dashboard.icon name="menu" /></button>
        <span class="nd-topbar-context"><x-dashboard.icon name="layout-dashboard" /><span>@yield('title', 'Area admin')</span></span>
    </div>
    <div class="nd-topbar-actions">
        <x-theme-toggle />
        <details class="nd-notification-menu" data-notification-menu>
            <summary class="nd-icon-button nd-notification" aria-label="Notifikasi{{ $unreadNotifications ? ', '.$unreadNotifications.' belum dibaca' : '' }}" aria-haspopup="true">
                <x-dashboard.icon name="bell" />
                @if($unreadNotifications)<span class="nd-notification-count" aria-hidden="true">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>@endif
            </summary>
            <div class="nd-notification-popover" role="group" aria-label="Notifikasi terbaru">
                <div class="nd-notification-heading"><div><strong>Notifikasi terbaru</strong><span>{{ $unreadNotifications ? $unreadNotifications.' belum dibaca' : 'Semua sudah dibaca' }}</span></div></div>
                @if($recentNotifications->isEmpty())
                    <p class="nd-notification-empty">Belum ada notifikasi.</p>
                @else
                    <ul class="nd-notification-list">
                        @foreach($recentNotifications as $notification)
                            <li>
                                <form method="POST" action="{{ route($notificationPrefix.'.open', $notification) }}">
                                    @csrf
                                    <button type="submit" class="nd-notification-item{{ $notification->read_at ? '' : ' is-unread' }}" aria-label="Buka notifikasi: {{ $notification->title }}">
                                        <span class="nd-notification-indicator" aria-hidden="true"></span>
                                        <span class="nd-notification-copy"><strong>{{ $notification->title }}</strong><span>{{ $notification->summary }}</span><time datetime="{{ $notification->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $notification->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time></span>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <a class="nd-notification-all" href="{{ route($notificationPrefix.'.index') }}">Lihat semua notifikasi<x-dashboard.icon name="arrow-right" /></a>
            </div>
        </details>
        <span class="nd-topbar-divider" aria-hidden="true"></span>
        <details class="nd-profile" data-profile-menu>
            <summary aria-label="Menu akun {{ auth()->user()->name }}">
                @if(auth()->user()->avatar_path)
                    <img class="nd-avatar" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->avatar_path) }}" alt="" width="36" height="36">
                @else
                    <span class="nd-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                @endif
                <span class="nd-profile-name">{{ auth()->user()->name }}<small>{{ auth()->user()->isAdmin() ? 'Administrator' : 'Member' }}</small></span>
                <x-dashboard.icon name="chevron-down" />
            </summary>
            <div class="nd-profile-dropdown">
                <p>{{ auth()->user()->name }}<span>{{ auth()->user()->role->label() }}</span></p>
                @unless(auth()->user()->isAdmin())<a class="nm-profile-link" href="{{ route('member.profile.edit') }}"><x-dashboard.icon name="users" />Profil saya</a>@endunless
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit"><x-dashboard.icon name="log-out" />Keluar</button></form>
            </div>
        </details>
    </div>
</header>
