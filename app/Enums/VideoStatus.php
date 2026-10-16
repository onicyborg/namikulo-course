<?php

namespace App\Enums;

enum VideoStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum disiapkan',
            self::Processing => 'Diproses',
            self::Ready => 'Siap',
            self::Failed => 'Gagal',
        };
    }
}
