@extends('layouts.admin')
@section('title', 'Detail pesanan')
@section('content')
<x-page-header title="Detail pesanan" parent="Pesanan" :parentUrl="route('admin.orders.index')" />
<div class="section-body na-order-detail"><x-flash-message />@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
    <div class="row"><div class="col-12 col-lg-7"><div class="card"><div class="card-header"><h2>{{ $order->order_number }}</h2></div><div class="card-body"><dl class="row">
        <dt class="col-sm-4">Member</dt><dd class="col-sm-8">{{ $order->user->name }} · {{ $order->user->email }}</dd>
        <dt class="col-sm-4">Course</dt><dd class="col-sm-8">{{ $order->course_title }}</dd>
        <dt class="col-sm-4">Nominal</dt><dd class="col-sm-8">Rp {{ number_format($order->amount, 0, ',', '.') }}</dd>
        <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><span class="nd-badge nd-badge--{{ $order->status->value }}">{{ ['pending' => 'Menunggu', 'paid' => 'Lunas', 'expired' => 'Kedaluwarsa', 'cancelled' => 'Dibatalkan', 'failed' => 'Gagal', 'refunded' => 'Perlu tinjauan'][$order->status->value] }}</span></dd>
        @if($order->paid_at)<dt class="col-sm-4">Dibayar</dt><dd class="col-sm-8"><time datetime="{{ $order->paid_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->paid_at->copy()->utc()->format('d M Y H:i') }} UTC</time></dd>@endif
        @if($order->enrollment)<dt class="col-sm-4">Akses berakhir</dt><dd class="col-sm-8"><time datetime="{{ $order->enrollment->expires_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $order->enrollment->expires_at->copy()->utc()->format('d M Y H:i') }} UTC</time></dd>@endif
        <dt class="col-sm-4">Email konfirmasi</dt><dd class="col-sm-8">{{ $order->payment_email_sent_at ? 'Terkirim' : ($order->payment_email_last_attempt_at ? 'Pernah dicoba; perlu ditinjau' : 'Belum dicoba') }}</dd>
    </dl><div class="d-flex flex-wrap" style="gap: 8px"><form method="POST" action="{{ route('admin.orders.sync', $order) }}" data-loading-form>@csrf<button class="btn btn-primary" type="submit">Sinkronkan status</button></form>@if($order->status === \App\Enums\OrderStatus::Paid && ! $order->payment_email_sent_at)<form method="POST" action="{{ route('admin.orders.retry-email', $order) }}" data-loading-form>@csrf<button class="btn btn-outline-primary" type="submit">Kirim ulang email</button></form>@endif</div>
    </div></div></div><div class="col-12 col-lg-5"><div class="card"><div class="card-header"><h2>Percobaan pembayaran</h2></div><div class="card-body">
        @forelse($order->payments as $payment)<div class="mb-3 pb-3 border-bottom"><strong>{{ strtoupper($payment->channel ?: $payment->payment_type) }}</strong><span class="badge badge-light ml-2">{{ $payment->status->value }}</span><small class="d-block text-muted">{{ $payment->midtrans_order_id }}</small>@if($payment->transaction_id)<small class="d-block text-muted">ID transaksi: {{ $payment->transaction_id }}</small>@endif<small class="d-block text-muted">Email instruksi: {{ $payment->instruction_email_sent_at ? 'Terkirim' : ($payment->instruction_email_last_attempt_at ? 'Pernah dicoba; perlu ditinjau' : 'Belum dicoba') }}</small>@if($order->status === \App\Enums\OrderStatus::Pending && $payment->status === \App\Enums\PaymentStatus::Pending && ! $payment->expires_at?->isPast() && ! $payment->instruction_email_sent_at)<form method="POST" action="{{ route('admin.orders.retry-instructions-email', [$order, $payment]) }}" class="mt-2" data-loading-form>@csrf<button class="btn btn-outline-primary btn-sm" type="submit">Kirim ulang instruksi</button></form>@endif</div>@empty<p>Belum ada percobaan pembayaran.</p>@endforelse
    </div></div></div></div>
</div>
@endsection
