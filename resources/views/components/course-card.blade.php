@props(['course', 'isActive' => false])
<article class="site-course-card">
    <a class="site-course-card-link" href="{{ route('site.courses.show', $course->slug) }}" aria-label="Lihat course {{ $course->title }}">
        <div class="site-course-image">
            @if($course->thumbnail_path)
                <img src="{{ url('storage/'.$course->thumbnail_path) }}" alt="Thumbnail course {{ $course->title }}" width="640" height="360" loading="lazy">
            @else
                <div class="site-course-placeholder" aria-hidden="true"><span>N</span></div>
            @endif
        </div>
        <div class="site-course-content">
            <span class="site-category">{{ $course->category?->name ?? 'Course' }}</span>
            @if($isActive)<span class="site-enrolled-label">Sedang diikuti</span>@endif
            <h3>{{ $course->title }}</h3>
            <p class="site-course-meta">{{ $course->sections_count }} bab <span aria-hidden="true">·</span> {{ $course->lessons_count }} video <span aria-hidden="true">·</span> {{ $course->lessons_duration_seconds ? floor($course->lessons_duration_seconds / 60).' menit' : 'Durasi belum tersedia' }}</p>
            <div class="site-course-bottom"><div class="site-price">@if($course->discount_price !== null)<del>Rp {{ number_format($course->price, 0, ',', '.') }}</del>@endif<strong>{{ ($course->discount_price ?? $course->price) === 0 ? 'Gratis' : 'Rp '.number_format($course->discount_price ?? $course->price, 0, ',', '.') }}</strong></div><span>Akses {{ $course->access_months }} bulan</span></div>
        </div>
    </a>
</article>
