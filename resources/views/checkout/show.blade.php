@extends('layouts.member')
@section('title', 'Checkout '.$course->title)
@push('styles')<link rel="stylesheet" href="{{ url('css/checkout.css') }}?v=2">@endpush
@section('content')
<div class="checkout-page nc-checkout nc-checkout-select">
    <nav class="site-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('site.courses.show', $course->slug) }}">{{ $course->title }}</a><x-dashboard.icon name="chevron-right" /><span aria-current="page">Checkout</span></nav>
    <header class="checkout-heading">
        <span class="checkout-eyebrow">CHECKOUT</span>
        <h1>Selesaikan pesanan Anda</h1>
        <p>Periksa course dan pilih satu metode pembayaran untuk melanjutkan.</p>
    </header>
    @if($errors->any())<div class="checkout-feedback checkout-feedback-error" role="alert"><x-dashboard.icon name="alert-triangle" /><span>{{ $errors->first() }}</span></div>@endif
    @if($pending)<div class="checkout-feedback checkout-feedback-info" role="status"><x-dashboard.icon name="clock-3" /><span>Pesanan ini masih menunggu pembayaran. <a href="{{ route('orders.pay', $pending) }}">Lihat instruksi sebelumnya</a>. Memilih metode lain akan membatalkan percobaan pembayaran sebelumnya pada pesanan yang sama.</span></div>@endif

    <div class="checkout-grid">
        <aside class="checkout-panel checkout-order-panel" aria-labelledby="checkout-course-title">
            <div class="checkout-panel-body">
                <div class="checkout-section-heading"><span class="checkout-section-icon"><x-dashboard.icon name="book-open" /></span><div><span class="checkout-section-overline">RINGKASAN</span><h2>Pesanan Anda</h2></div></div>
                <div class="checkout-course">
                    @if($course->thumbnail_path)<img class="checkout-course-image" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($course->thumbnail_path) }}" alt="Thumbnail course {{ $course->title }}" width="160" height="90">@else<span class="checkout-course-placeholder"><x-dashboard.icon name="book-open" /></span>@endif
                    <div><span class="checkout-kicker">COURSE VIDEO</span><h3 id="checkout-course-title">{{ $course->title }}</h3></div>
                </div>
                @if($course->summary)<p class="checkout-course-summary">{{ $course->summary }}</p>@endif
                <div class="checkout-summary-line"><span>Masa akses</span><strong>{{ $course->access_months }} bulan sejak pembayaran terkonfirmasi</strong></div>
                <div class="checkout-summary-line checkout-total"><span>Total pembayaran</span><strong>Rp {{ number_format($pending?->amount ?? ($course->discount_price ?? $course->price), 0, ',', '.') }}</strong></div>
                <p class="checkout-order-note"><x-dashboard.icon name="circle-check" />Akses course aktif setelah pembayaran dikonfirmasi.</p>
            </div>
        </aside>
        <section class="checkout-panel checkout-method-panel" aria-labelledby="checkout-method-title">
            <div class="checkout-panel-body">
                <div class="checkout-section-heading"><span class="checkout-section-icon"><x-dashboard.icon name="credit-card" /></span><div><span class="checkout-section-overline">LANGKAH 01</span><h2 id="checkout-method-title">Metode pembayaran</h2><p>Pilih metode yang tersedia untuk pesanan ini.</p></div></div>
                @if(empty($channels))
                    <div class="checkout-feedback checkout-feedback-warning" role="status"><x-dashboard.icon name="alert-triangle" /><span>Belum ada metode pembayaran yang tersedia.</span></div>
                @else
                    <form method="POST" action="{{ route('checkout.store', $course->slug) }}" data-loading-form>@csrf
                        <fieldset class="checkout-methods"><legend class="sr-only">Pilih metode pembayaran</legend>
                            @php($previousGroup = null)
                            @foreach($channels as $channel)
                                @php
                                    $label = ['bca' => 'BCA Virtual Account', 'bni' => 'BNI Virtual Account', 'bri' => 'BRI Virtual Account', 'permata' => 'Permata Virtual Account', 'cimb' => 'CIMB Virtual Account', 'qris' => 'QRIS', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay'][$channel] ?? strtoupper($channel);
                                    $group = in_array($channel, ['bca', 'bni', 'bri', 'permata', 'cimb'], true) ? 'Virtual Account' : ($channel === 'qris' ? 'QRIS' : (in_array($channel, ['gopay', 'shopeepay'], true) ? 'E-Wallet' : 'Metode lain'));
                                @endphp
                                @if($group !== $previousGroup)<p class="checkout-method-group">{{ $group }}</p>@endif
                                <label class="checkout-method" for="channel-{{ $channel }}">
                                    <input id="channel-{{ $channel }}" type="radio" name="channel" value="{{ $channel }}" @checked(old('channel') === $channel) required>
                                    <span class="checkout-method-icon" aria-hidden="true"><x-dashboard.icon name="{{ $group === 'Virtual Account' ? 'wallet' : 'credit-card' }}" /></span>
                                    <span class="checkout-method-copy"><strong>{{ $label }}</strong><small>{{ $group }}</small></span>
                                    <span class="checkout-method-indicator" aria-hidden="true"></span>
                                </label>
                                @php($previousGroup = $group)
                            @endforeach
                        </fieldset>
                        @error('channel')<div class="checkout-field-error" role="alert">{{ $message }}</div>@enderror
                        <div class="checkout-submit-area"><div><span>Total pembayaran</span><strong>Rp {{ number_format($pending?->amount ?? ($course->discount_price ?? $course->price), 0, ',', '.') }}</strong></div><button type="submit" class="btn btn-primary checkout-submit" data-loading-text="Memproses pembayaran...">{{ $pending ? 'Lanjutkan pembayaran' : 'Bayar sekarang' }}<x-dashboard.icon name="arrow-right" /></button></div>
                    </form>
                @endif
            </div>
        </section>

    </div>
</div>
@endsection
