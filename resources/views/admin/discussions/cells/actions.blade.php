@if(! $post->trashed())
    <div class="nc-discussion-actions">
        @if($post->lesson && (! $post->parent_id || $post->root))
            <a class="nc-discussion-action" href="{{ route('learn.discussion.thread', [$post->lesson, $post->root ?? $post]) }}?focus={{ $post->id }}#discussion-post-{{ $post->id }}" aria-label="Lihat kiriman {{ $post->user?->name ?? 'pengguna' }}" data-action-tooltip="Lihat diskusi"><x-dashboard.icon name="eye" /></a>
        @endif
        <form method="POST" action="{{ $post->status === \App\Enums\DiscussionPostStatus::Visible ? route('admin.discussions.hide', $post) : route('admin.discussions.restore', $post) }}" @if($post->status === \App\Enums\DiscussionPostStatus::Visible && ! $post->parent_id) data-confirm="Pertanyaan dan seluruh balasannya akan disembunyikan dari member. Anda dapat memulihkannya nanti." data-confirm-title="Sembunyikan pertanyaan" data-confirm-label="Sembunyikan" data-confirm-tone="warning" @endif>
            @csrf
            @if($post->status === \App\Enums\DiscussionPostStatus::Visible)
                <button type="submit" class="nc-discussion-action nc-discussion-warning" aria-label="Sembunyikan kiriman {{ $post->user?->name ?? 'pengguna' }}" data-action-tooltip="Sembunyikan kiriman"><x-dashboard.icon name="eye-off" /></button>
            @else
                <button type="submit" class="nc-discussion-action" aria-label="Pulihkan kiriman {{ $post->user?->name ?? 'pengguna' }}" data-action-tooltip="Pulihkan kiriman"><x-dashboard.icon name="rotate-ccw" /></button>
            @endif
        </form>
    </div>
@else
    <span class="text-muted" aria-label="Tidak ada aksi untuk kiriman yang dihapus">—</span>
@endif
