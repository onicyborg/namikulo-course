<article class="nc-log-item">
    <div class="nc-log-item-top">
        <span class="badge badge-{{ $log->action === 'operation_failed' ? 'danger' : 'light' }}">{{ $actionLabels[$log->action] ?? $log->action }}</span>
        <time datetime="{{ $log->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $log->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time>
    </div>
    <p class="nc-log-description">{{ $log->description ?? 'Tidak ada keterangan tambahan.' }}</p>
    <dl class="nc-log-meta">
        <div><dt>Pelaku</dt><dd>{{ $log->user?->name ?? 'Sistem' }}</dd></div>
        <div><dt>Sumber</dt><dd>{{ $tableLabels[$log->table_name] ?? ($log->table_name ?? '—') }}</dd></div>
        @if($log->record_id)<div><dt>ID record</dt><dd><code>{{ $log->record_id }}</code></dd></div>@endif
    </dl>
    @if($log->action === 'playback_started')
        <details class="nc-log-device">
            <summary>Detail perangkat</summary>
            <dl>
                <div><dt>IP</dt><dd>{{ $log->ip_address ?? 'Tidak tersedia' }}</dd></div>
                <div><dt>Perangkat</dt><dd>{{ $log->user_agent ?? 'Tidak diketahui' }}</dd></div>
            </dl>
        </details>
    @endif
</article>
