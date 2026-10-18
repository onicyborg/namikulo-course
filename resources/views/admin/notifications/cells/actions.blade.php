<div class="nc-data-table-actions">
    @if($target)
        <form method="POST" action="{{ route('admin.notifications.open', $item) }}">@csrf<button class="nc-data-table-action" type="submit" aria-label="Lihat detail: {{ $item->title }}" data-action-tooltip="Lihat detail"><x-dashboard.icon name="eye" /></button></form>
    @endif
    @unless($item->read_at)
        <form method="POST" action="{{ route('admin.notifications.read', $item) }}">@csrf<button class="nc-data-table-action" type="submit" aria-label="Tandai dibaca: {{ $item->title }}" data-action-tooltip="Tandai dibaca"><x-dashboard.icon name="circle-check" /></button></form>
    @endunless
    @if(! $target && $item->read_at)<span class="text-muted" aria-label="Tidak ada aksi tersedia">—</span>@endif
</div>
