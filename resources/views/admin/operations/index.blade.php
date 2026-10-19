@extends('layouts.admin')

@section('title', 'Tugas dan Log')
@push('styles')<link rel="stylesheet" href="{{ url('css/admin-operations.css') }}?v=3">@endpush

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
                <p>Cari aksi, pelaku, sumber atau ID record, dan keterangan log.</p>
            </div>
        </div>
        <div class="card-body">
            <x-server-table :rows="$logs"
                :columns="['created' => 'Waktu', 'action' => 'Aksi', 'actor' => 'Pelaku', 'source' => 'Sumber / ID record', 'description' => 'Keterangan']"
                cells="admin.operations._log-cell" record="log" :source="route('admin.operations.data')"
                :filters="$filters" sortable="0,1,3" order-column="0" direction="desc"
                :extra-filters="['action' => ['Aksi', $actionLabels], 'table' => ['Sumber', $tableLabels]]"
                :cell-data="compact('actionLabels', 'tableLabels')" />

        </div>
    </section>
</div>
@endsection

@push('styles')<link rel="stylesheet" href="{{ url('css/admin-action-tooltip.css') }}">@endpush
@push('scripts')<script src="{{ url('js/admin-action-tooltip.js') }}"></script>@endpush
