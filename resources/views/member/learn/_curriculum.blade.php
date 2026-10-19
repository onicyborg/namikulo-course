<aside class="learn-curriculum nl-curriculum" id="learn-curriculum" aria-labelledby="learn-curriculum-title">
    <div class="nl-curriculum-heading"><div><h2 id="learn-curriculum-title">Isi course</h2><p>{{ $sections->count() }} bab · {{ $readyCount }} materi siap dipelajari</p></div><x-dashboard.icon name="layers" /></div>
    <div class="nl-chapter-list">
        @foreach($sections as $section)
            <details class="nl-chapter" @if($section->id === $lesson->section_id) open @endif>
                <summary><span><small>Bab {{ $loop->iteration }}</small><strong>{{ $section->title }}</strong></span><x-dashboard.icon name="chevron-down" /></summary>
                <ol class="nl-lesson-list">
                    @foreach($section->lessons as $item)
                        @php($ready = $item->videoAsset?->status === \App\Enums\VideoStatus::Ready)
                        @php($unlocked = isset($unlockedIds[$item->id]))
                        <li class="{{ $item->id === $lesson->id ? 'is-current' : '' }}">
                            @if($ready && $unlocked)<a href="{{ route('learn.show', $item) }}" @if($item->id === $lesson->id) aria-current="page" @endif>@else<div class="nl-unavailable" @if($ready) data-locked-lesson="{{ $item->id }}" data-lesson-url="{{ route('learn.show', $item) }}" @endif>@endif
                                <span class="nl-lesson-number" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="nl-lesson-list-copy"><strong>{{ $item->title }}</strong>
                                    @if(! $ready)<span class="learn-lesson-state">Video belum siap</span>
                                    @elseif(! $unlocked)<span class="learn-lesson-state" data-lesson-state="{{ $item->id }}">Terkunci · selesaikan materi sebelumnya</span>
                                    @elseif(! $preview)<span class="learn-lesson-state" data-lesson-state="{{ $item->id }}">{{ $progress->get($item->id)?->completed_at ? 'Selesai' : ($progress->get($item->id)?->last_position_seconds ? 'Sedang dipelajari' : 'Belum dimulai') }}</span>
                                    @endif
                                    @if($item->id === $lesson->id)<span class="nl-current-label">Sedang dibuka</span>@endif
                                </span>
                            @if($ready && $unlocked)</a>@else</div>@endif
                        </li>
                    @endforeach
                </ol>
            </details>
        @endforeach
    </div>
    @unless($preview)<div class="nl-curriculum-footer"><a href="{{ $backUrl }}">Lihat detail course<x-dashboard.icon name="arrow-up-right" /></a></div>@endunless
</aside>
