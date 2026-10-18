<section class="nd-panel" aria-labelledby="orders-title">
    <div class="nd-panel-heading">
        <div><h2 id="orders-title">Pesanan terbaru</h2><p>Transaksi terakhir di platform Anda.</p></div>
        <a class="nd-text-link" href="{{ route('admin.orders.index') }}">Lihat semua<x-dashboard.icon name="arrow-up-right" /></a>
    </div>
    @if($orders->isEmpty())
        <div class="nd-empty"><span class="nd-empty-icon"><x-dashboard.icon name="credit-card" /></span><h3>Belum ada pesanan</h3><p>Pesanan pertama akan muncul di sini.</p></div>
    @else
        <div class="nd-table-scroll" tabindex="0" role="region" aria-label="Tabel pesanan terbaru, dapat digulir horizontal">
            <table class="nd-table">
                <thead><tr><th scope="col">Pesanan / Member</th><th scope="col">Jumlah</th><th scope="col">Status</th><th scope="col">Waktu</th><th scope="col"><span class="nd-sr-only">Aksi</span></th></tr></thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td><a class="nd-order-number" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a><span class="nd-order-course">{{ $order->course_title }}</span><span class="nd-order-member">{{ $order->user?->name ?? 'Akun dihapus' }}</span></td>
                            <td class="nd-amount">Rp {{ number_format($order->amount, 0, ',', '.') }}</td>
                            <td><span class="nd-badge nd-badge--{{ $order->status->value }}">{{ ['pending' => 'Menunggu', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Perlu tinjauan'][$order->status->value] ?? $order->status->value }}</span></td>
                            <td><time datetime="{{ $order->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time></td>
                            <td><a class="nd-icon-button nd-order-detail" href="{{ route('admin.orders.show', $order) }}" aria-label="Detail pesanan {{ $order->order_number }}"><x-dashboard.icon name="arrow-up-right" /></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
