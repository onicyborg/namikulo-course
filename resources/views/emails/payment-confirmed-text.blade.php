Halo, {{ $order->user->name }}.

Pembayaran pesanan {{ $order->order_number }} untuk {{ $order->course_title }} telah dikonfirmasi.
Jumlah: Rp {{ number_format($order->amount, 0, ',', '.') }}
@if($order->paid_at)
Waktu bayar: {{ $order->paid_at->copy()->timezone($order->user->timezone ?: 'UTC')->format('d M Y H:i') }} ({{ $order->user->timezone ?: 'UTC' }})
@endif
@if($order->enrollment?->expires_at)
Akses hingga: {{ $order->enrollment->expires_at->copy()->timezone($order->user->timezone ?: 'UTC')->format('d M Y H:i') }} ({{ $order->user->timezone ?: 'UTC' }})
@endif

Buka course saya: {{ route('member.dashboard') }}
Unduh invoice PDF: {{ route('member.orders.invoice', $order) }}
