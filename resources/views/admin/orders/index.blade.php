@extends('layouts.admin')
@section('title', 'Pesanan')
@section('content')
<x-page-header title="Pesanan" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body"><x-flash-message />
    <div class="card"><div class="card-header"><h2>Daftar pesanan</h2></div><div class="card-body">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="row align-items-end mb-3">
            <div class="col-12 col-md-5"><div class="form-group"><label for="orders-search">Nomor pesanan atau email</label><input id="orders-search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" maxlength="120"></div></div>
            <div class="col-12 col-md-3"><div class="form-group"><label for="orders-status">Status</label><select id="orders-status" name="status" class="form-control"><option value="">Semua status</option>@foreach(['pending' => 'Menunggu', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Perlu tinjauan'] as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div></div>
            <div class="col-12 col-md-4"><div class="form-group"><button type="submit" class="btn btn-primary">Terapkan filter</button><a href="{{ route('admin.orders.index') }}" class="btn btn-light">Reset</a></div></div>
        </form>
        @if($orders->isEmpty())<x-empty-state title="Belum ada pesanan" description="Pesanan baru akan tampil di sini." />@else
            <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Nomor</th><th>Member</th><th>Course</th><th>Jumlah</th><th>Status</th><th>Percobaan</th><th>Aksi</th></tr></thead><tbody>
                @foreach($orders as $order)<tr><td><strong>{{ $order->order_number }}</strong></td><td>{{ $order->user->name }}<small class="d-block text-muted">{{ $order->user->email }}</small></td><td>{{ $order->course_title }}</td><td>Rp {{ number_format($order->amount, 0, ',', '.') }}</td><td><span class="badge badge-{{ $order->status === \App\Enums\OrderStatus::Paid ? 'success' : ($order->status === \App\Enums\OrderStatus::Pending ? 'warning' : 'secondary') }}">{{ ['pending' => 'Menunggu', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Perlu tinjauan'][$order->status->value] }}</span></td><td>{{ $order->payments_count }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.orders.show', $order) }}">Detail</a></td></tr>@endforeach
            </tbody></table></div>
            <div class="mt-3">{{ $orders->links('pagination::bootstrap-4') }}</div>
        @endif
    </div></div>
</div>
@endsection
