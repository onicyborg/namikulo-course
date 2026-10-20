<nav class="nl-lesson-nav" aria-label="Bagian halaman belajar">
    <a href="#lesson-overview"><x-dashboard.icon name="book-open" />Ringkasan</a>
    <a href="#learn-curriculum"><x-dashboard.icon name="layers" />Kurikulum</a>
    <a href="#discussion"><x-dashboard.icon name="message-square" />Diskusi<span>{{ $discussionThreads->total() }}</span></a>
</nav>
<section class="learn-body nl-lesson-overview" id="lesson-overview" aria-labelledby="lesson-title">
    <h2 class="nl-overview-title">Tentang materi ini</h2>
    @if(! $preview)
        <div class="nl-save-status"><x-dashboard.icon name="circle-check" /><span class="learn-progress-feedback" data-progress-feedback role="status" aria-live="polite">{{ $progress->get($lesson->id)?->completed_at ? 'Materi selesai.' : ($progress->get($lesson->id)?->last_position_seconds ? 'Posisi terakhir akan dipulihkan.' : 'Progres akan tersimpan saat video diputar.') }}</span></div>
    @endif
    @if($lesson->description)<div class="nl-lesson-description"><x-rich-description :html="$lesson->description" /></div>@endif
    @if($lesson->section->description)
        <details class="nc-description-details"><summary>Tentang bab: {{ $lesson->section->title }}</summary><x-rich-description :html="$lesson->section->description" /></details>
    @endif
    <p class="learn-protection-note">Konten ini dilindungi. Penggunaan ulang tanpa izin dilarang.</p>
    <nav class="learn-neighbors" aria-label="Navigasi materi">
        @if($previous)
            <a href="{{ route('learn.show', $previous) }}" class="nl-previous"><x-dashboard.icon name="arrow-right" /><span><small>Materi sebelumnya</small><strong>{{ $previous->title }}</strong></span></a>
        @endif
        @if($next && isset($unlockedIds[$next->id]))
            <a href="{{ route('learn.show', $next) }}" class="nl-next"><span><small>Materi berikutnya</small><strong>{{ $next->title }}</strong></span><x-dashboard.icon name="arrow-right" /></a>
        @elseif($next)
            <div class="nl-next nl-next-locked" data-locked-lesson="{{ $next->id }}" data-lesson-url="{{ route('learn.show', $next) }}"><span><small>Materi berikutnya terkunci</small><strong>{{ $next->title }}</strong><small>Selesaikan materi ini untuk membuka.</small></span><x-dashboard.icon name="arrow-right" /></div>
        @elseif(! $preview)
            <a href="{{ $backUrl }}" class="nl-next"><span><small>Akhir kurikulum</small><strong>Kembali ke course</strong></span><x-dashboard.icon name="arrow-right" /></a>
        @endif
    </nav>
</section>
