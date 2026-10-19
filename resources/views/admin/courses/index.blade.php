@extends('layouts.admin')
@section('title', 'Course')
@push('styles')
<link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">

<link rel="stylesheet" href="{{ url('css/lexical-editor.css') }}?v=6">
<link rel="stylesheet" href="{{ url('css/content-admin.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-courses.css') }}">
@endpush
@section('content')
<x-page-header title="Course" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body content-admin">
    <x-flash-message />
    @if($errors->any())<div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary>Perubahan belum disimpan: {{ $errors->first() }}</div>@endif
    <div class="card">
        <div class="card-header content-card-header"><div><h2>Daftar course</h2><p>Kelola informasi, kurikulum, dan status terbit.</p></div><div class="card-header-action"><button class="btn btn-primary" data-toggle="modal" data-target="#course-create">Tambah course</button></div></div>
        <div class="card-body">
            <x-server-table :rows="$courses" :columns="['title' => 'Course', 'category' => 'Kategori', 'price' => 'Harga', 'curriculum' => 'Kurikulum', 'status' => 'Status', 'actions' => 'Aksi']" cells="admin.courses._cell" record="course" :source="route('admin.courses.data')" :filters="$filters" sortable="0,2,4" order-column="" direction="desc" />

        </div>
    </div>
</div>
@endsection

@push('modals')
<div class="modal fade nc-course-modal" id="course-create" tabindex="-1" role="dialog" aria-labelledby="course-create-title" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"><div class="modal-content"><form action="{{ route('admin.courses.store') }}" method="POST" enctype="multipart/form-data" data-loading-form>@csrf<div class="modal-header"><h2 class="modal-title h5" id="course-create-title">Tambah course</h2><button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@include('admin.courses._form', ['course' => null])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan draf</button></div></form></div></div></div>
@foreach($modalRecords as $course)
@include('admin.courses._edit-modal')
@endforeach
<x-confirm-delete-modal title="Hapus course" />
@endpush
@push('plugin-scripts')<script src="{{ url('vendor/lexical/namikulo-editor.js') }}?v=4" defer></script>@endpush
@push('scripts')
<script src="{{ url('js/admin-action-tooltip.js') }}"></script>
<script src="{{ url('js/pages/admin-content.js') }}?v=2"></script>
@if($errors->any() && old('_form'))<script>window.contentModalToOpen = @json(old('_form') === 'create' ? 'course-create' : 'course-edit-'.old('_record'));</script>@endif
@endpush
