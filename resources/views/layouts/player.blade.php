<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    <link rel="stylesheet" href="{{ url('css/site.css') }}">
    @stack('styles')
</head>
<body class="player-page">
    <a class="skip-link" href="#main-content">Lewati ke materi</a>
    <main id="main-content" tabindex="-1">@yield('content')</main>
    <script src="{{ asset('js/app.min.js') }}"></script>
    @stack('player-scripts')
    @stack('scripts')
</body>
</html>
