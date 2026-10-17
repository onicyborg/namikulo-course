@extends('layouts.member')

@section('title', 'Dashboard Member')

@push('styles')
    <link rel="stylesheet" href="{{ url('css/member-dashboard.css') }}">
@endpush

@section('content')
<x-page-header title="Dashboard member" />

<div class="section-body member-dashboard">
    <x-flash-message />

    <div class="card member-dashboard-intro">
        <div class="card-body">
            <div>
                <p class="member-dashboard-eyebrow">Area belajar</p>
                <h2>Halo, {{ auth()->user()->name }}</h2>
                <p>Course dengan akses aktif tersedia di bawah.</p>
            </div>
            <div class="member-dashboard-count" aria-label="{{ $enrollments->total() }} course aktif">
                <strong>{{ $enrollments->total() }}</strong>
                <span>Course aktif</span>
            </div>
        </div>
    </div>

    <section class="card member-dashboard-courses" aria-labelledby="member-courses-title">
        <div class="card-header">
            <div>
                <h2 id="member-courses-title">Course saya</h2>
                <p>Akses aktif yang dapat Anda gunakan saat ini.</p>
            </div>
            <div class="card-header-action"><a href="{{ route('site.courses.index') }}" class="btn btn-outline-primary">Jelajahi katalog</a></div>
        </div>
        <div class="card-body">
            @forelse($enrollments as $enrollment)
                @php($course = $enrollment->course)
                @php($firstLesson = $firstReadyLessons->get($course->id))
                <article class="member-course-row" aria-labelledby="member-course-{{ $course->id }}">
                    <div class="member-course-image">
                        @if($course->thumbnail_path)
                            <img src="{{ url('storage/'.$course->thumbnail_path) }}" alt="Thumbnail course {{ $course->title }}" width="320" height="180" loading="lazy">
                        @else
                            <div class="member-course-image-placeholder" aria-hidden="true"><i data-feather="book-open"></i></div>
                        @endif
                    </div>
                    <div class="member-course-content">
                        <div class="member-course-heading">
                            <div>
                                <span class="member-course-category">{{ $course->category?->name ?? 'Course' }}</span>
                                <h3 id="member-course-{{ $course->id }}">{{ $course->title }}</h3>
                            </div>
                            <span class="badge badge-success">Akses aktif</span>
                        </div>
                        @if($course->summary)<p class="member-course-summary">{{ $course->summary }}</p>@endif
                        <p class="member-course-expiry">Akses hingga <time datetime="{{ $enrollment->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $enrollment->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time></p>
                        <div class="member-course-actions">
                            @if($firstLesson)
                                <a href="{{ route('learn.show', $firstLesson) }}" class="btn btn-primary">Mulai belajar</a>
                            @else
                                <span class="text-muted">Video belum siap diputar.</span>
                            @endif
                            @if($course->status === \App\Enums\CourseStatus::Published)
                                <a href="{{ route('site.courses.show', $course->slug) }}" class="btn btn-outline-primary">Lihat kurikulum</a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <x-empty-state title="Belum ada course aktif" description="Course yang telah diberikan akses akan tampil di sini." icon="book-open" :action-url="route('site.courses.index')" action-label="Jelajahi katalog" />
            @endforelse
        </div>
        @if($enrollments->hasPages())
            <div class="card-footer member-dashboard-pagination">{{ $enrollments->links('pagination::bootstrap-4') }}</div>
        @endif
    </section>
</div>
@endsection
