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
