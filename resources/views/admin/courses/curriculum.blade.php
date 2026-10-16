@extends('layouts.admin')
@section('title', 'Kurikulum · '.$course->title)
@push('styles')<link rel="stylesheet" href="{{ url('css/content-admin.css') }}">@endpush
@section('content')
<x-page-header title="Kurikulum course" parent="Course" :parentUrl="route('admin.courses.index')" />
<div class="section-body content-admin content-admin--curriculum">
    <x-flash-message />
    @if($errors->any())<div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary>Perubahan belum disimpan: {{ $errors->first() }}</div>@endif
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card"><div class="card-header content-card-header"><div><p class="content-kicker">KURIKULUM</p><h2>{{ $course->title }}</h2><p>Susun bab dan materi. Video dapat disiapkan pada tahap pengembangan berikutnya.</p></div><div class="card-header-action"><a class="btn btn-outline-primary mr-2" href="{{ route('admin.courses.members', $course) }}">Anggota</a><button class="btn btn-primary" data-toggle="modal" data-target="#section-create">Tambah bab</button></div></div></div>
            @if($course->sections->isEmpty())
                <div class="card"><div class="card-body"><x-empty-state title="Belum ada bab" description="Tambahkan bab pertama, lalu masukkan materi di dalamnya." /></div></div>
            @else
                    <div data-sort-list="sections">
                    @foreach($course->sections as $section)
                        <div class="card content-section" data-sort-id="{{ $section->id }}">
                            <div class="card-header content-card-header"><div><p class="content-kicker">BAB {{ $loop->iteration }}</p><h3>{{ $section->title }}</h3>@if($section->description)<p>{{ $section->description }}</p>@endif</div><div class="content-order-controls"><button type="button" class="btn btn-sm btn-light" data-move="up" aria-label="Naikkan bab {{ $section->title }}">↑</button><button type="button" class="btn btn-sm btn-light" data-move="down" aria-label="Turunkan bab {{ $section->title }}">↓</button></div></div>
                            <div class="card-body">
                                <div class="content-section-actions"><button class="btn btn-sm btn-outline-primary" type="button" data-toggle="modal" data-target="#lesson-create-{{ $section->id }}">Tambah materi</button><button class="btn btn-sm btn-outline-primary" type="button" data-toggle="modal" data-target="#section-edit-{{ $section->id }}">Ubah bab</button><button class="btn btn-sm btn-outline-danger" type="button" data-toggle="modal" data-target="#confirm-delete-modal" data-delete-url="{{ route('admin.courses.sections.destroy', [$course, $section]) }}" data-name="bab {{ $section->title }} dan seluruh materi di dalamnya">Hapus bab</button></div>
                                @if($section->lessons->isEmpty())<p class="content-empty-line">Belum ada materi dalam bab ini.</p>@else
                                    <div class="content-lesson-list" data-sort-list="lessons">
                                    @foreach($section->lessons as $lesson)
                                        <div class="content-lesson" data-sort-id="{{ $lesson->id }}"><div><strong>{{ $lesson->title }}</strong><small>{{ $lesson->videoAsset?->status?->label() ?? 'Video belum disiapkan' }}</small></div><div class="content-actions"><button type="button" class="btn btn-sm btn-light" data-move="up" aria-label="Naikkan materi {{ $lesson->title }}">↑</button><button type="button" class="btn btn-sm btn-light" data-move="down" aria-label="Turunkan materi {{ $lesson->title }}">↓</button><button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#lesson-edit-{{ $lesson->id }}">Ubah</button><button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#confirm-delete-modal" data-delete-url="{{ route('admin.sections.lessons.destroy', [$section, $lesson]) }}" data-name="materi {{ $lesson->title }}">Hapus</button></div></div>
                                    @endforeach
                                    </div>
                                    <form class="content-order-form" method="POST" action="{{ route('admin.sections.lessons.reorder', $section) }}" data-order-source="lessons" data-loading-form>@csrf<button class="btn btn-sm btn-outline-primary" type="submit">Simpan urutan materi</button></form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    </div>
                    <form id="section-order-form" method="POST" action="{{ route('admin.courses.sections.reorder', $course) }}" data-order-source="sections" data-loading-form>@csrf<button class="btn btn-outline-primary mb-4" type="submit">Simpan urutan bab</button></form>
            @endif
        </div>
        <div class="col-12 col-lg-4">
            <div class="card"><div class="card-header"><h2>Status course</h2></div><div class="card-body">
                <p><span class="badge badge-{{ $course->status === \App\Enums\CourseStatus::Published ? 'success' : 'warning' }}">{{ $course->status->label() }}</span></p>
                <p class="text-muted">Penerbitan membutuhkan thumbnail dan sedikitnya satu video siap. Video baru tersedia setelah tahap pemrosesan video.</p>
                @if($course->status !== \App\Enums\CourseStatus::Published)<form method="POST" action="{{ route('admin.courses.publish', $course) }}" data-loading-form>@csrf<button class="btn btn-primary btn-block" type="submit">Terbitkan course</button></form>@else<form method="POST" action="{{ route('admin.courses.unpublish', $course) }}" data-loading-form>@csrf<button class="btn btn-outline-primary btn-block" type="submit">Kembalikan ke draf</button></form>@endif
                @if($course->status === \App\Enums\CourseStatus::Published)<form method="POST" action="{{ route('admin.courses.archive', $course) }}" data-loading-form class="mt-2">@csrf<button class="btn btn-outline-secondary btn-block" type="submit">Arsipkan course</button></form>@endif
            </div></div>
            <div class="card"><div class="card-header"><h2>Course unggulan</h2></div><div class="card-body"><form method="POST" action="{{ route('admin.courses.featured', $course) }}" data-loading-form>@csrf<div class="form-group"><label for="featured-{{ $course->id }}">Tampilkan sebagai unggulan</label><select id="featured-{{ $course->id }}" name="is_featured" class="form-control"><option value="0" @selected(! $course->is_featured)>Tidak</option><option value="1" @selected($course->is_featured)>Ya</option></select></div><div class="form-group"><label for="featured-order-{{ $course->id }}">Urutan unggulan</label><input id="featured-order-{{ $course->id }}" type="number" min="0" name="featured_order" class="form-control" value="{{ $course->featured_order ?? 0 }}"></div><button class="btn btn-outline-primary btn-block" type="submit">Simpan unggulan</button></form></div></div>
        </div>
    </div>
</div>

@endsection

@push('modals')
<div class="modal fade" id="section-create" tabindex="-1" role="dialog" aria-labelledby="section-create-title" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="POST" action="{{ route('admin.courses.sections.store', $course) }}" data-loading-form>@csrf<div class="modal-header"><h2 class="modal-title h5" id="section-create-title">Tambah bab</h2><button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@include('admin.courses._curriculum-fields', ['prefix' => 'section-create', 'record' => null, 'kind' => 'section'])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan bab</button></div></form></div></div></div>
@foreach($course->sections as $section)
<div class="modal fade" id="section-edit-{{ $section->id }}" tabindex="-1" role="dialog" aria-labelledby="section-edit-title-{{ $section->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="POST" action="{{ route('admin.courses.sections.update', [$course, $section]) }}" data-loading-form>@csrf @method('PUT')<div class="modal-header"><h2 class="modal-title h5" id="section-edit-title-{{ $section->id }}">Ubah bab</h2><button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@include('admin.courses._curriculum-fields', ['prefix' => 'section-edit-'.$section->id, 'record' => $section, 'kind' => 'section'])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan perubahan</button></div></form></div></div></div>
<div class="modal fade" id="lesson-create-{{ $section->id }}" tabindex="-1" role="dialog" aria-labelledby="lesson-create-title-{{ $section->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="POST" action="{{ route('admin.sections.lessons.store', $section) }}" data-loading-form>@csrf<div class="modal-header"><h2 class="modal-title h5" id="lesson-create-title-{{ $section->id }}">Tambah materi · {{ $section->title }}</h2><button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@include('admin.courses._curriculum-fields', ['prefix' => 'lesson-create-'.$section->id, 'record' => null, 'kind' => 'lesson'])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan materi</button></div></form></div></div></div>
@foreach($section->lessons as $lesson)
<div class="modal fade" id="lesson-edit-{{ $lesson->id }}" tabindex="-1" role="dialog" aria-labelledby="lesson-edit-title-{{ $lesson->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered" role="document"><div class="modal-content"><form method="POST" action="{{ route('admin.sections.lessons.update', [$section, $lesson]) }}" data-loading-form>@csrf @method('PUT')<div class="modal-header"><h2 class="modal-title h5" id="lesson-edit-title-{{ $lesson->id }}">Ubah materi</h2><button class="close" type="button" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@include('admin.courses._curriculum-fields', ['prefix' => 'lesson-edit-'.$lesson->id, 'record' => $lesson, 'kind' => 'lesson'])</div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><button class="btn btn-primary" type="submit">Simpan perubahan</button></div></form></div></div></div>
@endforeach
@endforeach
<x-confirm-delete-modal title="Hapus konten" />
@endpush
@push('scripts')<script src="{{ url('js/pages/admin-content.js') }}"></script>@if($errors->any() && old('_modal'))<script>window.contentModalToOpen = @json(old('_modal'));</script>@endif @endpush
