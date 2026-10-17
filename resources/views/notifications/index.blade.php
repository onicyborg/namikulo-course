@extends($layout)

@section('title', 'Notifikasi')
@push('styles')<link rel="stylesheet" href="{{ url('css/notifications.css') }}">@endpush

@section('content')
<x-page-header title="Notifikasi" parent="Dashboard" :parentUrl="auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.dashboard')" />
<div class="section-body">
    <div class="card notification-card">
        <div class="card-header notification-card-header">
            <div><h2>Aktivitas terbaru</h2><p class="mb-0 text-muted">{{ auth()->user()->isAdmin() ? 'Pembelian dan aktivitas yang perlu ditinjau.' : 'Pembaruan pesanan dan akses course Anda.' }}</p></div>
            @if($items->total() > 0)
            <form method="POST" action="{{ route($routePrefix.'.read-all') }}">@csrf<button type="submit" class="btn btn-outline-primary">Tandai semua dibaca</button></form>
            @endif
        </div>
        <div class="card-body notification-list">
            @forelse($items as $item)
                <article class="notification-item {{ $item->read_at ? '' : 'notification-item-unread' }}" aria-labelledby="notification-title-{{ $item->id }}">
                    <div class="notification-item-content">
                        <div class="notification-item-heading"><h3 id="notification-title-{{ $item->id }}">{{ $item->title }}</h3>@unless($item->read_at)<span class="badge badge-primary">Baru</span>@endunless</div>
                        <p>{{ $item->summary }}</p>
                        <time datetime="{{ $item->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $item->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time>
                    </div>
                    <div class="notification-item-actions">
                        @if($targets[$item->id])<a class="btn btn-outline-primary" href="{{ $targets[$item->id] }}">Lihat detail</a>@else<span class="text-muted">Detail tidak tersedia</span>@endif
                        @unless($item->read_at)<form method="POST" action="{{ route($routePrefix.'.read', $item) }}">@csrf<button type="submit" class="btn btn-light">Tandai dibaca</button></form>@endunless
                    </div>
                </article>
            @empty
                <x-empty-state title="Belum ada notifikasi" description="Pembaruan pesanan dan akses akan tampil di sini." icon="bell" />
            @endforelse
        </div>
        @if($items->hasPages())<div class="card-footer">{{ $items->links('pagination::bootstrap-4') }}</div>@endif
    </div>
</div>
@endsection
