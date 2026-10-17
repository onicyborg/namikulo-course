@extends('layouts.admin')

@section('title', 'Tugas dan Log')
@push('styles')<link rel="stylesheet" href="{{ url('css/admin-operations.css') }}">@endpush

@section('content')
<x-page-header title="Tugas dan log" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body admin-operations">
    <section class="card" aria-labelledby="task-status-title"><div class="card-header"><h2 id="task-status-title">Tugas berkala</h2></div><div class="card-body">
        <p class="text-muted">Data ini diperbarui saat halaman dibuka. Tugas dijalankan melalui cron-job.org; halaman ini hanya menampilkan statusnya.</p>
        <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Tugas</th><th>Calon tertunda</th><th>Mulai terakhir</th><th>Selesai terakhir</th><th>Kondisi</th></tr></thead><tbody>
            @foreach($tasks as $task => $item)
                @php($state = $states->get($task))
                @php($late = $state?->last_succeeded_at?->lessThan(now()->subMinutes($item['minutes'] * 2)))
                <tr><td><strong>{{ $item['name'] }}</strong><small class="d-block text-muted">{{ $task }} · tiap {{ $item['minutes'] }} menit</small></td><td>{{ number_format($item['remaining'], 0, ',', '.') }}</td>
                    <td>@if($state?->last_started_at)<time datetime="{{ $state->last_started_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $state->last_started_at->copy()->utc()->format('d M Y H:i') }} UTC</time>@else—@endif</td>
                    <td>@if($state?->last_succeeded_at)<time datetime="{{ $state->last_succeeded_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $state->last_succeeded_at->copy()->utc()->format('d M Y H:i') }} UTC</time>@else—@endif</td>
                    <td>@if($state?->last_error)<span class="badge badge-danger">Gagal</span><small class="d-block admin-operations-error">{{ $state->last_error }}</small>
                        @elseif(! $state?->last_succeeded_at)<span class="badge badge-warning">Belum selesai</span>
                        @elseif($late)<span class="badge badge-warning">Periksa jadwal</span>
                        @else<span class="badge badge-success">Berjalan</span>@endif
                        @if($state?->next_attempt_at?->isFuture())<small class="d-block">Coba lagi <time datetime="{{ $state->next_attempt_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $state->next_attempt_at->copy()->utc()->format('d M Y H:i') }} UTC</time></small>@endif
                    </td></tr>
            @endforeach
        </tbody></table></div>
        <p class="admin-operations-note">“Calon tertunda” menghitung data yang memenuhi syarat awal. Sebagian unggahan video dapat tetap dilindungi karena masih dipakai sesi atau aset aktif.</p>
    </div></section>

    <section class="card" aria-labelledby="system-log-title"><div class="card-header"><h2 id="system-log-title">Log aktivitas</h2></div><div class="card-body">
        <form method="GET" action="{{ route('admin.operations.index') }}" class="row align-items-end admin-operations-filters">
            <div class="col-12 col-md-3"><div class="form-group"><label for="log-action">Aksi</label><select id="log-action" name="action" class="form-control"><option value="">Semua aksi</option>@foreach($actionLabels as $value => $label)<option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div></div>
            <div class="col-12 col-md-3"><div class="form-group"><label for="log-table">Sumber</label><select id="log-table" name="table" class="form-control"><option value="">Semua sumber</option>@foreach($tableLabels as $value => $label)<option value="{{ $value }}" @selected(($filters['table'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div></div>
            <div class="col-12 col-md-4"><div class="form-group"><label for="log-search">ID record atau keterangan</label><input id="log-search" name="q" type="search" maxlength="100" value="{{ $filters['q'] ?? '' }}" class="form-control"></div></div>
            <div class="col-12 col-md-2"><div class="form-group admin-operations-filter-actions"><button type="submit" class="btn btn-primary">Cari</button><a href="{{ route('admin.operations.index') }}" class="btn btn-light">Reset</a></div></div>
        </form>
        @if($logs->isEmpty())<x-empty-state title="Tidak ada log" description="Ubah filter atau tunggu aktivitas baru." icon="activity" />@else
            <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Waktu</th><th>Aksi</th><th>Pelaku</th><th>Target</th><th>Keterangan</th></tr></thead><tbody>
                @foreach($logs as $log)<tr><td><time datetime="{{ $log->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $log->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time></td><td><span class="badge badge-{{ $log->action === 'operation_failed' ? 'danger' : 'light' }}">{{ $actionLabels[$log->action] ?? $log->action }}</span></td><td>{{ $log->user?->name ?? 'Sistem' }}@if($log->action === 'playback_started')<small class="d-block text-muted">IP {{ $log->ip_address ?? 'tidak tersedia' }}</small><small class="d-block text-muted admin-log-agent">{{ $log->user_agent ?? 'Perangkat tidak diketahui' }}</small>@endif</td><td>{{ $tableLabels[$log->table_name] ?? ($log->table_name ?? '—') }}@if($log->record_id)<small class="d-block text-muted admin-log-id">{{ $log->record_id }}</small>@endif</td><td>{{ $log->description ?? '—' }}</td></tr>@endforeach
            </tbody></table></div>
            <div class="mt-3">{{ $logs->links('pagination::bootstrap-4') }}</div>
        @endif
    </div></section>
</div>
@endsection
