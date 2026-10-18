<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    @stack('styles')
    <script src="{{ url('js/pages/admin-dashboard-theme.js') }}?v=3"></script>
    <link rel="stylesheet" href="{{ url('css/admin-dashboard.css') }}?v=3">
    <link rel="stylesheet" href="{{ url('css/admin-pages.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">
    <link rel="stylesheet" href="{{ url('css/member-area.css') }}?v=1">
</head>
<body class="nd-app nd-legacy nm-app" data-timezone-url="{{ route('account.timezone.update') }}" data-saved-timezone="{{ auth()->user()->timezone }}">
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
    @stack('plugin-scripts')
    <script src="{{ url('js/app-custom.js') }}?v=3"></script>
    <script src="{{ url('js/pages/admin-dashboard.js') }}?v=2"></script>
    <script src="{{ url('js/admin-action-tooltip.js') }}"></script>
    <script src="{{ url('js/pages/member-area.js') }}"></script>
    @stack('scripts')
</body>
</html>
