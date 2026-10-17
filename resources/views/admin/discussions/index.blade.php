@extends('layouts.admin')

@section('title', 'Moderasi diskusi')

@section('content')
<x-page-header title="Moderasi diskusi" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body">
    <x-flash-message />
    <div class="card">
        <div class="card-header"><h2>Kiriman materi</h2><div class="card-header-action"><form method="GET" action="{{ route('admin.discussions.index') }}" class="form-inline"><label for="discussion-status" class="mr-2">Status</label><select id="discussion-status" name="status" class="form-control mr-2"><option value="">Semua</option><option value="visible" @selected(($filters['status'] ?? '') === 'visible')>Tampil</option><option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Disembunyikan</option><option value="deleted" @selected(($filters['status'] ?? '') === 'deleted')>Dihapus penulis</option></select><button class="btn btn-primary" type="submit">Terapkan</button></form></div></div>
        <div class="card-body">
            <p class="text-muted">Kiriman tersembunyi tidak terlihat oleh member. Menyembunyikan pertanyaan juga menutup seluruh balasannya.</p>
            @if($posts->isEmpty())
                <x-empty-state title="Belum ada kiriman" description="Diskusi yang dibuat pada materi akan muncul di sini." icon="message-circle" />
            @else
                <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Materi</th><th>Penulis &amp; waktu</th><th>Kiriman</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    @foreach($posts as $post)
                        <tr>
                            <td><strong>{{ $post->lesson?->title ?? 'Materi tidak tersedia' }}</strong><small class="d-block text-muted">{{ $post->lesson?->course?->title ?? 'Course tidak tersedia' }} · {{ $post->parent_id ? 'Balasan' : 'Pertanyaan' }}</small></td>
                            <td>{{ $post->user?->name ?? 'Pengguna' }}<small class="d-block text-muted"><time datetime="{{ $post->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $post->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time></small></td>
                            <td style="min-width:220px;max-width:400px;overflow-wrap:anywhere">{{ \Illuminate\Support\Str::limit($post->body, 180) }}</td>
                            <td>@if($post->trashed())<span class="badge badge-secondary">Dihapus</span>@elseif($post->status === \App\Enums\DiscussionPostStatus::Hidden)<span class="badge badge-warning">Disembunyikan</span>@else<span class="badge badge-success">Tampil</span>@endif</td>
                            <td>
                                @if(! $post->trashed())
                                    @if($post->lesson && (! $post->parent_id || $post->root))<a class="btn btn-sm btn-outline-primary mb-1" href="{{ route('learn.discussion.thread', [$post->lesson, $post->root ?? $post]) }}?focus={{ $post->id }}#discussion-post-{{ $post->id }}">Lihat</a>@endif
                                    <form method="POST" action="{{ $post->status === \App\Enums\DiscussionPostStatus::Visible ? route('admin.discussions.hide', $post) : route('admin.discussions.restore', $post) }}" class="d-inline" @if($post->status === \App\Enums\DiscussionPostStatus::Visible && ! $post->parent_id) data-confirm="Sembunyikan pertanyaan ini dan seluruh balasannya dari member?" @endif>@csrf<button type="submit" class="btn btn-sm {{ $post->status === \App\Enums\DiscussionPostStatus::Visible ? 'btn-outline-warning' : 'btn-outline-success' }} mb-1">{{ $post->status === \App\Enums\DiscussionPostStatus::Visible ? 'Sembunyikan' : 'Pulihkan' }}</button></form>
                                @else<span class="text-muted">—</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody></table></div>
                <div class="mt-3">{{ $posts->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
@push('scripts')<script src="{{ url('js/pages/discussion.js') }}"></script>@endpush
