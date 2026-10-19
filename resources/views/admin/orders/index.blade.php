@extends('layouts.admin')
@section('title', 'Pesanan')
@push('styles')

<link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">
@endpush
@section('content')
<x-page-header title="Pesanan" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body nc-data-table-area" data-admin-table-area>
    <x-flash-message />
    <div class="card"><div class="card-header"><h2>Daftar pesanan</h2></div><div class="card-body">
        <x-server-table :rows="$orders" :columns="['number' => 'Nomor', 'member' => 'Member', 'course' => 'Course', 'amount' => 'Jumlah', 'status' => 'Status', 'attempts' => 'Percobaan', 'created' => 'Dibuat', 'actions' => 'Aksi']" cells="admin.orders._cell" record="order" :source="route('admin.orders.data')" :filters="$filters" sortable="0,2,3,4,6" order-column="6" direction="desc" :statuses="['pending'=>'Menunggu','paid'=>'Lunas','expired'=>'Kedaluwarsa','cancelled'=>'Dibatalkan','failed'=>'Gagal','refunded'=>'Perlu tinjauan']" />

    </div></div>
</div>
@endsection
@push('scripts')
<script src="{{ url('js/admin-action-tooltip.js') }}"></script>

@endpush
