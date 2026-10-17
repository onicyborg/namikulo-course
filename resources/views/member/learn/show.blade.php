@extends('layouts.player')

@section('title', 'Belajar · '.$lesson->title)
@push('styles')<link rel="stylesheet" href="{{ url('css/player.css') }}?v=2">@endpush

@section('content')
<div class="learn-shell" id="learn-player" data-start-url="{{ route('learn.playback', $lesson) }}">
    <header class="learn-header"><a href="{{ $backUrl }}" aria-label="Kembali ke course">← <span>Course</span></a><div><small>{{ $lesson->course->title }}</small><strong>{{ $lesson->title }}</strong></div>@if($preview)<span class="learn-preview">Pratinjau admin</span>@endif</header>
    <div class="learn-layout"><div class="learn-main">
        <div class="learn-stage" data-video-stage>
            <video id="course-video" preload="none" playsinline controlsList="nodownload noremoteplayback" disablepictureinpicture disableremoteplayback aria-label="Video {{ $lesson->title }}"></video>
            <div class="learn-watermark" data-watermark aria-hidden="true"></div>
            <div class="learn-screen-message" data-screen-message role="status" aria-live="polite">Menyiapkan pemutar video...</div>
        </div>
        <div class="learn-controls" aria-label="Kontrol video"><button type="button" data-play aria-label="Putar video" disabled>Putar</button><label class="sr-only" for="learn-seek">Posisi video</label><input id="learn-seek" data-seek type="range" min="0" max="100" value="0" disabled><span data-time>0:00 / 0:00</span><label class="sr-only" for="learn-volume">Volume</label><input id="learn-volume" data-volume type="range" min="0" max="100" value="100"><label class="sr-only" for="learn-speed">Kecepatan</label><select id="learn-speed" data-speed aria-label="Kecepatan video"><option value="0.75">0,75×</option><option value="1" selected>1×</option><option value="1.25">1,25×</option><option value="1.5">1,5×</option><option value="2">2×</option></select><button type="button" data-fullscreen aria-label="Layar penuh">Layar penuh</button></div>
        <div class="learn-body"><h1>{{ $lesson->title }}</h1><p class="learn-protection-note">Konten ini dilindungi. Penggunaan ulang tanpa izin dilarang.</p>@if($lesson->description)<p>{{ $lesson->description }}</p>@endif<div class="learn-neighbors">@if($previous)<a href="{{ route('learn.show', $previous) }}">← Materi sebelumnya</a>@else<span></span>@endif @if($next)<a href="{{ route('learn.show', $next) }}">Materi berikutnya →</a>@endif</div></div>
    </div><aside class="learn-curriculum" aria-labelledby="learn-curriculum-title"><h2 id="learn-curriculum-title">Kurikulum</h2>@foreach($sections as $section)<div class="learn-section"><h3>{{ $section->title }}</h3><ol>@foreach($section->lessons as $item)<li class="{{ $item->id === $lesson->id ? 'is-current' : '' }}">@if($item->videoAsset?->status === \App\Enums\VideoStatus::Ready)<a href="{{ route('learn.show', $item) }}" @if($item->id === $lesson->id) aria-current="page" @endif>{{ $item->title }}</a>@else<span>{{ $item->title }} · Belum siap</span>@endif</li>@endforeach</ol></div>@endforeach</aside></div>
</div>
@endsection

@push('player-scripts')<script src="{{ url('vendor/hls/hls.min.js') }}"></script>@endpush
@push('scripts')<script src="{{ url('js/pages/player.js') }}"></script>@endpush
