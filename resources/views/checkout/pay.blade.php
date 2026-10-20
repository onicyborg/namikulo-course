@extends('layouts.member')
@section('title', 'Pembayaran '.$order->order_number)
@push('styles')<link rel="stylesheet" href="{{ url('css/checkout.css') }}">@endpush
@section('content')
<div class="container checkout-page checkout-pay-page" data-order-status-url="{{ route('orders.status', $order) }}" data-order-status="{{ $order->status->value }}" data-payment-status="{{ $payment?->status?->value }}" data-order-expires="{{ $order->expires_at?->copy()->utc()->toIso8601String() }}">
    <nav class="site-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('member.orders.index') }}">Transaksi</a><span aria-hidden="true">/</span><span aria-current="page">Pembayaran</span></nav>
    <div class="checkout-heading"><span>STATUS PESANAN</span><h1>{{ $order->status === \App\Enums\OrderStatus::Paid ? 'Pembayaran berhasil' : 'Instruksi pembayaran' }}</h1><p>Pesanan {{ $order->order_number }} untuk {{ $order->course_title }}</p></div>
    <x-flash-message />
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    <div class="checkout-grid">
        <section class="checkout-panel" aria-labelledby="payment-detail-title"><div class="checkout-panel-body">
            <h2 id="payment-detail-title">Detail pesanan</h2>
            <div class="checkout-summary-line"><span>Course</span><strong>{{ $order->course_title }}</strong></div>
            <div class="checkout-summary-line"><span>Nomor pesanan</span><strong>{{ $order->order_number }}</strong></div>
            <div class="checkout-summary-line checkout-total"><span>Total</span><span class="checkout-amount-action"><strong>Rp {{ number_format($order->amount, 0, ',', '.') }}</strong><button type="button" class="btn btn-outline-primary btn-sm" data-copy-text="{{ $order->amount }}" data-copy-label="Salin nominal">Salin nominal</button></span></div>
            @if($order->status === \App\Enums\OrderStatus::Pending)
                <div class="checkout-summary-line"><span>Batas bayar</span><strong><time datetime="{{ $order->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time></strong></div>
                <p class="checkout-countdown" data-payment-countdown role="timer"></p>
            @endif
        </div></section>
        <section class="checkout-panel" aria-labelledby="payment-action-title"><div class="checkout-panel-body">
            <h2 id="payment-action-title">{{ $order->status === \App\Enums\OrderStatus::Pending ? 'Cara membayar' : 'Status pembayaran' }}</h2>
            @if($order->status === \App\Enums\OrderStatus::Paid)
                <p class="checkout-state-success">Pembayaran dikonfirmasi. Akses course sudah aktif.</p>
                @if($order->enrollment)<p>Akses hingga <time datetime="{{ $order->enrollment->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->enrollment->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time>.</p>@endif
                @if($firstReadyLesson)<a class="btn btn-primary" href="{{ route('learn.show', $firstReadyLesson) }}">Mulai belajar</a>@endif
                <a class="btn btn-outline-primary" href="{{ route('member.dashboard') }}">Dashboard member</a>
                <a class="btn btn-outline-primary" href="{{ route('member.orders.invoice', $order) }}">Unduh invoice PDF</a>
            @elseif($order->status === \App\Enums\OrderStatus::Pending && $payment?->status === \App\Enums\PaymentStatus::Pending)
                <p>Metode: <strong>{{ strtoupper($payment->channel ?: $payment->payment_type) }}</strong></p>
                @if(! $payment->instruction_email_sent_at)
                    <div class="alert alert-warning" role="status">Email instruksi belum tercatat terkirim. Simpan rincian di halaman ini untuk membayar, atau hubungi admin jika Anda membutuhkan emailnya.</div>
                @endif
                @if($payment->va_number)
                    <p>Transfer sesuai nominal ke virtual account berikut:</p><div class="checkout-copy-line"><strong>{{ $payment->va_number }}</strong><button type="button" class="btn btn-outline-primary btn-sm" data-copy-text="{{ $payment->va_number }}" data-copy-label="Salin nomor">Salin nomor</button></div>
                    <div class="checkout-instructions" id="payment-instructions">
                        @foreach(['Aplikasi mobile' => 'Buka aplikasi bank, pilih transfer atau bayar ke Virtual Account, lalu masukkan nomor di atas.', 'ATM' => 'Pilih transaksi lain atau pembayaran Virtual Account di ATM, lalu masukkan nomor di atas.', 'Internet banking' => 'Masuk ke internet banking, pilih pembayaran Virtual Account, lalu masukkan nomor di atas.'] as $method => $instruction)
                            <div class="checkout-instruction"><button class="checkout-instruction-toggle" type="button" data-toggle="collapse" data-target="#instruction-{{ $loop->index }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="instruction-{{ $loop->index }}">{{ $method }} <span aria-hidden="true">⌄</span></button><div id="instruction-{{ $loop->index }}" class="collapse {{ $loop->first ? 'show' : '' }}" data-parent="#payment-instructions"><p>{{ $instruction }} Periksa nama penerima dan nominal sebelum menyelesaikan transaksi {{ strtoupper($payment->channel) }}.</p></div></div>
                        @endforeach
                    </div>
                @endif
                @if($payment->qr_url)<p>Pindai kode QR menggunakan aplikasi pembayaran yang mendukung QRIS.</p><img class="checkout-qr" src="{{ $payment->qr_url }}" alt="Kode QR pembayaran pesanan {{ $order->order_number }}" width="240" height="240">@endif
                @if($payment->deeplink_url)<p><a class="btn btn-primary" href="{{ $payment->deeplink_url }}" rel="noopener noreferrer">Buka aplikasi pembayaran</a></p>@endif
                @if(! $payment->va_number && ! $payment->qr_url && ! $payment->deeplink_url)<div class="alert alert-warning">Instruksi pembayaran belum tersedia. Jangan membayar sebelum informasi pembayaran dapat dilihat; hubungi admin jika keadaan ini berlanjut.</div>@endif
                <p class="checkout-note">Status diperbarui otomatis selama halaman ini terbuka. Akses aktif setelah pembayaran dikonfirmasi oleh server.</p>
                <div class="checkout-actions">@if(! $order->course->trashed() && $order->course->status === \App\Enums\CourseStatus::Published)<a class="btn btn-outline-primary" href="{{ route('checkout.show', $order->course->slug) }}">Ganti metode</a>@endif<form method="POST" action="{{ route('orders.cancel', $order) }}" data-loading-form>@csrf<button type="submit" class="btn btn-outline-danger">Batalkan pesanan</button></form></div>
            @elseif($order->status === \App\Enums\OrderStatus::Pending)
                @if(in_array($payment?->status, [\App\Enums\PaymentStatus::Initiating, \App\Enums\PaymentStatus::Unknown], true))
                    <p>Status percobaan pembayaran sedang dikonfirmasi. Jangan membuat pembayaran baru sebelum statusnya jelas.</p>
                    <p class="checkout-note">Halaman ini memeriksa perubahan status secara berkala. Admin juga dapat melakukan sinkronisasi bila diperlukan.</p>
                @else
                    <p>Percobaan pembayaran terakhir tidak aktif. Anda dapat memilih metode lain pada pesanan ini.</p>
                    @if(! $order->course->trashed() && $order->course->status === \App\Enums\CourseStatus::Published)<a class="btn btn-primary" href="{{ route('checkout.show', $order->course->slug) }}">Pilih metode pembayaran</a>@endif
                @endif
            @else
                <p>Pesanan {{ ['expired' => 'telah kedaluwarsa', 'cancelled' => 'telah dibatalkan', 'failed' => 'gagal', 'refunded' => 'memerlukan peninjauan admin'][$order->status->value] ?? 'tidak aktif' }}.</p>
                @if(! $order->course->trashed() && $order->course->status === \App\Enums\CourseStatus::Published)<a class="btn btn-primary" href="{{ route('checkout.show', $order->course->slug) }}">Buat pesanan baru</a>@endif
            @endif
        </div></section>
    </div>
    @if($order->status === \App\Enums\OrderStatus::Pending)<p class="checkout-live-message" data-payment-live role="status" aria-live="polite"></p>@endif
</div>
@endsection
@push('scripts')<script src="{{ url('js/pages/checkout.js') }}?v=20"></script>@endpush
