<section class="nd-panel nd-video-panel" aria-labelledby="videos-title">
    <div class="nd-panel-heading"><div><h2 id="videos-title">Video perlu diperiksa</h2><p>Status pemrosesan materi belajar.</p></div></div>
    @if($failedVideos->isEmpty())
        <div class="nd-empty nd-video-empty"><span class="nd-empty-icon nd-empty-icon--success"><x-dashboard.icon name="circle-check" /></span><h3>Semua proses video lancar</h3><p>Tidak ada video berstatus gagal saat ini.</p></div>
    @else
        <ul class="nd-video-list">
            @foreach($failedVideos as $item)
                <li><div><strong>{{ $item['lesson']?->title ?? 'Materi dihapus' }}</strong><span>{{ $item['course']?->title ?? 'Course tidak tersedia' }}</span>@if($item['error'])<p>{{ $item['error'] }}</p>@endif</div>
                    @if($item['lesson'] && $item['course'])<a class="nd-text-link" href="{{ route('admin.lessons.video.prepare', $item['lesson']) }}" aria-label="Periksa video {{ $item['lesson']->title }}">Periksa<x-dashboard.icon name="arrow-up-right" /></a>@endif
                </li>
            @endforeach
        </ul>
    @endif
    <a class="nd-panel-footer" href="{{ route('admin.operations.index') }}">Buka tugas &amp; log<x-dashboard.icon name="arrow-right" /></a>
</section>
