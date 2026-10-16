@extends('layouts.admin')
@section('title', 'Kategori')

@push('styles')
<link rel="stylesheet" href="{{ asset('bundles/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ url('css/content-admin.css') }}">
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
            @if ($categories->isEmpty())
                <x-empty-state title="Belum ada kategori" description="Tambahkan kategori pertama untuk mulai menyusun course." />
            @else
                <div class="table-responsive">
                    <table class="table table-striped" id="category-table">
                        <thead><tr><th>Nama</th><th>Slug</th><th>Urutan</th><th>Course</th><th>Aksi</th></tr></thead>
                        <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td><strong>{{ $category->name }}</strong></td><td>{{ $category->slug }}</td><td>{{ $category->sort_order }}</td><td>{{ $category->courses_count }}</td>
                                <td class="content-actions">
                                    <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#category-edit-{{ $category->id }}">Ubah</button>
                                    <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#confirm-delete-modal" data-delete-url="{{ route('admin.categories.destroy', $category) }}" data-name="{{ $category->name }}">Hapus</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
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
@foreach ($categories as $category)
<div class="modal fade" id="category-edit-{{ $category->id }}" tabindex="-1" role="dialog" aria-labelledby="category-edit-title-{{ $category->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" data-loading-form>
            @csrf @method('PUT')
            <div class="modal-header"><h2 class="modal-title h5" id="category-edit-title-{{ $category->id }}">Ubah kategori</h2><button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>
            <div class="modal-body">
                @php($editing = old('_form') === 'edit' && old('_record') === $category->id)
                <div class="form-group"><label for="edit-category-name-{{ $category->id }}">Nama kategori</label><input id="edit-category-name-{{ $category->id }}" name="name" class="form-control @if($editing) @error('name') is-invalid @enderror @endif" value="{{ $editing ? old('name') : $category->name }}" required>@if($editing) @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                <div class="form-group"><label for="edit-category-slug-{{ $category->id }}">Slug</label><input id="edit-category-slug-{{ $category->id }}" name="slug" class="form-control @if($editing) @error('slug') is-invalid @enderror @endif" value="{{ $editing ? old('slug') : $category->slug }}" required><small class="form-text text-muted">Mengubah slug akan mengubah alamat kategori.</small>@if($editing) @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                <div class="form-group mb-0"><label for="edit-category-order-{{ $category->id }}">Urutan</label><input id="edit-category-order-{{ $category->id }}" type="number" min="0" name="sort_order" class="form-control @if($editing) @error('sort_order') is-invalid @enderror @endif" value="{{ $editing ? old('sort_order') : $category->sort_order }}" required>@if($editing) @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif</div>
                <input type="hidden" name="_form" value="edit"><input type="hidden" name="_record" value="{{ $category->id }}">
            </div>
            <div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan perubahan</button></div>
        </form>
    </div></div>
</div>
@endforeach
<x-confirm-delete-modal title="Hapus kategori" />
@endpush

@push('plugin-scripts')<script src="{{ asset('bundles/datatables/datatables.min.js') }}"></script>@endpush
@push('scripts')
<script src="{{ url('js/pages/admin-content.js') }}"></script>
@if ($errors->any() && old('_form'))<script>window.contentModalToOpen = @json(old('_form') === 'create' ? 'category-create' : 'category-edit-'.old('_record'));</script>@endif
@endpush
