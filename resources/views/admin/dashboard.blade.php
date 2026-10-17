@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@push('styles')<link rel="stylesheet" href="{{ url('css/admin-dashboard.css') }}">@endpush

@section('content')
<x-page-header title="Dashboard admin" />
<div class="section-body admin-overview">
    <x-flash-message />
    <div class="admin-overview-intro">
        <div><p class="admin-overview-kicker">Ringkasan operasional</p><h2>Aktivitas platform</h2><p>Pantau akses belajar, pesanan, dan video yang perlu ditangani.</p></div>
        <div class="admin-overview-actions"><a href="{{ route('admin.orders.index') }}" class="btn btn-outline-primary">Lihat pesanan</a><a href="{{ route('admin.operations.index') }}" class="btn btn-primary">Lihat operasi</a></div>
    </div>

    <div class="row admin-kpi-row">
        @foreach([
            ['label' => 'Total user', 'value' => number_format($kpis['users'], 0, ',', '.'), 'icon' => 'users', 'tone' => 'primary'],
            ['label' => 'Course terbit', 'value' => number_format($kpis['published'], 0, ',', '.'), 'icon' => 'book-open', 'tone' => 'info'],
            ['label' => 'Akses aktif', 'value' => number_format($kpis['active_enrollments'], 0, ',', '.'), 'icon' => 'user-check', 'tone' => 'success'],
            ['label' => 'Pendapatan bulan ini', 'value' => 'Rp '.number_format($kpis['revenue'], 0, ',', '.'), 'icon' => 'trending-up', 'tone' => 'primary'],
            ['label' => 'Pesanan menunggu', 'value' => number_format($kpis['pending'], 0, ',', '.'), 'icon' => 'clock', 'tone' => 'warning'],
        ] as $item)
            <div class="col-12 col-sm-6 col-xl"><div class="card card-statistic-1 admin-kpi-card"><div class="card-icon bg-{{ $item['tone'] }}"><i data-feather="{{ $item['icon'] }}" aria-hidden="true"></i></div><div class="card-wrap"><div class="card-header"><h3>{{ $item['label'] }}</h3></div><div class="card-body">{{ $item['value'] }}</div></div></div></div>
        @endforeach
    </div>
    <p class="admin-overview-period">Pendapatan dihitung dari pesanan lunas pada {{ $monthStart->locale('id')->translatedFormat('F Y') }} menurut zona waktu akun Anda.</p>

    <div class="row">
        <div class="col-12 col-xl-7"><section class="card admin-overview-card" aria-labelledby="recent-orders-title"><div class="card-header"><h2 id="recent-orders-title">Pesanan terbaru</h2><div class="card-header-action"><a href="{{ route('admin.orders.index') }}">Semua pesanan</a></div></div><div class="card-body">
            @if($orders->isEmpty())<x-empty-state title="Belum ada pesanan" description="Pesanan baru akan terlihat di sini." icon="shopping-bag" />@else
                <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Pesanan</th><th>Member</th><th>Jumlah</th><th>Status</th><th>Waktu</th><th>Aksi</th></tr></thead><tbody>
                    @foreach($orders as $order)<tr><td><strong>{{ $order->order_number }}</strong><small class="d-block text-muted">{{ $order->course_title }}</small></td><td>{{ $order->user?->name ?? 'Akun dihapus' }}</td><td>Rp {{ number_format($order->amount, 0, ',', '.') }}</td><td><span class="badge badge-{{ $order->status === \App\Enums\OrderStatus::Paid ? 'success' : ($order->status === \App\Enums\OrderStatus::Pending ? 'warning' : 'secondary') }}">{{ ['pending' => 'Menunggu', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Perlu tinjauan'][$order->status->value] ?? $order->status->value }}</span></td><td><time datetime="{{ $order->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time></td><td><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">Detail</a></td></tr>@endforeach
                </tbody></table></div>
            @endif
        </div></section></div>
        <div class="col-12 col-xl-5"><section class="card admin-overview-card" aria-labelledby="failed-videos-title"><div class="card-header"><h2 id="failed-videos-title">Video perlu diperiksa</h2><div class="card-header-action"><a href="{{ route('admin.courses.index') }}">Kelola course</a></div></div><div class="card-body">
            @if($failedVideos->isEmpty())<p class="text-muted mb-0">Tidak ada proses video berstatus gagal.</p>@else
                <ul class="admin-failed-list">@foreach($failedVideos as $item)<li><div><strong>{{ $item['lesson']?->title ?? 'Materi dihapus' }}</strong><small>{{ $item['course']?->title ?? 'Course tidak tersedia' }}</small>@if($item['error'])<p>{{ $item['error'] }}</p>@endif</div>@if($item['lesson'] && $item['course'])<a href="{{ route('admin.lessons.video.prepare', $item['lesson']) }}" aria-label="Periksa video {{ $item['lesson']->title }}">Periksa</a>@endif</li>@endforeach</ul>
            @endif
        </div></section></div>
    </div>
</div>
@endsection
