<?php

return [
    'seller_name' => env('INVOICE_SELLER_NAME', env('APP_NAME', 'Namikulo Course')),
    'seller_address' => env('INVOICE_SELLER_ADDRESS', ''),
    'seller_email' => env('INVOICE_SELLER_EMAIL', env('MAIL_FROM_ADDRESS', '')),
    'seller_tax_id' => env('INVOICE_SELLER_TAX_ID', ''),
];
