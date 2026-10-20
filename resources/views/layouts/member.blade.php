<!DOCTYPE html>
<html lang="id">
<head>
    @php($usesWorkspaceTheme = request()->routeIs('member.dashboard', 'member.courses.*', 'member.orders.index', 'member.notifications.*', 'member.profile.*'))
    @php($usesServerTable = request()->routeIs('member.orders.index'))
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    @stack('styles')
    @if($usesServerTable)
        <link rel="stylesheet" href="{{ url('vendor/datatables/datatables.min.css') }}">
        <link rel="stylesheet" href="{{ url('css/admin-data-table.css') }}?v=4">
        <link rel="stylesheet" href="{{ url('css/server-table.css') }}?v=2">
    @endif
    <script src="{{ url('js/pages/admin-dashboard-theme.js') }}?v=3"></script>
    <link rel="stylesheet" href="{{ url('css/admin-dashboard.css') }}?v=3">
    <link rel="stylesheet" href="{{ url('css/admin-pages.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">
    <link rel="stylesheet" href="{{ url('css/member-area.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/interaction-motion.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/table-actions.css') }}?v=1">
    @if($usesWorkspaceTheme)
        <link rel="stylesheet" href="{{ url('css/admin-workspace.css') }}?v=2">
        <link rel="stylesheet" href="{{ url('css/admin-workspace-data.css') }}?v=4">
        <link rel="stylesheet" href="{{ url('css/member-workspace.css') }}?v=2">
    @endif
</head>
<body class="nd-app nd-legacy nm-app{{ $usesWorkspaceTheme ? ' nm-workspace' : '' }}" data-timezone-url="{{ route('account.timezone.update') }}" data-saved-timezone="{{ auth()->user()->timezone }}">
    <a class="nd-skip" href="#main-content">Lewati ke konten utama</a>
    @include('member.partials.sidebar')
    <button class="nd-backdrop" type="button" data-sidebar-close aria-label="Tutup navigasi" hidden></button>
    <div class="nd-workspace">
        @include('admin.dashboard.topbar')
        <main class="nd-main" id="main-content" tabindex="-1">@yield('content')</main>
        <footer class="nd-footer">&copy; {{ date('Y') }} {{ config('app.name') }}</footer>
    </div>
    @stack('modals')
    <script src="{{ asset('js/app.min.js') }}"></script>
    @if($usesServerTable)<script src="{{ url('vendor/datatables/datatables.min.js') }}"></script>@endif
    @stack('plugin-scripts')
    <script src="{{ url('js/app-custom.js') }}?v=5"></script>
    <script src="{{ url('js/interaction-motion.js') }}?v=2"></script>
    <script src="{{ url('js/pages/admin-dashboard.js') }}?v=3"></script>
    <script src="{{ url('js/admin-action-tooltip.js') }}"></script>
    <script src="{{ url('js/pages/member-area.js') }}"></script>
    @if($usesServerTable)
        <script src="{{ url('js/pages/admin-data-table.js') }}?v=10"></script>
        <script src="{{ url('js/remote-modal.js') }}?v=1"></script>
    @endif
    @stack('scripts')
</body>
</html>
