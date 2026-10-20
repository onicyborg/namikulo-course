@extends('layouts.admin')

@section('title', 'Moderasi diskusi')
@push('styles')

<link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-discussions.css') }}">
@endpush

@section('content')
<x-page-header title="Moderasi diskusi" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body nc-discussions">
    <x-flash-message />
    <div class="card">
        <div class="card-header"><h2>Kiriman materi</h2></div>
        <div class="card-body">
            <p class="nc-discussions-note">Kiriman tersembunyi tidak terlihat oleh member. Menyembunyikan pertanyaan juga menutup seluruh balasannya.</p>
            <x-server-table :rows="$posts" :columns="['lesson' => 'Materi', 'author' => 'Penulis & waktu', 'body' => 'Kiriman', 'status' => 'Status', 'actions' => 'Aksi']" cells="admin.discussions._cell" record="post" :source="route('admin.discussions.data')" :filters="$filters" sortable="1" order-column="1" direction="desc" :statuses="['visible'=>'Tampil','hidden'=>'Disembunyikan','deleted'=>'Dihapus penulis']" />
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="{{ url('js/admin-action-tooltip.js') }}"></script>
<script src="{{ url('js/pages/discussion.js') }}?v=20"></script>

@endpush
