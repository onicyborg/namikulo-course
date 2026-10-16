@props(['code', 'title', 'message'])

<section class="error-page">
    <div class="container">
        <div class="error-panel">
            <p class="error-code">{{ $code }}</p>
            <h1>{{ $title }}</h1>
            <p>{{ $message }}</p>
            <a href="{{ route('site.home') }}" class="btn btn-primary">Kembali ke Beranda</a>
        </div>
    </div>
</section>
