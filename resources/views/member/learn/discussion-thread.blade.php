@extends('layouts.player')

@section('title', 'Diskusi · '.$lesson->title)
@push('styles')<link rel="stylesheet" href="{{ url('css/player.css') }}?v=2"><link rel="stylesheet" href="{{ url('css/discussion.css') }}">@endpush

@section('content')
<div class="discussion-page">
    <header class="discussion-page-header"><a href="{{ route('learn.show', $lesson) }}#discussion">← Kembali ke materi</a><span>{{ $lesson->title }}</span></header>
    <div class="discussion-page-main">
        <h1>Diskusi materi</h1>
        <x-flash-message />
        <div class="discussion-thread">
            @include('member.learn._discussion-post', ['post' => $root, 'root' => $root, 'isReply' => false])
            @foreach($replies as $reply)
                @include('member.learn._discussion-post', ['post' => $reply, 'root' => $root, 'isReply' => true])
            @endforeach
        </div>
        @if($replies->hasPages())<div class="discussion-pagination">{{ $replies->withQueryString()->links('pagination::bootstrap-4') }}</div>@endif
        @if($root->status === \App\Enums\DiscussionPostStatus::Visible)
        <form method="POST" action="{{ route('learn.discussion.store', $lesson) }}" class="discussion-form" id="reply-form">
            @csrf
            <input type="hidden" name="parent_id" value="{{ $root->id }}">
            <input type="hidden" name="reply_to_id" value="{{ $replyTo?->id ?? $root->id }}">
            <h2>Tulis balasan</h2>
            @if($replyTo && $replyTo->id !== $root->id)<p class="discussion-context">Membalas {{ $replyTo->user?->name ?? 'komentar' }}</p>@endif
            <div class="form-group">
                <label for="reply-body">Balasan Anda</label>
                <textarea id="reply-body" name="body" rows="4" minlength="10" maxlength="2000" required class="form-control @error('body') is-invalid @enderror" aria-describedby="reply-help @error('body') reply-error @enderror">{{ old('body') }}</textarea>
                <small id="reply-help" class="form-text">10–2.000 karakter. Balasan dapat dibaca semua member yang memiliki akses.</small>
                @error('body')<div id="reply-error" class="invalid-feedback">{{ $message }}</div>@enderror
                @error('parent_id')<div class="text-danger">{{ $message }}</div>@enderror
                @error('reply_to_id')<div class="text-danger">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary" type="submit">Kirim balasan</button>
        </form>
        @else<p class="discussion-empty">Pertanyaan ini disembunyikan. Pulihkan sebelum menambah balasan.</p>@endif
    </div>
</div>
@endsection
@push('scripts')<script src="{{ url('js/pages/discussion.js') }}"></script>@endpush
