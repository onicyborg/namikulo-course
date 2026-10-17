@extends('layouts.member')

@section('title', $course->title)
@push('styles')<link rel="stylesheet" href="{{ url('css/member-learning.css') }}">@endpush

@section('content')
<x-page-header title="Detail course" />
<div class="section-body member-learning">
    <x-flash-message />
    <nav class="member-learning-back"><a href="{{ route('member.courses.index') }}">← Course Saya</a></nav>
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card member-learning-detail">
                <div class="card-body">
                    <div class="member-learning-overline">{{ $course->category?->name ?? 'Course' }} @if($course->status === \App\Enums\CourseStatus::Archived || $course->trashed()) · Diarsipkan @endif</div>
                    <h1>{{ $course->title }}</h1>
                    @if($course->summary)<p>{{ $course->summary }}</p>@endif
                    <div class="member-learning-progress-meta"><strong>{{ $summaryData['percent'] }}% selesai</strong><span>{{ $summaryData['completed'] }} dari {{ $summaryData['total'] }} materi</span></div>
                    <div class="progress" aria-label="Progres belajar {{ $summaryData['percent'] }} persen"><div class="progress-bar" role="progressbar" style="width: {{ $summaryData['percent'] }}%" aria-valuenow="{{ $summaryData['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                    @if($active && ! $course->trashed() && $firstReady)<a class="btn btn-primary mt-3" href="{{ route('learn.show', $firstReady) }}">Buka materi</a>@endif
                </div>
            </div>
            <section class="card" aria-labelledby="member-curriculum-title">
                <div class="card-header"><h2 id="member-curriculum-title">Kurikulum</h2></div>
                <div class="card-body">
                    @forelse($course->sections as $section)
                        <div class="member-learning-section"><h3>{{ $section->title }}</h3>
                            @if($section->description)<p>{{ $section->description }}</p>@endif
                            <ol>
                                @forelse($section->lessons as $lesson)
                                    @php($saved = $lessonProgress->get($lesson->id))
                                    @php($ready = $lesson->videoAsset?->status === \App\Enums\VideoStatus::Ready)
                                    <li><div><strong>{{ $lesson->title }}</strong><small>{{ $saved?->completed_at ? 'Selesai' : ($saved?->last_position_seconds ? 'Sedang dipelajari' : 'Belum dimulai') }} · {{ $ready ? 'Video siap' : 'Video belum siap' }}</small></div>
                                        @if($active && ! $course->trashed() && $ready)<a href="{{ route('learn.show', $lesson) }}" aria-label="Putar {{ $lesson->title }}">Putar</a>@endif
                                    </li>
                                @empty
                                    <li class="text-muted">Belum ada materi pada bab ini.</li>
                                @endforelse
                            </ol>
                        </div>
                    @empty
                        <x-empty-state title="Kurikulum belum tersedia" description="Belum ada materi yang dapat ditampilkan." icon="book-open" />
                    @endforelse
                </div>
            </section>
        </div>
        <div class="col-12 col-lg-4">
            <aside class="card member-learning-access" aria-labelledby="member-access-title"><div class="card-header"><h2 id="member-access-title">Akses course</h2></div><div class="card-body">
                <span class="badge {{ $active ? 'badge-success' : 'badge-secondary' }}">{{ $active ? 'Aktif' : ($current->status === \App\Enums\EnrollmentStatus::Revoked ? 'Dicabut' : 'Berakhir') }}</span>
                <p>Akses hingga <time datetime="{{ $current->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $current->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time>.</p>
                @if($active)
                    @if($course->trashed())<p>Course ini tidak lagi tersedia untuk diputar.</p>@endif
                    @if(! $firstReady)<p>Video course ini belum siap diputar.</p>@endif
                    @if($course->status === \App\Enums\CourseStatus::Archived)<p>Course telah diarsipkan. Akses Anda tetap berlaku sampai masa akses berakhir.</p>@endif
                @elseif($pendingOrder)
                    <a class="btn btn-primary btn-block" href="{{ route('orders.pay', $pendingOrder) }}">Lanjutkan pembayaran</a>
                @elseif($canBuy)
                    <a class="btn btn-primary btn-block" href="{{ route('checkout.show', $course->slug) }}">Beli akses lagi</a>
                @else
                    <p>Course ini belum tersedia untuk pembelian ulang.</p>
                @endif
            </div></aside>
        </div>
    </div>
</div>
@endsection
