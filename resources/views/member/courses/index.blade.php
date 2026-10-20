@extends('layouts.member')

@section('title', 'Course Saya')
@push('styles')<link rel="stylesheet" href="{{ url('css/member-learning.css') }}">@endpush

@section('content')
<x-page-header title="Course Saya" />
<div class="section-body member-learning">
    <x-flash-message />
    <div class="card">
        <div class="card-header member-learning-header">
            <div><h2>Course yang pernah Anda ikuti</h2><p>Lihat materi, progres, dan masa akses dalam satu tempat.</p></div>
            <a class="btn btn-outline-primary" href="{{ route('site.courses.index') }}">Jelajahi katalog</a>
        </div>
        <div class="card-body">
            <nav class="member-learning-filters" aria-label="Filter course">
                <a href="{{ route('member.courses.index') }}" class="btn {{ empty($filters['status']) ? 'btn-primary' : 'btn-outline-primary' }}" @if(empty($filters['status'])) aria-current="page" @endif>Semua</a>
                <a href="{{ route('member.courses.index', ['status' => 'active']) }}" class="btn {{ ($filters['status'] ?? null) === 'active' ? 'btn-primary' : 'btn-outline-primary' }}" @if(($filters['status'] ?? null) === 'active') aria-current="page" @endif>Aktif</a>
                <a href="{{ route('member.courses.index', ['status' => 'ended']) }}" class="btn {{ ($filters['status'] ?? null) === 'ended' ? 'btn-primary' : 'btn-outline-primary' }}" @if(($filters['status'] ?? null) === 'ended') aria-current="page" @endif>Akses berakhir</a>
            </nav>
            <div class="nm-course-grid">
            @forelse($courses as $course)
                @php($state = $states->get($course->id))
                @php($itemProgress = $progress->get($course->id, ['total' => 0, 'completed' => 0, 'percent' => 0]))
                <article class="member-learning-row">
                    <div class="member-learning-thumb">
                        @if($course->thumbnail_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($course->thumbnail_path) }}" alt="Thumbnail {{ $course->title }}" width="240" height="135" loading="lazy">@else<x-dashboard.icon name="book-open" />@endif
                    </div>
                    <div class="member-learning-copy">
                        <div class="member-learning-title"><h3><a href="{{ route('member.courses.show', $course->slug) }}">{{ $course->title }}</a></h3><span class="badge {{ $state['active'] ? 'badge-success' : 'badge-secondary' }}">{{ $state['active'] ? 'Akses aktif' : ($state['enrollment']->status === \App\Enums\EnrollmentStatus::Revoked ? 'Akses dicabut' : 'Akses berakhir') }}</span></div>
                        <p>{{ $course->category?->name ?? 'Course' }} · {{ $itemProgress['completed'] }} dari {{ $itemProgress['total'] }} materi selesai</p>
                        <div class="progress" aria-label="Progres belajar {{ $itemProgress['percent'] }} persen"><div class="progress-bar" role="progressbar" style="width: {{ $itemProgress['percent'] }}%" aria-valuenow="{{ $itemProgress['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                        <p class="member-learning-date">Akses hingga <time datetime="{{ $state['enrollment']->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $state['enrollment']->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time></p>
                        <a class="btn btn-outline-primary" href="{{ route('member.courses.show', $course->slug) }}">{{ $state['active'] ? 'Lihat kurikulum' : 'Lihat detail akses' }}</a>
                    </div>
                </article>
            @empty
                <x-empty-state title="Belum ada course" description="Course yang Anda ikuti akan tampil di sini." icon="book-open" :action-url="route('site.courses.index')" action-label="Jelajahi katalog" />
            @endforelse
            </div>
        </div>
        @if($courses->hasPages())<div class="card-footer">{{ $courses->links('pagination::bootstrap-4') }}</div>@endif
    </div>
</div>
@endsection
