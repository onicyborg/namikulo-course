@extends('layouts.admin')

@section('title', 'Tugas dan Log')
@push('styles')<link rel="stylesheet" href="{{ url('css/admin-operations.css') }}?v=2">@endpush

@section('content')
<x-page-header title="Tugas dan log" parent="Dashboard" :parentUrl="route('admin.dashboard')" />
<div class="section-body admin-operations">
    <section class="card" aria-labelledby="task-status-title">
        <div class="card-header nc-operations-heading">
            <div>
                <p class="nc-operations-kicker">PEMANTAUAN</p>
                <h2 id="task-status-title">Tugas berkala</h2>
                <p>Status terakhir dari pekerjaan yang dijalankan melalui cron-job.org.</p>
            </div>
            <span class="nc-operations-count">{{ count($tasks) }} tugas</span>
        </div>
        <div class="card-body">
            <div class="nc-task-grid">
                @foreach($tasks as $task => $item)
                    @include('admin.operations._task-card', ['task' => $task, 'item' => $item, 'state' => $states->get($task), 'index' => $loop->index])
                @endforeach
            </div>

            <p class="nc-operations-note">Calon tertunda menghitung data yang memenuhi syarat awal. Unggahan video tertentu tetap dilindungi saat masih dipakai sesi atau aset aktif.</p>

            <section class="nc-digest-summary" aria-labelledby="digest-title">
                <div class="nc-digest-heading">
                    <div>
                        <h3 id="digest-title">Rekap notifikasi</h3>
                        <p>{{ $digestDate }} · waktu WIB</p>
                    </div>
                    <x-dashboard.icon name="bell" />
                </div>
                <div class="nc-digest-grid">
                    <div><strong>{{ (int) ($digestStats[\App\Enums\NotificationDigestStatus::Sent->value] ?? 0) }}</strong><span>Terkirim</span></div>
                    <div><strong>{{ (int) ($digestStats[\App\Enums\NotificationDigestStatus::Uncertain->value] ?? 0) }}</strong><span>Belum pasti</span></div>
                    <div><strong>{{ (int) ($digestStats[\App\Enums\NotificationDigestStatus::Failed->value] ?? 0) }}</strong><span>Gagal</span></div>
                    <div><strong>{{ number_format($tasks[\App\Services\SendNotificationDigests::TASK]['remaining'], 0, ',', '.') }}</strong><span>Menunggu giliran</span></div>
                </div>
                <p class="nc-digest-note">Pengiriman dengan hasil belum pasti tidak dicoba ulang otomatis pada hari yang sama.</p>
            </section>
        </div>
    </section>

    <section class="card" aria-labelledby="system-log-title">
        <div class="card-header nc-operations-heading">
            <div>
                <p class="nc-operations-kicker">RIWAYAT</p>
                <h2 id="system-log-title">Log aktivitas</h2>
                <p>Telusuri tindakan pengguna dan kejadian penting sistem.</p>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.operations.index') }}" class="nc-log-filters">
                <div class="form-group"><label for="log-action">Aksi</label><select id="log-action" name="action" class="form-control"><option value="">Semua aksi</option>@foreach($actionLabels as $value => $label)<option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-group"><label for="log-table">Sumber</label><select id="log-table" name="table" class="form-control"><option value="">Semua sumber</option>@foreach($tableLabels as $value => $label)<option value="{{ $value }}" @selected(($filters['table'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="form-group"><label for="log-search">ID record atau keterangan</label><input id="log-search" name="q" type="search" maxlength="100" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Cari aktivitas…"></div>
                <div class="nc-log-filter-actions"><button type="submit" class="btn btn-primary">Cari log</button><a href="{{ route('admin.operations.index') }}" class="btn btn-light">Reset</a></div>
            </form>

            @if($logs->isEmpty())
                <x-empty-state title="Tidak ada log" description="Ubah filter atau tunggu aktivitas baru." icon="activity" />
            @else
                <div class="nc-log-list">
                    @foreach($logs as $log)
                        @include('admin.operations._log-item', ['log' => $log])
                    @endforeach
                </div>
                <div class="nc-log-pagination">{{ $logs->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </section>
</div>
@endsection
