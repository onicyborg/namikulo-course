@switch($column)
    @case('created')
        <time datetime="{{ $log->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $log->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time>
        @break
    @case('action')
        <span class="badge badge-{{ $log->action === 'operation_failed' ? 'danger' : 'light' }}">{{ $actionLabels[$log->action] ?? $log->action }}</span>
        @break
    @case('actor')
        {{ $log->user?->name ?? 'Sistem' }}
        @break
    @case('source')
        <span class="nc-table-summary">{{ $tableLabels[$log->table_name] ?? ($log->table_name ?? '—') }}</span>
        @if($log->record_id)<code class="nc-table-summary">{{ $log->record_id }}</code>@endif
        @break
    @case('description')
        <span class="nc-table-summary">{{ $log->description ?? 'Tidak ada keterangan tambahan.' }}</span>
        @if($log->action === 'playback_started')
            <details class="nc-log-device">
                <summary>Detail perangkat</summary>
                <dl>
                    <div><dt>IP</dt><dd>{{ $log->ip_address ?? 'Tidak tersedia' }}</dd></div>
                    <div><dt>Perangkat</dt><dd class="nc-table-summary">{{ $log->user_agent ?? 'Tidak diketahui' }}</dd></div>
                </dl>
            </details>
        @endif
        @break
@endswitch
