<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Initiating = 'initiating';
    case Unknown = 'unknown';
    case Pending = 'pending';
    case Settlement = 'settlement';
    case Capture = 'capture';
    case Deny = 'deny';
    case Cancel = 'cancel';
    case Expire = 'expire';
    case Failure = 'failure';
    case Refund = 'refund';

    public function isPaid(): bool
    {
        return in_array($this, [self::Settlement, self::Capture], true);
    }
}
