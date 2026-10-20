@extends('layouts.player')

@section('title', 'Belajar · '.$lesson->title)
@push('styles')<link rel="stylesheet" href="{{ url('css/player.css') }}?v=2"><link rel="stylesheet" href="{{ url('css/discussion.css') }}?v=2"><link rel="stylesheet" href="{{ url('css/learning-room.css') }}?v=2">@endpush

@section('content')
<div class="learn-shell" id="learn-player" data-start-url="{{ route('learn.playback', $lesson) }}" data-resume-seconds="{{ $progress->get($lesson->id)?->completed_at ? 0 : ($progress->get($lesson->id)?->last_position_seconds ?? 0) }}">
    <header class="learn-header learn-header--course">
        @unless($preview)<a class="nd-icon-button nm-back" href="{{ $backUrl }}" aria-label="Kembali ke course" data-action-tooltip="Kembali ke course"><x-dashboard.icon name="arrow-right" /></a>@endunless
        <div class="nm-learn-context"><small>RUANG BELAJAR</small><strong>{{ $lesson->course->title }}</strong></div>
        @if($preview)<span class="learn-preview">Pratinjau admin</span>@endif
        <div class="nm-learn-actions"><x-theme-toggle /><a class="nd-icon-button" href="{{ auth()->user()->isAdmin() ? route('admin.notifications.index') : route('member.notifications.index') }}" aria-label="Notifikasi" data-action-tooltip="Notifikasi"><x-dashboard.icon name="bell" /></a></div>
    </header>
    <div class="learn-layout learn-workspace">
        <div class="learn-main">
            @include('member.learn._workspace-heading')
            @include('member.learn._player')
            @include('member.learn._lesson-overview')
        </div>
        @include('member.learn._curriculum')
        @include('member.learn._discussion')
    </div>
</div>
@endsection
@push('modals')@include('member.learn._discussion-modal')@endpush

@push('player-scripts')<script src="{{ url('vendor/hls/hls.min.js') }}"></script>@endpush
@push('scripts')<script src="{{ url('js/pages/player.js') }}?v=2"></script><script src="{{ url('js/pages/discussion.js') }}?v=6"></script>@endpush
