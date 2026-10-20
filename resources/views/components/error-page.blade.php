@props(['code', 'title', 'message', 'icon' => 'book-open', 'primaryLabel' => 'Kembali ke beranda', 'primaryPath' => '/', 'secondaryLabel' => 'Jelajahi katalog', 'secondaryPath' => '/courses', 'hint' => 'Pilih tujuan di bawah untuk melanjutkan.', 'retry' => false])
<section class="ne-panel" aria-labelledby="error-title">
    <div class="ne-copy">
        <p class="ne-code"><span class="ne-code-line" aria-hidden="true"></span> {{ $code }} <span>/ NAMIKULO COURSE</span></p>
        <h1 id="error-title">{{ $title }}</h1>
        <p class="ne-message">{{ $message }}</p>
        <div class="ne-actions">
            @if($retry && request()->isMethod('GET'))
                <a class="ne-button ne-primary" href="{{ url()->current() }}"><x-dashboard.icon name="rotate-ccw" />Muat ulang halaman</a>
            @else
                <a class="ne-button ne-primary" href="{{ url($primaryPath) }}">{{ $primaryLabel }}<x-dashboard.icon name="arrow-right" /></a>
            @endif
            @if($secondaryLabel)<a class="ne-button ne-secondary" href="{{ url($secondaryPath) }}">{{ $secondaryLabel }}</a>@endif
        </div>
        <p class="ne-hint">{{ $hint }}</p>
    </div>
    <div class="ne-art" aria-hidden="true">
        <div class="ne-orbit"></div>
        <div class="ne-page-back"></div>
        <div class="ne-page-front"><span class="ne-art-icon"><x-dashboard.icon :name="$icon" /></span><span class="ne-art-code">{{ $code }}</span><span class="ne-art-rule"></span><span class="ne-art-rule ne-art-rule-short"></span><span class="ne-art-book"><x-dashboard.icon name="book-open" /></span></div>
        <span class="ne-bookmark"></span>
    </div>
</section>
