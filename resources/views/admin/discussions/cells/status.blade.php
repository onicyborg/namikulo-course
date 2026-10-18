@if($post->trashed())
    <span class="badge badge-secondary">Dihapus</span>
@elseif($post->status === \App\Enums\DiscussionPostStatus::Hidden)
    <span class="badge badge-warning">Disembunyikan</span>
@else
    <span class="badge badge-success">Tampil</span>
@endif
