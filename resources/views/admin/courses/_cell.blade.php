@switch($column)
    @case('title')
        <div class="content-course-title">@if($course->thumbnail_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($course->thumbnail_path) }}" alt="Thumbnail {{ $course->title }}">@endif<div><strong>{{ $course->title }}</strong><small>{{ $course->instructor_name ?: 'Pengajar belum diisi' }}</small></div></div>
        @break
    @case('category')
        {{ $course->category?->name ?? 'Tanpa kategori' }}
        @break
    @case('price')
        Rp {{ number_format($course->discount_price ?? $course->price, 0, ',', '.') }} @if($course->discount_price !== null)<small class="d-block text-muted"><del>Rp {{ number_format($course->price, 0, ',', '.') }}</del></small>@endif
        @break
    @case('curriculum')
        {{ $course->sections_count }} bab · {{ $course->lessons_count }} materi
        @break
    @case('status')
        <span class="badge badge-{{ $course->status === \App\Enums\CourseStatus::Published ? 'success' : ($course->status === \App\Enums\CourseStatus::Archived ? 'secondary' : 'warning') }}">{{ $course->status->label() }}</span>@if($course->is_featured)<small class="d-block">Unggulan #{{ $course->featured_order }}</small>@endif
        @break
    @case('actions')
        <div class="nc-course-actions">
                        <a class="nc-icon-action nc-table-action nc-table-action--manage" href="{{ route('admin.courses.curriculum', $course) }}" data-action-tooltip="Kelola kurikulum" aria-label="Kelola kurikulum {{ $course->title }}"><x-dashboard.icon name="book-open" /></a>
                        <a class="nc-icon-action nc-table-action nc-table-action--view" href="{{ route('admin.courses.preview', $course) }}" target="_blank" rel="noopener noreferrer" data-action-tooltip="Pratinjau course (tab baru)" aria-label="Pratinjau {{ $course->title }} di tab baru"><x-dashboard.icon name="eye" /></a>
                        <a class="nc-icon-action nc-table-action nc-table-action--manage" href="{{ route('admin.courses.members', $course) }}" data-action-tooltip="Kelola anggota" aria-label="Kelola anggota {{ $course->title }}"><x-dashboard.icon name="users" /></a>
                        <button type="button" class="nc-icon-action nc-table-action nc-table-action--edit" data-remote-modal-url="{{ route('admin.courses.modal', $course) }}" data-modal-id="course-edit-{{ $course->id }}" data-action-tooltip="Ubah course" aria-label="Ubah {{ $course->title }}"><x-dashboard.icon name="pencil" /></button>
                        <button type="button" class="nc-icon-action nc-icon-danger nc-table-action nc-table-action--danger" data-toggle="modal" data-target="#confirm-delete-modal" data-delete-url="{{ route('admin.courses.destroy', $course) }}" data-name="{{ $course->title }}" data-action-tooltip="Hapus course" aria-label="Hapus {{ $course->title }}"><x-dashboard.icon name="trash-2" /></button>
                    </div>
        @break
@endswitch
