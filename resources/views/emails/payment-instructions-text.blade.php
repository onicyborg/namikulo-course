Halo, {{ $payment->order->user->name }}.

Pesanan {{ $payment->order->order_number }} untuk {{ $payment->order->course_title }} sudah dibuat.
Metode: {{ strtoupper($payment->channel ?: $payment->payment_type) }}
Total bayar: Rp {{ number_format($payment->order->amount, 0, ',', '.') }}
Batas bayar: {{ $payment->expires_at?->copy()->timezone($payment->order->user->timezone ?: 'UTC')->locale('id')->translatedFormat('d F Y H:i') }} ({{ $payment->order->user->timezone ?: 'UTC' }})
@if($payment->va_number)
Nomor Virtual Account: {{ $payment->va_number }}
Bayar melalui menu Virtual Account bank yang dipilih. Pastikan nominalnya sesuai.
@elseif($payment->qr_url)
Kode QR pembayaran tersedia pada versi HTML email ini dan di halaman pembayaran.
@else
Lanjutkan pembayaran melalui halaman pembayaran.
@endif

Lihat detail pembayaran: {{ route('orders.pay', $payment->order) }}

Instruksi ini tidak berlaku jika pesanan dibatalkan, kedaluwarsa, atau metode pembayaran diganti.
