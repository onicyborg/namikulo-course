<div class="nm-showcase" aria-label="Gambaran pengalaman belajar di Namikulo">
    <div class="nm-showcase-orbit" aria-hidden="true"></div>
    <div class="nm-learning-window">
        <div class="nm-window-bar"><span><x-dashboard.icon name="book-open" />Ruang belajar</span><span class="nm-window-caption">PRATINJAU TAMPILAN</span></div>
        <div class="nm-window-body">
            <div class="nm-preview-cover">
                @if($spotlight?->thumbnail_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($spotlight->thumbnail_path) }}" alt="{{ $spotlight->title }}" width="640" height="360" fetchpriority="high">@else<div class="nm-cover-art" aria-hidden="true"><x-dashboard.icon name="book-open" /><span>Setiap keahlian dimulai<br>dari rasa ingin tahu.</span></div>@endif
                <span class="nm-cover-label"><x-dashboard.icon name="file-video" />Belajar lewat video</span>
            </div>
            <div class="nm-preview-heading"><span class="nm-overline">{{ $spotlight?->category?->name ?? 'PENGALAMAN NAMIKULO' }}</span><h2>{{ $spotlight?->title ?? 'Satu ruang. Banyak pemahaman baru.' }}</h2></div>
            <div class="nm-preview-tools"><span><x-dashboard.icon name="layers" />Kurikulum terstruktur</span><span><x-dashboard.icon name="message-circle" />Ruang diskusi</span></div>
            <div class="nm-preview-path" aria-hidden="true"><span class="is-done"><x-dashboard.icon name="circle-check" /></span><i></i><span class="is-current">02</span><i></i><span>03</span><i></i><span>04</span><small>Selangkah lebih paham.</small></div>
        </div>
        <div class="nm-window-footer"><span class="nm-live-dot" aria-hidden="true"></span>Materi, progres, dan diskusi dalam satu tempat.</div>
    </div>
    <div class="nm-floating-note"><span class="nm-note-icon"><x-dashboard.icon name="message-square" /></span><div><strong>Jangan berhenti di pertanyaan.</strong><span>Diskusikan langsung di setiap materi.</span></div></div>
    <span class="nm-showcase-caption">Dirancang untuk fokus. Dibuat untuk belajar.</span>
</div>
