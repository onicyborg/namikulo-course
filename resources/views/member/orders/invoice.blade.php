<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 38px 44px 36px; }
        body { margin: 0; color: #1b2437; font-family: 'DejaVu Sans', sans-serif; font-size: 10px; line-height: 1.5; }
        h1, h2, p { margin: 0; }
        .header { width: 100%; border-collapse: collapse; border-top: 5px solid #4e5fd6; }
        .header td { padding-top: 22px; vertical-align: top; }
        .brand { color: #1b2437; font-size: 13px; font-weight: bold; letter-spacing: 1.5px; }
        .document-title { color: #4e5fd6; font-size: 25px; font-weight: bold; text-align: right; letter-spacing: 1px; }
        .subtle { color: #64728c; }
        .intro { margin: 28px 0 24px; border-bottom: 1px solid #dfe4ef; padding-bottom: 20px; }
        .intro-table, .parties, .items, .totals, .payment { width: 100%; border-collapse: collapse; }
        .intro-table td { vertical-align: top; }
        .invoice-number { margin-top: 7px; font-size: 14px; font-weight: bold; }
        .status { display: inline-block; padding: 7px 13px; color: #176448; background: #e3f5ec; font-size: 10px; font-weight: bold; }
        .label { color: #64728c; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        .parties { margin-bottom: 27px; }
        .parties td { width: 50%; vertical-align: top; padding-right: 22px; }
        .party-name { margin: 10px 0 5px; font-size: 12px; font-weight: bold; }
        .party-line { margin-bottom: 3px; color: #40506c; word-wrap: break-word; }
        .items th { padding: 11px 9px; background: #eef0fe; color: #2f4167; font-size: 9px; text-align: left; text-transform: uppercase; letter-spacing: .5px; }
        .items td { padding: 14px 9px; vertical-align: top; border-bottom: 1px solid #dfe4ef; }
        .items .right, .totals .right { text-align: right; }
        .items .center { text-align: center; }
        .item-title { font-size: 11px; font-weight: bold; }
        .totals { width: 45%; margin: 20px 0 0 auto; }
        .totals td { padding: 7px 2px; }
        .totals .grand td { padding-top: 11px; border-top: 2px solid #4e5fd6; font-size: 12px; font-weight: bold; }
        .payment { margin-top: 38px; border-top: 1px solid #dfe4ef; }
        .payment td { width: 50%; padding-top: 17px; vertical-align: top; }
        .payment strong { display: block; margin-top: 6px; font-size: 10px; }
        .footer { margin-top: 42px; padding-top: 13px; border-top: 1px solid #dfe4ef; color: #64728c; font-size: 9px; }
    </style>
</head>
<body>
    @php $issuedAt = $invoice->issued_at->copy()->timezone($invoice->display_timezone); @endphp
    <table class="header"><tr>
        <td><p class="brand">{{ strtoupper(config('app.name')) }}</p><p class="subtle" style="margin-top:7px;">Dokumen pembayaran course</p></td>
        <td class="document-title">INVOICE</td>
    </tr></table>

    <div class="intro"><table class="intro-table"><tr>
        <td><p class="label">Nomor invoice</p><p class="invoice-number">{{ $invoice->invoice_number }}</p><p class="subtle" style="margin-top:5px;">Pesanan {{ $invoice->order_number }}</p></td>
        <td style="text-align:right;"><span class="status">LUNAS</span><p style="margin-top:11px;">{{ $issuedAt->format('d M Y H:i') }} ({{ $invoice->display_timezone }})</p></td>
    </tr></table></div>

    <table class="parties"><tr>
        <td><p class="label">Diterbitkan oleh</p><p class="party-name">{{ $invoice->seller_name }}</p>
            @if($invoice->seller_address)<p class="party-line">{!! nl2br(e($invoice->seller_address)) !!}</p>@endif
            @if($invoice->seller_email)<p class="party-line">{{ $invoice->seller_email }}</p>@endif
            @if($invoice->seller_tax_id)<p class="party-line">NPWP: {{ $invoice->seller_tax_id }}</p>@endif
        </td>
        <td><p class="label">Ditagihkan kepada</p><p class="party-name">{{ $invoice->buyer_name }}</p><p class="party-line">{{ $invoice->buyer_email }}</p></td>
    </tr></table>

    <table class="items">
        <thead><tr><th style="width:48%;">Deskripsi</th><th class="center" style="width:10%;">Jumlah</th><th class="right" style="width:21%;">Harga</th><th class="right" style="width:21%;">Total</th></tr></thead>
        <tbody><tr><td><p class="item-title">{{ $invoice->course_title }}</p><p class="subtle" style="margin-top:4px;">Akses course digital</p></td><td class="center">1</td><td class="right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td><td class="right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td></tr></tbody>
    </table>

    <table class="totals"><tr><td class="subtle">Subtotal</td><td class="right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td></tr><tr class="grand"><td>Total dibayar</td><td class="right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td></tr></table>

    <table class="payment"><tr>
        <td><p class="label">Tanggal pembayaran</p><strong>{{ $issuedAt->format('d M Y H:i') }} ({{ $invoice->display_timezone }})</strong></td>
        <td><p class="label">Metode pembayaran</p><strong>{{ $invoice->payment_method ?: 'Midtrans' }}</strong></td>
    </tr></table>

    <p class="footer">Invoice ini dibuat dari pembayaran yang telah dikonfirmasi. Simpan dokumen ini sebagai bukti transaksi Anda.</p>
</body>
</html>
