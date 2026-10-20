<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Halaman tidak tersedia') | Namikulo Course</title>
    <link rel="icon" type="image/svg+xml" href="{{ url('favicon.svg') }}?v=1">
    <script src="{{ url('js/pages/admin-dashboard-theme.js') }}?v=3"></script>
    <link rel="stylesheet" href="{{ url('css/error-pages.css') }}?v=1">
</head>
<body class="ne-page">
    <a class="ne-skip" href="#error-content">Lewati ke pesan</a>
    <header class="ne-header">
        <a class="ne-brand" href="{{ url('/') }}" aria-label="Namikulo Course, beranda"><span class="ne-brand-mark"><x-dashboard.icon name="book-open" /></span><span>Namikulo<small>COURSE</small></span></a>
        <button class="ne-theme" type="button" data-nc-theme-toggle aria-label="Ganti tema" aria-pressed="false"><span class="ne-sun"><x-dashboard.icon name="sun" /></span><span class="ne-moon"><x-dashboard.icon name="moon" /></span></button>
    </header>
    <main id="error-content" class="ne-main" tabindex="-1">@yield('content')</main>
    <footer class="ne-footer"><span>Namikulo Course</span><a href="{{ url('/') }}">Beranda<x-dashboard.icon name="arrow-up-right" /></a></footer>
</body>
</html>
