<article class="nm-course">
    <a class="nm-course-link" href="{{ route('site.courses.show', $course->slug) }}">
        <div class="nm-course-cover">
            @if($course->thumbnail_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($course->thumbnail_path) }}" alt="Thumbnail {{ $course->title }}" width="640" height="360" loading="lazy">@else<div class="nm-course-art" aria-hidden="true"><x-dashboard.icon name="book-open" /><span>NAMIKULO / COURSE</span></div>@endif
            @if(in_array($course->id, $activeCourseIds, true))<span class="nm-enrolled"><x-dashboard.icon name="circle-check" />Sedang diikuti</span>@endif
        </div>
        <div class="nm-course-body">
            <span class="nm-overline">{{ $course->category?->name ?? 'Course video' }}</span>
            <h3>{{ $course->title }}</h3>
            @if($course->summary)<p class="nm-course-summary">{{ $course->summary }}</p>@endif
            @if($course->instructor_name)<p class="nm-instructor"><span aria-hidden="true">{{ mb_substr($course->instructor_name, 0, 1) }}</span>{{ $course->instructor_name }}</p>@endif
            <div class="nm-course-meta"><span><x-dashboard.icon name="layers" />{{ $course->sections_count }} bab</span><span><x-dashboard.icon name="file-video" />{{ $course->lessons_count }} video</span><span><x-dashboard.icon name="clock-3" />Akses {{ $course->access_months }} bulan</span></div>
            <div class="nm-course-bottom"><div class="nm-price">@if($course->discount_price !== null)<del>Rp {{ number_format($course->price, 0, ',', '.') }}</del>@endif<strong>{{ ($course->discount_price ?? $course->price) === 0 ? 'Gratis' : 'Rp '.number_format($course->discount_price ?? $course->price, 0, ',', '.') }}</strong></div><span class="nm-course-action">Lihat course<x-dashboard.icon name="arrow-up-right" /></span></div>
        </div>
    </a>
</article>
