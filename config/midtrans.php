<?php

return [
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'merchant_id' => env('MIDTRANS_MERCHANT_ID', ''),
    'server_key' => env('MIDTRANS_SERVER_KEY', ''),
    'client_key' => env('MIDTRANS_CLIENT_KEY', ''),
    'enabled_channels' => array_values(array_intersect(
        array_map('trim', explode(',', (string) env('MIDTRANS_ENABLED_CHANNELS', 'bca,bni,bri,permata,cimb,qris,gopay,shopeepay'))),
        ['bca', 'bni', 'bri', 'permata', 'cimb', 'qris', 'gopay', 'shopeepay']
    )),
    'timeout_seconds' => 8,
];
