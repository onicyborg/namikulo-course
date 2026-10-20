@extends('layouts.member')
@section('title', 'Pembayaran '.$order->order_number)
@push('styles')<link rel="stylesheet" href="{{ url('css/checkout.css') }}?v=2">@endpush
@section('content')
@php
    $statusLabel = ['pending' => 'Menunggu pembayaran', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Dalam peninjauan'][$order->status->value] ?? 'Tidak aktif';
@endphp
<div class="checkout-page checkout-pay-page nc-checkout" data-order-status-url="{{ route('orders.status', $order) }}" data-order-status="{{ $order->status->value }}" data-payment-status="{{ $payment?->status?->value }}" data-order-expires="{{ $order->expires_at?->copy()->utc()->toIso8601String() }}">
    <nav class="site-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('member.orders.index') }}">Transaksi</a><x-dashboard.icon name="chevron-right" /><span aria-current="page">Pembayaran</span></nav>
    <header class="checkout-heading checkout-pay-heading">
        <div><span class="checkout-eyebrow">PESANAN {{ $order->order_number }}</span><h1>{{ $order->status === \App\Enums\OrderStatus::Paid ? 'Pembayaran berhasil' : 'Detail pembayaran' }}</h1><p>{{ $order->course_title }}</p></div>
        <span class="checkout-status checkout-status-{{ $order->status->value }}"><span aria-hidden="true"></span>{{ $statusLabel }}</span>
    </header>
    <x-flash-message />
    @if($errors->any())<div class="checkout-feedback checkout-feedback-error" role="alert"><x-dashboard.icon name="alert-triangle" /><span>{{ $errors->first() }}</span></div>@endif

    <div class="checkout-grid checkout-payment-grid">
        <aside class="checkout-panel checkout-order-panel" aria-labelledby="payment-detail-title"><div class="checkout-panel-body">
            <div class="checkout-section-heading"><span class="checkout-section-icon"><x-dashboard.icon name="book-open" /></span><div><span class="checkout-section-overline">RINGKASAN</span><h2 id="payment-detail-title">Detail pesanan</h2></div></div>
            <div class="checkout-summary-line"><span>Course</span><strong>{{ $order->course_title }}</strong></div>
            <div class="checkout-summary-line"><span>Nomor pesanan</span><strong class="checkout-order-number">{{ $order->order_number }}</strong></div>
            <div class="checkout-amount"><span>Total pembayaran</span><strong>Rp {{ number_format($order->amount, 0, ',', '.') }}</strong><button type="button" class="btn btn-outline-primary btn-sm" data-copy-text="{{ $order->amount }}" data-copy-label="Salin nominal">Salin nominal</button></div>
            @if($order->status === \App\Enums\OrderStatus::Pending)
                <div class="checkout-deadline"><x-dashboard.icon name="clock-3" /><div><span>Batas pembayaran</span><strong><time datetime="{{ $order->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time></strong><p class="checkout-countdown" data-payment-countdown role="timer"></p></div></div>
            @endif
        </div></aside>
        <section class="checkout-panel checkout-payment-panel" aria-labelledby="payment-action-title"><div class="checkout-panel-body">
            <div class="checkout-section-heading"><span class="checkout-section-icon"><x-dashboard.icon name="{{ $order->status === \App\Enums\OrderStatus::Paid ? 'circle-check' : 'credit-card' }}" /></span><div><span class="checkout-section-overline">PEMBAYARAN</span><h2 id="payment-action-title">{{ $order->status === \App\Enums\OrderStatus::Pending ? 'Cara membayar' : 'Status pembayaran' }}</h2></div></div>
            @if($order->status === \App\Enums\OrderStatus::Paid)
                <div class="checkout-result checkout-result-success"><x-dashboard.icon name="circle-check" /><div><strong>Pembayaran dikonfirmasi</strong><p>Akses course Anda sudah aktif.</p></div></div>
                @if($order->enrollment)<p class="checkout-supporting">Akses hingga <time datetime="{{ $order->enrollment->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->enrollment->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time>.</p>@endif
                <div class="checkout-actions">@if($firstReadyLesson)<a class="btn btn-primary" href="{{ route('learn.show', $firstReadyLesson) }}">Mulai belajar<x-dashboard.icon name="arrow-right" /></a>@endif<a class="btn btn-outline-primary" href="{{ route('member.dashboard') }}">Dashboard member</a><a class="btn btn-outline-primary" href="{{ route('member.orders.invoice', $order) }}">Unduh invoice PDF</a></div>
            @elseif($order->status === \App\Enums\OrderStatus::Pending && $payment?->status === \App\Enums\PaymentStatus::Pending)
                <div class="checkout-method-selected"><span>Metode yang dipilih</span><strong>{{ strtoupper($payment->channel ?: $payment->payment_type) }}</strong></div>
                @if(! $payment->instruction_email_sent_at)
                    <div class="checkout-feedback checkout-feedback-warning" role="status"><x-dashboard.icon name="alert-triangle" /><span>Email instruksi belum tercatat terkirim. Simpan rincian di halaman ini untuk membayar, atau hubungi admin jika Anda membutuhkan emailnya.</span></div>
                @endif
                @if($payment->va_number)
                    <div class="checkout-payment-data"><span>Nomor virtual account</span><div class="checkout-copy-line"><strong>{{ $payment->va_number }}</strong><button type="button" class="btn btn-outline-primary btn-sm" data-copy-text="{{ $payment->va_number }}" data-copy-label="Salin nomor">Salin nomor</button></div></div>
                    <p class="checkout-instruction-intro">Transfer sesuai nominal melalui salah satu kanal berikut.</p>
                    <div class="checkout-instructions" id="payment-instructions">
                        @foreach(['Aplikasi mobile' => 'Buka aplikasi bank, pilih transfer atau bayar ke Virtual Account, lalu masukkan nomor di atas.', 'ATM' => 'Pilih transaksi lain atau pembayaran Virtual Account di ATM, lalu masukkan nomor di atas.', 'Internet banking' => 'Masuk ke internet banking, pilih pembayaran Virtual Account, lalu masukkan nomor di atas.'] as $method => $instruction)
                            <div class="checkout-instruction"><button class="checkout-instruction-toggle" type="button" data-toggle="collapse" data-target="#instruction-{{ $loop->index }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="instruction-{{ $loop->index }}"><span class="checkout-instruction-number">0{{ $loop->iteration }}</span>{{ $method }}<x-dashboard.icon name="chevron-down" /></button><div id="instruction-{{ $loop->index }}" class="collapse {{ $loop->first ? 'show' : '' }}" data-parent="#payment-instructions"><p>{{ $instruction }} Periksa nama penerima dan nominal sebelum menyelesaikan transaksi {{ strtoupper($payment->channel) }}.</p></div></div>
                        @endforeach
                    </div>
                @endif
                @if($payment->qr_url)<div class="checkout-qr-section"><span class="checkout-qr-overline">PINDAI UNTUK MEMBAYAR</span><div class="checkout-qr-frame"><img class="checkout-qr" src="{{ $payment->qr_url }}" alt="Kode QR pembayaran pesanan {{ $order->order_number }}" width="240" height="240"></div><p>Pindai kode QR menggunakan aplikasi pembayaran yang mendukung QRIS.</p></div>@endif
                @if($payment->deeplink_url)<a class="btn btn-primary checkout-deeplink" href="{{ $payment->deeplink_url }}" rel="noopener noreferrer">Buka aplikasi pembayaran<x-dashboard.icon name="arrow-up-right" /></a>@endif
                @if(! $payment->va_number && ! $payment->qr_url && ! $payment->deeplink_url)<div class="checkout-feedback checkout-feedback-warning" role="alert"><x-dashboard.icon name="alert-triangle" /><span>Instruksi pembayaran belum tersedia. Jangan membayar sebelum informasi pembayaran dapat dilihat; hubungi admin jika keadaan ini berlanjut.</span></div>@endif
                <p class="checkout-note"><x-dashboard.icon name="activity" />Status diperbarui otomatis selama halaman ini terbuka. Akses aktif setelah pembayaran dikonfirmasi oleh server.</p>
                <div class="checkout-actions">@if(! $order->course->trashed() && $order->course->status === \App\Enums\CourseStatus::Published)<a class="btn btn-outline-primary" href="{{ route('checkout.show', $order->course->slug) }}">Ganti metode</a>@endif<form method="POST" action="{{ route('orders.cancel', $order) }}" data-loading-form>@csrf<button type="submit" class="btn btn-outline-danger">Batalkan pesanan</button></form></div>
            @elseif($order->status === \App\Enums\OrderStatus::Pending)
                @if(in_array($payment?->status, [\App\Enums\PaymentStatus::Initiating, \App\Enums\PaymentStatus::Unknown], true))
                    <div class="checkout-result checkout-result-info"><x-dashboard.icon name="clock-3" /><div><strong>Status pembayaran sedang dikonfirmasi</strong><p>Jangan membuat pembayaran baru sebelum statusnya jelas.</p></div></div>
                    <p class="checkout-note"><x-dashboard.icon name="activity" />Halaman ini memeriksa perubahan status secara berkala. Admin juga dapat melakukan sinkronisasi bila diperlukan.</p>
                @else
                    <div class="checkout-result checkout-result-neutral"><x-dashboard.icon name="credit-card" /><div><strong>Percobaan pembayaran tidak aktif</strong><p>Anda dapat memilih metode lain pada pesanan ini.</p></div></div>
                    @if(! $order->course->trashed() && $order->course->status === \App\Enums\CourseStatus::Published)<a class="btn btn-primary" href="{{ route('checkout.show', $order->course->slug) }}">Pilih metode pembayaran<x-dashboard.icon name="arrow-right" /></a>@endif
                @endif
            @else
                <div class="checkout-result checkout-result-neutral"><x-dashboard.icon name="alert-triangle" /><div><strong>Pesanan {{ ['expired' => 'telah kedaluwarsa', 'cancelled' => 'telah dibatalkan', 'failed' => 'gagal', 'refunded' => 'memerlukan peninjauan admin'][$order->status->value] ?? 'tidak aktif' }}.</strong></div></div>
                @if(! $order->course->trashed() && $order->course->status === \App\Enums\CourseStatus::Published)<a class="btn btn-primary" href="{{ route('checkout.show', $order->course->slug) }}">Buat pesanan baru<x-dashboard.icon name="arrow-right" /></a>@endif
            @endif
        </div></section>

    </div>
    @if($order->status === \App\Enums\OrderStatus::Pending)<p class="checkout-live-message" data-payment-live role="status" aria-live="polite"></p>@endif
</div>
@endsection
@push('scripts')<script src="{{ url('js/pages/checkout.js') }}?v=20"></script>@endpush
