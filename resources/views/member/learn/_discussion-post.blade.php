<article class="discussion-post {{ $isReply ? 'discussion-reply' : '' }}" id="discussion-post-{{ $post->id }}" aria-labelledby="discussion-author-{{ $post->id }}">
    <div class="discussion-post-meta">
        <strong id="discussion-author-{{ $post->id }}">{{ $post->user?->name ?? 'Pengguna' }}</strong>
        @if($post->user?->isAdmin())<span class="discussion-admin-label">Admin</span>@endif
        @if($post->status === \App\Enums\DiscussionPostStatus::Hidden)<span class="discussion-hidden-label">Disembunyikan</span>@endif
        <time datetime="{{ $post->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $post->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time>
    </div>
    @if($isReply && $post->replyTo && $post->reply_to_id !== $root->id)
        <p class="discussion-context">Membalas {{ $post->replyTo->user?->name ?? 'komentar' }}</p>
    @endif
    <p class="discussion-body">{{ $post->body }}</p>
    <div class="discussion-post-actions">
        @if($post->status === \App\Enums\DiscussionPostStatus::Visible && $root->status === \App\Enums\DiscussionPostStatus::Visible)
            <a href="{{ route('learn.discussion.thread', [$lesson, $root]) }}?reply_to={{ $post->id }}#reply-form">Balas</a>
        @endif
        @if(auth()->id() === $post->user_id || auth()->user()->isAdmin())
            <form method="POST" action="{{ route('learn.discussion.destroy', [$lesson, $post]) }}" data-confirm="Hapus kiriman diskusi ini?">
                @csrf @method('DELETE')
                <button type="submit" class="discussion-text-button">Hapus</button>
            </form>
        @endif
    </div>
</article>
