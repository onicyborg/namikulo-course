<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    @stack('styles')
    <script src="{{ url('js/pages/admin-dashboard-theme.js') }}?v=3"></script>
    <link rel="stylesheet" href="{{ url('css/admin-dashboard.css') }}?v=3">
    <link rel="stylesheet" href="{{ url('css/admin-pages.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/confirm-action-dialog.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">
    <link rel="stylesheet" href="{{ url('css/member-player.css') }}?v=1">
    <link rel="stylesheet" href="{{ url('css/discussion-editor.css') }}?v=5">
    <link rel="stylesheet" href="{{ url('css/lexical-editor.css') }}?v=1">
</head>
<body class="nd-app nd-legacy player-page nm-player" @auth data-timezone-url="{{ route('account.timezone.update') }}" data-saved-timezone="{{ auth()->user()->timezone }}" @endauth>
    <a class="nd-skip" href="#main-content">Lewati ke materi</a>
    <main id="main-content" tabindex="-1">@yield('content')</main>
    <x-confirm-action-dialog />
    <script src="{{ asset('js/app.min.js') }}"></script>
    <script src="{{ url('js/app-custom.js') }}?v=3"></script>
    <script src="{{ url('js/admin-action-tooltip.js') }}"></script>
    @stack('player-scripts')
    @stack('scripts')
</body>
</html>
