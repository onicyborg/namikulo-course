@extends('layouts.admin')
@section('title', 'Kategori')

@push('styles')

<link rel="stylesheet" href="{{ url('css/content-admin.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">
@endpush

@section('content')
<x-page-header title="Kategori" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body content-admin">
    <x-flash-message />
    @if ($errors->any())
        <div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary>Periksa kembali isian kategori: {{ $errors->first() }}</div>
    @endif
    <div class="card">
        <div class="card-header content-card-header">
            <div><h2>Kategori course</h2><p>Kelompokkan course agar mudah ditemukan saat katalog tersedia.</p></div>
            <div class="card-header-action"><button class="btn btn-primary" data-toggle="modal" data-target="#category-create">Tambah kategori</button></div>
        </div>
        <div class="card-body">
            <x-server-table :rows="$categories" :columns="['name' => 'Nama', 'slug' => 'Slug', 'sort' => 'Urutan', 'courses' => 'Course', 'actions' => 'Aksi']" cells="admin.categories._cell" record="category" :source="route('admin.categories.data')" :filters="$filters" sortable="0,1,2,3" order-column="2" direction="asc" />

        </div>
    </div>
</div>

@endsection

@push('modals')
<div class="modal fade" id="category-create" tabindex="-1" role="dialog" aria-labelledby="category-create-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
        <form action="{{ route('admin.categories.store') }}" method="POST" data-loading-form>
            @csrf
            <div class="modal-header"><h2 class="modal-title h5" id="category-create-title">Tambah kategori</h2><button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>
            <div class="modal-body">
                <div class="form-group"><label for="create-category-name">Nama kategori</label><input id="create-category-name" name="name" class="form-control @if(old('_form') === 'create') @error('name') is-invalid @enderror @endif" value="{{ old('_form') === 'create' ? old('name') : '' }}" required>@if(old('_form') === 'create') @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                <div class="form-group"><label for="create-category-slug">Slug <span class="text-muted">(opsional)</span></label><input id="create-category-slug" name="slug" class="form-control @if(old('_form') === 'create') @error('slug') is-invalid @enderror @endif" value="{{ old('_form') === 'create' ? old('slug') : '' }}" placeholder="Otomatis dari nama"><small class="form-text text-muted">Mengubah slug akan mengubah alamat kategori.</small>@if(old('_form') === 'create') @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                <div class="form-group mb-0"><label for="create-category-order">Urutan</label><input id="create-category-order" type="number" min="0" name="sort_order" class="form-control @if(old('_form') === 'create') @error('sort_order') is-invalid @enderror @endif" value="{{ old('_form') === 'create' ? old('sort_order', 0) : 0 }}" required>@if(old('_form') === 'create') @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                <input type="hidden" name="_form" value="create">
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan kategori</button></div>
        </form>
    </div></div>
</div>
@foreach($modalRecords as $category)
@include('admin.categories._edit-modal')
@endforeach
<x-confirm-delete-modal title="Hapus kategori" />
@endpush

@push('scripts')
<script src="{{ url('js/admin-action-tooltip.js') }}"></script>
<script src="{{ url('js/pages/admin-content.js') }}?v=2"></script>
@if ($errors->any() && old('_form'))<script>window.contentModalToOpen = @json(old('_form') === 'create' ? 'category-create' : 'category-edit-'.old('_record'));</script>@endif
@endpush
