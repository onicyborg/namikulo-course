<section class="nc-panel nc-setting">
    <h2>Status course</h2>
    <span class="nc-badge {{ $course->status === \App\Enums\CourseStatus::Published ? 'nc-published' : '' }}">{{ $course->status->label() }}</span>
    <p>Penerbitan membutuhkan thumbnail dan sedikitnya satu video siap.</p>
    @if($course->status === \App\Enums\CourseStatus::Published)
        <form method="POST" action="{{ route('admin.courses.unpublish', $course) }}" data-loading-form>@csrf<button class="nc-button nc-full" type="submit">Kembalikan ke draf</button></form>
        <form method="POST" action="{{ route('admin.courses.archive', $course) }}" data-loading-form>@csrf<button class="nc-button nc-danger nc-full" type="submit">Arsipkan course</button></form>
    @else
        <form method="POST" action="{{ route('admin.courses.publish', $course) }}" data-loading-form>@csrf<button class="nc-button nc-primary nc-full" type="submit">Terbitkan course</button></form>
    @endif
</section>
<section class="nc-panel nc-setting">
    <h2>Course unggulan</h2>
    <p>Pilih course yang ditampilkan sebagai unggulan.</p>
    <form method="POST" action="{{ route('admin.courses.featured', $course) }}" data-loading-form>
        @csrf
        <div class="nc-field"><label for="curriculum-featured">Tampilkan sebagai unggulan</label><select id="curriculum-featured" name="is_featured"><option value="0" @selected(! old('is_featured', $course->is_featured))>Tidak</option><option value="1" @selected(old('is_featured', $course->is_featured))>Ya</option></select>@error('is_featured')<small class="nc-danger">{{ $message }}</small>@enderror</div>
        <div class="nc-field"><label for="curriculum-featured-order">Urutan unggulan</label><input id="curriculum-featured-order" type="number" min="0" name="featured_order" value="{{ old('featured_order', $course->featured_order ?? 0) }}">@error('featured_order')<small class="nc-danger">{{ $message }}</small>@enderror</div>
        <button class="nc-button nc-primary nc-full" type="submit">Simpan unggulan</button>
    </form>
</section>
