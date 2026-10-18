@extends('layouts.admin')
@section('title', 'User')
@push('styles')
<link rel="stylesheet" href="{{ asset('bundles/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ url('css/content-admin.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-data-table.css') }}?v=3">
<link rel="stylesheet" href="{{ url('css/admin-users.css') }}?v=1">
@endpush
@section('content')
<x-page-header title="User" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body content-admin nc-users"><x-flash-message />
    @if($errors->any())<div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary>Perubahan belum disimpan: {{ $errors->first() }}</div>@endif
    <div class="card"><div class="card-header content-card-header"><div><h2>Daftar user</h2><p>Kelola akun, peran, dan akses login.</p></div><div class="card-header-action"><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#user-create">Tambah user</button></div></div><div class="card-body">
        @include('admin.users._filters')
        @if($users->isEmpty())<x-empty-state title="Tidak ada user" description="Ubah filter atau tambahkan user baru." />@else
        <div class="table-responsive"><table class="table table-striped" @unless($serverSide) id="users-table" @endunless><thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Status</th><th>Akses</th><th>Aksi</th></tr></thead><tbody>
            @foreach($users as $user)<tr><td><strong>{{ $user->name }}</strong></td><td>{{ $user->email }}</td><td>{{ $user->role->label() }}</td><td><span class="badge badge-{{ $user->is_active ? 'success' : 'secondary' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>{{ $user->enrollments_count }}</td><td><div class="nc-data-table-actions">
                <button type="button" class="nc-data-table-action" data-toggle="modal" data-target="#user-edit-{{ $user->id }}" aria-label="Ubah user {{ $user->name }}" data-action-tooltip="Ubah user"><x-dashboard.icon name="pencil" /></button>
                @if($user->id !== auth()->id())<form method="POST" action="{{ route('admin.users.toggle-active', $user) }}" data-loading-form>@csrf<button type="submit" class="nc-data-table-action {{ $user->is_active ? 'nc-data-table-action--danger' : 'nc-data-table-action--success' }}" aria-label="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} user {{ $user->name }}" data-action-tooltip="{{ $user->is_active ? 'Nonaktifkan user' : 'Aktifkan user' }}" data-loading-text="…"><x-dashboard.icon :name="$user->is_active ? 'x' : 'circle-check'" /></button></form>@endif
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}" data-loading-form>@csrf<button type="submit" class="nc-data-table-action" aria-label="Kirim tautan reset password ke {{ $user->name }}" data-action-tooltip="Kirim tautan reset password" data-loading-text="…"><x-dashboard.icon name="rotate-ccw" /></button></form>
            </div></td></tr>@endforeach
        </tbody></table></div>
        @if($serverSide)@include('admin.partials.data-table-pagination', ['paginator' => $users])@endif
        @unless($serverSide)<div class="nc-user-table-footer" data-user-table-footer></div>@endunless
        @endif
    </div></div>
</div>
@endsection
@push('modals')
<div class="modal fade" id="user-create" tabindex="-1" role="dialog" aria-labelledby="user-create-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="POST" action="{{ route('admin.users.store') }}" data-loading-form>@csrf<div class="modal-header"><h2 class="modal-title h5" id="user-create-title">Tambah user</h2><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@include('admin.users._form', ['user' => null])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan user</button></div></form></div></div></div>
@foreach($users as $user)<div class="modal fade" id="user-edit-{{ $user->id }}" tabindex="-1" role="dialog" aria-labelledby="user-edit-title-{{ $user->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="POST" action="{{ route('admin.users.update', $user) }}" data-loading-form>@csrf @method('PUT')<div class="modal-header"><h2 class="modal-title h5" id="user-edit-title-{{ $user->id }}">Ubah user</h2><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@include('admin.users._form', ['user' => $user])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan perubahan</button></div></form></div></div></div>@endforeach
@endpush
@push('plugin-scripts')<script src="{{ asset('bundles/datatables/datatables.min.js') }}"></script>@endpush
@push('scripts')<script src="{{ url('js/admin-action-tooltip.js') }}"></script><script src="{{ url('js/pages/admin-content.js') }}"></script><script src="{{ url('js/pages/admin-users.js') }}?v=1"></script>@if($errors->any() && old('_form'))<script>window.contentModalToOpen = @json(old('_form') === 'create' ? 'user-create' : 'user-edit-'.old('_record'));</script>@endif @endpush
