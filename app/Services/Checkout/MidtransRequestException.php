<?php

namespace App\Services\Checkout;

use RuntimeException;

class MidtransRequestException extends RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly ?string $gatewayStatusCode,
    ) {
        parent::__construct('Layanan pembayaran belum memberi respons yang dapat digunakan.');
    }
}
