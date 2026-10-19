@switch($column)
    @case('order')
        <a class="nd-order-number" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a><span class="nd-order-course">{{ $order->course_title }}</span><span class="nd-order-member">{{ $order->user?->name ?? 'Akun dihapus' }}</span>
        @break
    @case('amount')
        Rp {{ number_format($order->amount, 0, ',', '.') }}
        @break
    @case('status')
        <span class="nd-badge nd-badge--{{ $order->status->value }}">{{ ['pending' => 'Menunggu', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Perlu tinjauan'][$order->status->value] ?? $order->status->value }}</span>
        @break
    @case('created')
        <time datetime="{{ $order->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time>
        @break
    @case('actions')
        <a class="nd-icon-button nd-order-detail nc-table-action nc-table-action--view" data-action-tooltip="Lihat detail pesanan" href="{{ route('admin.orders.show', $order) }}" aria-label="Detail pesanan {{ $order->order_number }}"><x-dashboard.icon name="arrow-up-right" /></a>
        @break
@endswitch
