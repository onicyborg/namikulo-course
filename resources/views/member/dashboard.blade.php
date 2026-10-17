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
                <p>Lanjutkan materi yang sedang Anda pelajari dan pantau masa aksesnya.</p>
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
                @php($courseProgress = $progress->get($course->id, ['total' => 0, 'completed' => 0, 'percent' => 0]))
                @php($daysLeft = max(0, (int) ceil(now()->diffInDays($enrollment->expires_at))))
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
                            <span class="badge {{ $daysLeft < 3 ? 'badge-danger' : ($daysLeft < 14 ? 'badge-warning' : 'badge-success') }}">{{ $daysLeft < 1 ? 'Berakhir hari ini' : $daysLeft.' hari tersisa' }}</span>
                        </div>
                        @if($course->summary)<p class="member-course-summary">{{ $course->summary }}</p>@endif
                        <div class="member-learning-progress-meta"><strong>{{ $courseProgress['percent'] }}% selesai</strong><span>{{ $courseProgress['completed'] }} dari {{ $courseProgress['total'] }} materi</span></div>
                        <div class="progress" aria-label="Progres belajar {{ $courseProgress['percent'] }} persen"><div class="progress-bar" role="progressbar" style="width: {{ $courseProgress['percent'] }}%" aria-valuenow="{{ $courseProgress['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div></div>
                        <p class="member-course-expiry">Akses hingga <time datetime="{{ $enrollment->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $enrollment->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time></p>
                        <div class="member-course-actions">
                            <a href="{{ route('member.courses.show', $course->slug) }}" class="btn btn-primary">{{ $courseProgress['completed'] ? 'Lanjutkan belajar' : 'Lihat materi' }}</a>
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
    <div class="row member-dashboard-secondary">
        <div class="col-12 col-lg-6"><section class="card" aria-labelledby="pending-orders-title"><div class="card-header"><h2 id="pending-orders-title">Pembayaran tertunda</h2><a href="{{ route('member.orders.index') }}">Semua transaksi</a></div><div class="card-body">
            @forelse($pendingOrders as $order)<div class="member-dashboard-small-row"><div><strong>{{ $order->course_title }}</strong><small>{{ $order->order_number }} · Rp {{ number_format($order->amount, 0, ',', '.') }}</small></div><a href="{{ route('orders.pay', $order) }}">Lanjutkan</a></div>@empty<p class="text-muted mb-0">Tidak ada pembayaran yang menunggu.</p>@endforelse
        </div></section></div>
        <div class="col-12 col-lg-6"><section class="card" aria-labelledby="ended-courses-title"><div class="card-header"><h2 id="ended-courses-title">Akses berakhir</h2><a href="{{ route('member.courses.index', ['status' => 'ended']) }}">Course Saya</a></div><div class="card-body">
            @forelse($ended as $item)<div class="member-dashboard-small-row"><div><strong>{{ $item->course->title }}</strong><small>{{ $item->status === \App\Enums\EnrollmentStatus::Revoked ? 'Akses dicabut' : 'Masa akses berakhir' }}</small></div><a href="{{ $item->course->status === \App\Enums\CourseStatus::Published ? route('checkout.show', $item->course->slug) : route('member.courses.show', $item->course->slug) }}">{{ $item->course->status === \App\Enums\CourseStatus::Published ? 'Beli lagi' : 'Detail' }}</a></div>@empty<p class="text-muted mb-0">Belum ada akses yang berakhir.</p>@endforelse
        </div></section></div>
    </div>
    @if($recommendations->isNotEmpty())<section class="card member-dashboard-recommendations" aria-labelledby="recommendations-title"><div class="card-header"><h2 id="recommendations-title">Course lain untuk Anda</h2><a href="{{ route('site.courses.index') }}">Lihat katalog</a></div><div class="card-body"><div class="row">
        @foreach($recommendations as $course)<div class="col-12 col-md-4"><article class="member-recommendation"><h3>{{ $course->title }}</h3><p>{{ $course->summary }}</p><a href="{{ route('site.courses.show', $course->slug) }}">Lihat course</a></article></div>@endforeach
    </div></div></section>@endif
</div>
@endsection
