<?php

namespace App\Enums;

enum EnrollmentSource: string
{
    case Admin = 'admin';
    case Purchase = 'purchase';

    public function label(): string
    {
        return $this === self::Admin ? 'Admin' : 'Pembelian';
    }
}
