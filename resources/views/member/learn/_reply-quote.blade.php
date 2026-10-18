@if($quoted && $quoted->status === \App\Enums\DiscussionPostStatus::Visible)
    <a class="nc-reply-quote" href="{{ route('learn.discussion.thread', [$lesson, $root]) }}?focus={{ $quoted->id }}#discussion-post-{{ $quoted->id }}" data-discussion-quote="discussion-post-{{ $quoted->id }}">
        <span>Membalas <strong>{{ $quoted->user?->name ?? 'Pengguna' }}</strong><x-dashboard.icon name="arrow-up-right" /></span>
        <q>{{ \Illuminate\Support\Str::limit($quoted->plainBody(), 160) }}</q>
    </a>
@else
    <div class="nc-reply-unavailable">Komentar yang dibalas sudah tidak tersedia.</div>
@endif
