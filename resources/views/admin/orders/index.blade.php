@extends('layouts.admin')
@section('title', 'Pesanan')
@push('styles')
<link rel="stylesheet" href="{{ asset('bundles/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-data-table.css') }}?v=3">
@endpush
@section('content')
<x-page-header title="Pesanan" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body nc-data-table-area" data-admin-table-area>
    <x-flash-message />
    <div class="card"><div class="card-header"><h2>Daftar pesanan</h2></div><div class="card-body">
        @include('admin.partials.data-table-filters', [
            'action' => route('admin.orders.index'),
            'idPrefix' => 'orders',
            'searchLabel' => 'Cari pesanan',
            'searchPlaceholder' => 'Nomor, email, atau course…',
            'statusOptions' => ['pending' => 'Menunggu', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Perlu tinjauan'],
        ])
        @if($orders->isEmpty())<p class="nc-data-table-fallback-empty">{{ ($filters['status'] ?? null) || ($filters['q'] ?? null) ? 'Tidak ada pesanan yang sesuai filter.' : 'Belum ada pesanan.' }}</p>@endif
        <p class="nc-data-table-feedback" role="status" data-admin-table-feedback hidden></p>
        <div class="table-responsive"><table class="table nc-admin-table" data-admin-data-table data-source-url="{{ route('admin.orders.index') }}" data-columns="number,member,course,amount,status,attempts,created,actions" data-sortable="0,2,3,4,6" data-order-column="6" data-order-direction="desc" data-display-start="{{ ($orders->currentPage() - 1) * $orders->perPage() }}" data-empty-message="Belum ada pesanan."><thead><tr><th>Nomor</th><th>Member</th><th>Course</th><th>Jumlah</th><th>Status</th><th>Percobaan</th><th>Dibuat</th><th>Aksi</th></tr></thead><tbody>
            @foreach($orders as $order)<tr>
                <td>@include('admin.orders.cells.number')</td><td>@include('admin.orders.cells.member')</td><td>@include('admin.orders.cells.course')</td><td>@include('admin.orders.cells.amount')</td><td>@include('admin.orders.cells.status')</td><td>@include('admin.orders.cells.attempts')</td><td>@include('admin.orders.cells.created')</td><td>@include('admin.orders.cells.actions')</td>
            </tr>@endforeach
        </tbody></table></div>
        @include('admin.partials.data-table-pagination', ['paginator' => $orders])
    </div></div>
</div>
@endsection
@push('plugin-scripts')<script src="{{ asset('bundles/datatables/datatables.min.js') }}"></script>@endpush
@push('scripts')
<script src="{{ url('js/admin-action-tooltip.js') }}"></script>
<script src="{{ url('js/pages/admin-data-table.js') }}?v=3"></script>
@endpush
