@extends('layouts.admin')
@section('title', 'Course')
@push('styles')
<link rel="stylesheet" href="{{ asset('bundles/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ asset('bundles/summernote/summernote-bs4.css') }}">
<link rel="stylesheet" href="{{ url('css/content-admin.css') }}">
@endpush
@section('content')
<x-page-header title="Course" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body content-admin">
    <x-flash-message />
    @if($errors->any())<div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary>Perubahan belum disimpan: {{ $errors->first() }}</div>@endif
    <div class="card">
        <div class="card-header content-card-header"><div><h2>Daftar course</h2><p>Kelola informasi, kurikulum, dan status terbit.</p></div><div class="card-header-action"><button class="btn btn-primary" data-toggle="modal" data-target="#course-create">Tambah course</button></div></div>
        <div class="card-body">
            @if($courses->isEmpty())
                <x-empty-state title="Belum ada course" description="Buat course pertama sebagai draf, lalu susun bab dan materi." />
            @else
            <div class="table-responsive"><table class="table table-striped" id="course-table">
                <thead><tr><th>Course</th><th>Kategori</th><th>Harga</th><th>Kurikulum</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>@foreach($courses as $course)
                <tr>
                    <td><div class="content-course-title">@if($course->thumbnail_path)<img src="{{ url('storage/'.$course->thumbnail_path) }}" alt="Thumbnail {{ $course->title }}">@endif<div><strong>{{ $course->title }}</strong><small>{{ $course->instructor_name ?: 'Pengajar belum diisi' }}</small></div></div></td>
                    <td>{{ $course->category?->name ?? 'Tanpa kategori' }}</td>
                    <td>Rp {{ number_format($course->discount_price ?? $course->price, 0, ',', '.') }} @if($course->discount_price !== null)<small class="d-block text-muted"><del>Rp {{ number_format($course->price, 0, ',', '.') }}</del></small>@endif</td>
                    <td>{{ $course->sections_count }} bab · {{ $course->lessons_count }} materi</td>
                    <td><span class="badge badge-{{ $course->status === \App\Enums\CourseStatus::Published ? 'success' : ($course->status === \App\Enums\CourseStatus::Archived ? 'secondary' : 'warning') }}">{{ $course->status->label() }}</span>@if($course->is_featured)<small class="d-block">Unggulan #{{ $course->featured_order }}</small>@endif</td>
                    <td class="content-actions"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.courses.curriculum', $course) }}">Kurikulum</a><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.courses.members', $course) }}">Anggota</a><button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#course-edit-{{ $course->id }}">Ubah</button><button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#confirm-delete-modal" data-delete-url="{{ route('admin.courses.destroy', $course) }}" data-name="{{ $course->title }}">Hapus</button></td>
                </tr>@endforeach</tbody>
            </table></div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('modals')
<div class="modal fade" id="course-create" tabindex="-1" role="dialog" aria-labelledby="course-create-title" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"><div class="modal-content"><form action="{{ route('admin.courses.store') }}" method="POST" enctype="multipart/form-data" data-loading-form>@csrf<div class="modal-header"><h2 class="modal-title h5" id="course-create-title">Tambah course</h2><button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@include('admin.courses._form', ['course' => null])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan draf</button></div></form></div></div></div>
@foreach($courses as $course)
<div class="modal fade" id="course-edit-{{ $course->id }}" tabindex="-1" role="dialog" aria-labelledby="course-edit-title-{{ $course->id }}" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"><div class="modal-content"><form action="{{ route('admin.courses.update', $course) }}" method="POST" enctype="multipart/form-data" data-loading-form>@csrf @method('PUT')<div class="modal-header"><h2 class="modal-title h5" id="course-edit-title-{{ $course->id }}">Ubah course</h2><button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@include('admin.courses._form', ['course' => $course])</div><div class="modal-footer"><button class="btn btn-light" type="button" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan perubahan</button></div></form></div></div></div>
@endforeach
<x-confirm-delete-modal title="Hapus course" />
@endpush
@push('plugin-scripts')<script src="{{ asset('bundles/datatables/datatables.min.js') }}"></script><script src="{{ asset('bundles/summernote/summernote-bs4.js') }}"></script>@endpush
@push('scripts')
<script src="{{ url('js/pages/admin-content.js') }}"></script>
@if($errors->any() && old('_form'))<script>window.contentModalToOpen = @json(old('_form') === 'create' ? 'course-create' : 'course-edit-'.old('_record'));</script>@endif
@endpush
