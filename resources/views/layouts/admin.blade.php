<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    @unless(request()->routeIs('admin.dashboard'))
        <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
        @stack('styles')
    @endunless
    <script src="{{ url('js/pages/admin-dashboard-theme.js') }}?v=3"></script>
    <link rel="stylesheet" href="{{ url('css/admin-dashboard.css') }}?v=3">
    <link rel="stylesheet" href="{{ url('css/confirm-action-dialog.css') }}?v=1">
    @unless(request()->routeIs('admin.dashboard'))<link rel="stylesheet" href="{{ url('css/admin-pages.css') }}?v=1">@endunless
</head>
<body class="nd-app{{ request()->routeIs('admin.dashboard') ? '' : ' nd-legacy' }}" data-timezone-url="{{ route('account.timezone.update') }}" data-saved-timezone="{{ auth()->user()->timezone }}">
    <a class="nd-skip" href="#main-content">Lewati ke konten utama</a>
    @include('admin.dashboard.sidebar')
    <button class="nd-backdrop" type="button" data-sidebar-close aria-label="Tutup navigasi" hidden></button>
    <div class="nd-workspace">
        @include('admin.dashboard.topbar')
        <main class="nd-main" id="main-content" tabindex="-1">@yield('content')</main>
        <footer class="nd-footer">&copy; {{ date('Y') }} {{ config('app.name') }}</footer>
    </div>
    @stack('modals')
    <x-confirm-action-dialog />
    @unless(request()->routeIs('admin.dashboard'))
        <script src="{{ asset('js/app.min.js') }}"></script>
        @stack('plugin-scripts')
    @endunless
    <script src="{{ url('js/app-custom.js') }}?v=3"></script>
    <script src="{{ url('js/pages/admin-dashboard.js') }}?v=2"></script>
    @unless(request()->routeIs('admin.dashboard'))<script src="{{ url('js/pages/admin-legacy.js') }}"></script>@endunless
    @stack('scripts')
</body>
</html>
