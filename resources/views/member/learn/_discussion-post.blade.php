<article class="discussion-post {{ $isReply ? 'discussion-reply' : '' }} {{ $post->user?->isAdmin() ? 'nl-instructor-post' : '' }}" id="{{ !empty($inDialog) ? 'dialog-' : '' }}discussion-post-{{ $post->id }}" aria-labelledby="{{ !empty($inDialog) ? 'dialog-' : '' }}discussion-author-{{ $post->id }}" tabindex="-1">
    <div class="discussion-post-meta">
        <span class="nc-discussion-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($post->user?->name ?? 'P', 0, 1)) }}</span>
        <strong id="{{ !empty($inDialog) ? 'dialog-' : '' }}discussion-author-{{ $post->id }}">{{ $post->user?->name ?? 'Pengguna' }}</strong>
        @if($post->user?->isAdmin())<span class="discussion-admin-label">Admin</span>@endif
        @if($post->status === \App\Enums\DiscussionPostStatus::Hidden)<span class="discussion-hidden-label">Disembunyikan</span>@endif
        <time datetime="{{ $post->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $post->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time>
    </div>
    @if($isReply)@include('member.learn._reply-quote', ['quoted' => $post->replyTo])@endif
    <div class="discussion-body"><x-rich-description :html="$post->formattedBody()" /></div>
    <div class="discussion-post-actions">
        @if($post->status === \App\Enums\DiscussionPostStatus::Visible && $root->status === \App\Enums\DiscussionPostStatus::Visible)
            <a href="{{ route('learn.discussion.thread', [$lesson, $root]) }}?reply_to={{ $post->id }}#reply-form" data-discussion-dialog><x-dashboard.icon name="message-square" />Balas</a>
        @endif
        @if(auth()->id() === $post->user_id || auth()->user()->isAdmin())
            <form method="POST" action="{{ route('learn.discussion.destroy', [$lesson, $post]) }}" data-confirm="Kiriman diskusi ini akan dihapus. Tindakan ini tidak dapat dibatalkan." data-confirm-title="Hapus kiriman diskusi" data-confirm-label="Hapus kiriman">
                @csrf @method('DELETE')
                <button type="submit" class="discussion-text-button"><x-dashboard.icon name="trash-2" />Hapus</button>
            </form>
        @endif
    </div>
</article>
