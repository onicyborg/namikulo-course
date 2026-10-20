@php
    $late = $state?->last_succeeded_at?->lessThan(now()->subMinutes($item['minutes'] * 2));
    $tone = $state?->last_error ? 'danger' : (! $state?->last_succeeded_at || $late ? 'warning' : 'success');
    $status = $state?->last_error ? 'Gagal' : (! $state?->last_succeeded_at ? 'Belum selesai' : ($late ? 'Periksa jadwal' : 'Berjalan'));
@endphp
<article class="nc-task-card" data-task-tone="{{ $tone }}" aria-labelledby="task-{{ $index }}">
    <div class="nc-task-heading">
        <div>
            <h3 id="task-{{ $index }}">{{ $item['name'] }}</h3>
            <p>Setiap {{ $item['minutes'] }} menit</p>
        </div>
        <span class="badge badge-{{ $tone }}">{{ $status }}</span>
    </div>
    <dl class="nc-task-metrics">
        <div><dt>Calon tertunda</dt><dd class="nc-task-count">{{ number_format($item['remaining'], 0, ',', '.') }}</dd></div>
        <div><dt>Mulai terakhir</dt><dd>@if($state?->last_started_at)<time datetime="{{ $state->last_started_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $state->last_started_at->copy()->utc()->format('d M Y H:i') }} UTC</time>@else Belum ada @endif</dd></div>
        <div><dt>Selesai terakhir</dt><dd>@if($state?->last_succeeded_at)<time datetime="{{ $state->last_succeeded_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $state->last_succeeded_at->copy()->utc()->format('d M Y H:i') }} UTC</time>@else Belum ada @endif</dd></div>
    </dl>
    @if($state?->last_error)<div class="nc-task-error"><strong>Kegagalan terakhir</strong><p>{{ $state->last_error }}</p></div>@endif
    @if($state?->next_attempt_at?->isFuture())<p class="nc-task-retry">Coba lagi <time datetime="{{ $state->next_attempt_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $state->next_attempt_at->copy()->utc()->format('d M Y H:i') }} UTC</time></p>@endif
    <p class="nc-task-key">ID tugas: <code>{{ $task }}</code></p>
</article>
