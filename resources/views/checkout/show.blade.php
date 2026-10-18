@extends('layouts.member')
@section('title', 'Checkout '.$course->title)
@push('styles')<link rel="stylesheet" href="{{ url('css/checkout.css') }}">@endpush
@section('content')
<div class="container checkout-page">
    <nav class="site-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('site.courses.show', $course->slug) }}">{{ $course->title }}</a><span aria-hidden="true">/</span><span aria-current="page">Checkout</span></nav>
    <div class="checkout-heading"><span>PEMBELIAN COURSE</span><h1>Periksa pesanan Anda</h1><p>Pilih metode pembayaran yang tersedia, lalu ikuti instruksi pada pesanan.</p></div>
    @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    @if($pending)<div class="alert alert-info" role="status">Anda memiliki <a href="{{ route('orders.pay', $pending) }}">pesanan yang belum selesai</a>. Memilih metode lain akan membatalkan percobaan pembayaran sebelumnya pada pesanan yang sama.</div>@endif
    <div class="checkout-grid">
        <section class="checkout-panel" aria-labelledby="checkout-course-title">
            @if($course->thumbnail_path)<img class="checkout-course-image" src="{{ url('storage/'.$course->thumbnail_path) }}" alt="Thumbnail course {{ $course->title }}" width="640" height="360">@endif
            <div class="checkout-panel-body"><p class="checkout-kicker">COURSE</p><h2 id="checkout-course-title">{{ $course->title }}</h2>
                @if($course->summary)<p>{{ $course->summary }}</p>@endif
                <div class="checkout-summary-line"><span>Akses</span><strong>{{ $course->access_months }} bulan sejak pembayaran terkonfirmasi</strong></div>
                <div class="checkout-summary-line checkout-total"><span>Total</span><strong>Rp {{ number_format($pending?->amount ?? ($course->discount_price ?? $course->price), 0, ',', '.') }}</strong></div>
            </div>
        </section>
        <section class="checkout-panel" aria-labelledby="checkout-method-title">
            <div class="checkout-panel-body"><h2 id="checkout-method-title">Metode pembayaran</h2><p>Metode yang aktif untuk merchant saat ini.</p>
                @if(empty($channels))
                    <div class="alert alert-warning">Belum ada metode pembayaran yang tersedia.</div>
                @else
                    <form method="POST" action="{{ route('checkout.store', $course->slug) }}" data-loading-form>@csrf
                        <fieldset class="checkout-methods"><legend class="sr-only">Pilih metode pembayaran</legend>
                            @foreach($channels as $channel)
                                @php($label = ['bca' => 'BCA Virtual Account', 'bni' => 'BNI Virtual Account', 'bri' => 'BRI Virtual Account', 'permata' => 'Permata Virtual Account', 'cimb' => 'CIMB Virtual Account', 'qris' => 'QRIS', 'gopay' => 'GoPay', 'shopeepay' => 'ShopeePay'][$channel])
                                <label class="checkout-method" for="channel-{{ $channel }}"><input id="channel-{{ $channel }}" type="radio" name="channel" value="{{ $channel }}" @checked(old('channel') === $channel) required><span>{{ $label }}</span></label>
                            @endforeach
                        </fieldset>
                        @error('channel')<div class="text-danger" role="alert">{{ $message }}</div>@enderror
                        <button type="submit" class="btn btn-primary btn-block checkout-submit" data-loading-text="Memproses pembayaran...">{{ $pending ? 'Lanjutkan pembayaran' : 'Bayar sekarang' }}</button>
                    </form>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
