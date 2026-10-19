@php
$status = $order->status->value;
$labels = ['pending' => 'Menunggu pembayaran', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Perlu tinjauan'];
$badge = ['pending' => 'warning', 'paid' => 'success', 'expired' => 'secondary', 'cancelled' => 'secondary', 'failed' => 'danger', 'refunded' => 'warning'];
@endphp
@switch($column)
    @case('order')
        <strong class="member-order-title">{{ $order->course_title }}</strong><span class="member-order-number">{{ $order->order_number }}</span>
        @break
    @case('created')
        <time datetime="{{ $order->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time>
        @break
    @case('amount')
        Rp {{ number_format($order->amount, 0, ',', '.') }}
        @break
    @case('status')
        <span class="badge badge-{{ $badge[$status] ?? 'light' }}">{{ $labels[$status] ?? ucfirst($status) }}</span>
        @break
    @case('actions')
        @if($order->status === \App\Enums\OrderStatus::Pending && $order->expires_at?->isFuture())
                                        <a href="{{ route('orders.pay', $order) }}" class="btn btn-primary nm-icon-action nc-table-action nc-table-action--pay" data-action-tooltip="Lanjutkan pembayaran" aria-label="Lanjutkan pembayaran {{ $order->order_number }}"><x-dashboard.icon name="credit-card" /></a>
                                    @else
                                        <a href="{{ route('orders.pay', $order) }}" class="btn btn-outline-primary nm-icon-action nc-table-action nc-table-action--view" data-action-tooltip="Lihat detail transaksi" aria-label="Lihat detail {{ $order->order_number }}"><x-dashboard.icon name="eye" /></a>
                                    @endif
                                    @if($order->status === \App\Enums\OrderStatus::Paid)
                                        <a href="{{ route('member.orders.invoice', $order) }}" class="btn btn-outline-primary nm-icon-action nc-table-action nc-table-action--download" data-action-tooltip="Unduh invoice PDF" aria-label="Unduh invoice PDF {{ $order->order_number }}"><x-dashboard.icon name="arrow-down" /></a>
                                    @endif
        @break
@endswitch
