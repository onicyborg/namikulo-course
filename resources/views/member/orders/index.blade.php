@extends('layouts.member')

@section('title', 'Transaksi saya')

@push('styles')
    <link rel="stylesheet" href="{{ url('css/member-orders.css') }}">
@endpush

@section('content')
<x-page-header title="Transaksi saya" parent="Dashboard" :parentUrl="route('member.dashboard')" />

<div class="section-body member-orders">
    <x-flash-message />
    <div class="card member-orders-card">
        <div class="card-header">
            <div>
                <h2>Riwayat transaksi</h2>
                <p>Buka kembali tagihan yang aktif dan unduh invoice untuk pesanan lunas.</p>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('member.orders.index') }}" class="member-orders-filter" data-member-auto-filter>
                <div class="nm-filter-field">
                <label for="order-status">Status transaksi</label>
                <select id="order-status" name="status" class="form-control">
                    <option value="">Semua status</option>
                    @foreach(['pending' => 'Menunggu pembayaran', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Perlu tinjauan'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                </div>
                <button type="submit" class="btn btn-primary" data-filter-submit>Terapkan</button>
                <a href="{{ route('member.orders.index') }}" class="btn btn-light nm-icon-action" data-action-tooltip="Reset filter" aria-label="Reset filter"><x-dashboard.icon name="rotate-ccw" /></a>
            </form>

            @if($orders->count())
                <div class="table-responsive">
                    <table class="table table-striped member-orders-table">
                        <thead><tr><th scope="col">Pesanan</th><th scope="col">Tanggal</th><th scope="col">Total</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
                        <tbody>
                        @foreach($orders as $order)
                            @php
                                $status = $order->status->value;
                                $labels = ['pending' => 'Menunggu pembayaran', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Perlu tinjauan'];
                                $badge = ['pending' => 'warning', 'paid' => 'success', 'expired' => 'secondary', 'cancelled' => 'secondary', 'failed' => 'danger', 'refunded' => 'warning'];
                            @endphp
                            <tr>
                                <td><strong class="member-order-title">{{ $order->course_title }}</strong><span class="member-order-number">{{ $order->order_number }}</span></td>
                                <td><time datetime="{{ $order->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time></td>
                                <td class="member-order-amount">Rp {{ number_format($order->amount, 0, ',', '.') }}</td>
                                <td><span class="badge badge-{{ $badge[$status] ?? 'light' }}">{{ $labels[$status] ?? ucfirst($status) }}</span></td>
                                <td class="member-order-actions">
                                    @if($order->status === \App\Enums\OrderStatus::Pending && $order->expires_at?->isFuture())
                                        <a href="{{ route('orders.pay', $order) }}" class="btn btn-primary nm-icon-action" data-action-tooltip="Lanjutkan pembayaran" aria-label="Lanjutkan pembayaran {{ $order->order_number }}"><x-dashboard.icon name="credit-card" /></a>
                                    @else
                                        <a href="{{ route('orders.pay', $order) }}" class="btn btn-outline-primary nm-icon-action" data-action-tooltip="Lihat detail transaksi" aria-label="Lihat detail {{ $order->order_number }}"><x-dashboard.icon name="eye" /></a>
                                    @endif
                                    @if($order->status === \App\Enums\OrderStatus::Paid)
                                        <a href="{{ route('member.orders.invoice', $order) }}" class="btn btn-outline-primary nm-icon-action" data-action-tooltip="Unduh invoice PDF" aria-label="Unduh invoice PDF {{ $order->order_number }}"><x-dashboard.icon name="arrow-down" /></a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                @if($filters['status'] ?? null)
                    <x-empty-state title="Tidak ada transaksi pada status ini" description="Coba lihat semua transaksi untuk menemukan pesanan lain." icon="file-text" :action-url="route('member.orders.index')" action-label="Lihat semua transaksi" />
                @else
                    <x-empty-state title="Belum ada transaksi" description="Pesanan course yang Anda buat akan tampil di sini." icon="file-text" :action-url="route('site.courses.index')" action-label="Lihat katalog" />
                @endif
            @endif
        </div>
        @if($orders->hasPages())<div class="card-footer">{{ $orders->links('pagination::bootstrap-4') }}</div>@endif
    </div>
</div>
@endsection
