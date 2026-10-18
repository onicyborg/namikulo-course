@extends('layouts.admin')

@section('title', 'Moderasi diskusi')
@push('styles')
<link rel="stylesheet" href="{{ asset('bundles/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">
<link rel="stylesheet" href="{{ url('css/admin-discussions.css') }}">
@endpush

@section('content')
<x-page-header title="Moderasi diskusi" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body nc-discussions">
    <x-flash-message />
    <div class="card">
        <div class="card-header"><h2>Kiriman materi</h2></div>
        <div class="card-body">
            <p class="nc-discussions-note">Kiriman tersembunyi tidak terlihat oleh member. Menyembunyikan pertanyaan juga menutup seluruh balasannya.</p>
            <form method="GET" action="{{ route('admin.discussions.index') }}" class="nc-discussion-filters" data-discussion-filters>
                <div><label for="discussion-page-size">Tampilkan data</label><select id="discussion-page-size" name="per_page" class="form-control">@foreach([10, 25, 50, 100] as $size)<option value="{{ $size }}" @selected((int) ($filters['per_page'] ?? 10) === $size)>{{ $size }} data</option>@endforeach</select></div>
                <div><label for="discussion-search">Cari diskusi</label><input id="discussion-search" name="q" type="search" maxlength="120" value="{{ $filters['q'] ?? '' }}" placeholder="Materi, penulis, atau isi kiriman…" class="form-control"></div>
                <div><label for="discussion-status">Status</label><select id="discussion-status" name="status" class="form-control"><option value="">Semua status</option><option value="visible" @selected(($filters['status'] ?? '') === 'visible')>Tampil</option><option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Disembunyikan</option><option value="deleted" @selected(($filters['status'] ?? '') === 'deleted')>Dihapus penulis</option></select></div>
                <a class="nc-discussion-action nc-discussion-reset" href="{{ route('admin.discussions.index') }}" aria-label="Reset filter" data-action-tooltip="Reset filter"><x-dashboard.icon name="x" /></a>
                <noscript><button class="btn btn-primary" type="submit">Cari diskusi</button></noscript>
            </form>
            @if($posts->isEmpty())<p class="nc-discussion-fallback-empty">{{ ($filters['status'] ?? null) || ($filters['q'] ?? null) ? 'Tidak ada kiriman yang sesuai filter.' : 'Belum ada kiriman diskusi.' }}</p>@endif
            <p class="nc-discussion-feedback" role="status" data-discussion-feedback hidden></p>
            <div class="table-responsive"><table class="table nc-discussion-table" id="discussion-table" data-source-url="{{ route('admin.discussions.index') }}"><thead><tr><th>Materi</th><th>Penulis &amp; waktu</th><th>Kiriman</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                @foreach($posts as $post)
                    <tr>
                        <td>@include('admin.discussions.cells.lesson')</td>
                        <td>@include('admin.discussions.cells.author')</td>
                        <td>@include('admin.discussions.cells.body')</td>
                        <td>@include('admin.discussions.cells.status')</td>
                        <td>@include('admin.discussions.cells.actions')</td>
                    </tr>
                @endforeach
            </tbody></table></div>
            <div class="nc-discussion-fallback-pagination">{{ $posts->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
@endsection
@push('plugin-scripts')<script src="{{ asset('bundles/datatables/datatables.min.js') }}"></script>@endpush
@push('scripts')
<script src="{{ url('js/admin-action-tooltip.js') }}"></script>
<script src="{{ url('js/pages/discussion.js') }}?v=5"></script>
<script src="{{ url('js/pages/admin-discussions.js') }}"></script>
@endpush
