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
            <x-server-table :rows="$orders" :columns="['order' => 'Pesanan', 'created' => 'Tanggal', 'amount' => 'Total', 'status' => 'Status', 'actions' => 'Aksi']" cells="member.orders._cell" record="order" :source="route('member.orders.data')" :filters="$filters" sortable="0,1,2,3" order-column="1" direction="desc" :statuses="['pending'=>'Menunggu','paid'=>'Lunas','expired'=>'Kedaluwarsa','cancelled'=>'Dibatalkan','failed'=>'Gagal','refunded'=>'Perlu tinjauan']" />
        </div>
    </div>
</div>
@endsection
