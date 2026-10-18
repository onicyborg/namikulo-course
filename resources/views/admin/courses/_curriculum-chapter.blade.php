<article class="nc-panel nc-chapter" data-curriculum-item="{{ $section->id }}">
    <header class="nc-chapter-header">
        <button class="nc-grip" type="button" data-drag-handle aria-label="Urutkan bab {{ $section->title }}" aria-describedby="curriculum-order-help"><x-dashboard.icon name="grip-vertical" /></button>
        <div class="nc-heading"><p class="nc-eyebrow">Bab <span data-chapter-number>{{ $chapterNumber }}</span> <span class="nc-count">· {{ $section->lessons->count() }} materi</span></p><h3>{{ $section->title }}</h3>@if($section->description)<x-rich-description class="nc-description" :html="$section->description" />@endif</div>
        @include('admin.courses._curriculum-actions', ['kind' => 'bab', 'record' => $section, 'editTarget' => 'section-edit-'.$section->id, 'deleteUrl' => route('admin.courses.sections.destroy', [$course, $section]), 'deleteName' => 'bab '.$section->title.' dan seluruh materi di dalamnya'])
    </header>
    <div class="nc-lessons" data-curriculum-list data-reorder-url="{{ route('admin.sections.lessons.reorder', $section) }}">
        @forelse($section->lessons as $lesson)
            <div class="nc-lesson" data-curriculum-item="{{ $lesson->id }}">
                <button class="nc-grip" type="button" data-drag-handle aria-label="Urutkan materi {{ $lesson->title }}" aria-describedby="curriculum-order-help"><x-dashboard.icon name="grip-vertical" /></button>
                <span class="nc-video-icon"><x-dashboard.icon name="file-video" /></span>
                <div class="nc-lesson-info"><h4>{{ $lesson->title }}</h4><span class="nc-video-status {{ $lesson->videoAsset?->status === \App\Enums\VideoStatus::Ready ? 'nc-ready' : '' }}">{{ $lesson->videoAsset?->status?->label() ?? 'Video belum disiapkan' }}</span></div>
                <a class="nc-button nc-small nc-prepare" href="{{ route('admin.lessons.video.prepare', $lesson) }}">Siapkan video</a>
                @include('admin.courses._curriculum-actions', ['kind' => 'materi', 'record' => $lesson, 'editTarget' => 'lesson-edit-'.$lesson->id, 'deleteUrl' => route('admin.sections.lessons.destroy', [$section, $lesson]), 'deleteName' => 'materi '.$lesson->title])
            </div>
        @empty
            <p class="nc-no-lessons">Belum ada materi. Tambahkan materi pertama untuk bab ini.</p>
        @endforelse
    </div>
    <footer class="nc-chapter-footer"><button type="button" class="nc-add-lesson" data-toggle="modal" data-target="#lesson-create-{{ $section->id }}"><x-dashboard.icon name="plus" />Tambah materi</button></footer>
</article>
