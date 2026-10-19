@switch($column)
    @case('name')
        <strong>{{ $category->name }}</strong>
        @break
    @case('slug')
        {{ $category->slug }}
        @break
    @case('sort')
        {{ $category->sort_order }}
        @break
    @case('courses')
        {{ $category->courses_count }}
        @break
    @case('actions')
        <div class="nc-data-table-actions">
                                    <button type="button" class="nc-table-action nc-table-action--edit" data-remote-modal-url="{{ route('admin.categories.modal', $category) }}" data-modal-id="category-edit-{{ $category->id }}" data-action-tooltip="Ubah kategori" aria-label="Ubah kategori {{ $category->name }}"><x-dashboard.icon name="pencil" /></button>
                                    <button type="button" class="nc-table-action nc-table-action--danger" data-toggle="modal" data-target="#confirm-delete-modal" data-delete-url="{{ route('admin.categories.destroy', $category) }}" data-name="{{ $category->name }}" data-action-tooltip="Hapus kategori" aria-label="Hapus kategori {{ $category->name }}"><x-dashboard.icon name="trash-2" /></button>
                                </div>
        @break
@endswitch
