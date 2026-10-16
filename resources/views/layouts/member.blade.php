<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <link rel="stylesheet" href="{{ asset('css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ url('css/app-custom.css') }}">
    @stack('styles')
</head>
<body class="light light-sidebar theme-white">
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
    <div class="loader" aria-hidden="true"></div>
    <div id="app">
        <div class="main-wrapper main-wrapper-1">
            @include('partials.application-navbar')
            @include('partials.member-sidebar')
            <main class="main-content" id="main-content" tabindex="-1">
                <section class="section">
                    @yield('content')
                </section>
            </main>
            @include('partials.application-footer')
        </div>
    </div>
    <script src="{{ asset('js/app.min.js') }}"></script>
    @stack('plugin-scripts')
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ url('js/app-custom.js') }}"></script>
    @stack('scripts')
</body>
</html>
