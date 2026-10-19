@extends('layouts.admin')
@section('title', 'User')
@push('styles')

<link rel="stylesheet" href="{{ url('css/content-admin.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-users.css') }}?v=1">
@endpush
@section('content')
<x-page-header title="User" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body content-admin nc-users"><x-flash-message />
    @if($errors->any())<div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary>Perubahan belum disimpan: {{ $errors->first() }}</div>@endif
    <div class="card"><div class="card-header content-card-header"><div><h2>Daftar user</h2><p>Kelola akun, peran, dan akses login.</p></div><div class="card-header-action"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#user-create">Tambah user</button></div></div><div class="card-body">
            <x-server-table :rows="$users" :columns="['name' => 'Nama', 'email' => 'Email', 'role' => 'Peran', 'status' => 'Status', 'access' => 'Akses', 'actions' => 'Aksi']" cells="admin.users._cell" record="user" :source="route('admin.users.data')" :filters="$filters" sortable="0,1,2,3,4" order-column="0" direction="asc" :statuses="['active'=>'Aktif','inactive'=>'Nonaktif']" :roles="['admin'=>'Admin','member'=>'Member']" />
    </div></div>
</div>
@endsection
@push('modals')
<div class="modal fade" id="user-create" tabindex="-1" role="dialog" aria-labelledby="user-create-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="POST" action="{{ route('admin.users.store') }}" data-loading-form>@csrf<div class="modal-header"><h2 class="modal-title h5" id="user-create-title">Tambah user</h2><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@include('admin.users._form', ['user' => null])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan user</button></div></form></div></div></div>
@foreach($modalRecords as $user)
@include('admin.users._edit-modal')
@endforeach
@endpush
@push('scripts')<script src="{{ url('js/admin-action-tooltip.js') }}"></script><script src="{{ url('js/pages/admin-content.js') }}?v=2"></script>@if($errors->any() && old('_form'))<script>window.contentModalToOpen = @json(old('_form') === 'create' ? 'user-create' : 'user-edit-'.old('_record'));</script>@endif @endpush
