<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LocalDateTime
{
    public function toUtc(string $local, string $isoUtc, string $timezone, string $field): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $local)
            || ! preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/', $isoUtc)) {
            throw ValidationException::withMessages([$field => 'Isi tanggal dan waktu yang valid.']);
        }

        try {
            $instant = Carbon::parse($isoUtc)->utc();
        } catch (\Throwable) {
            throw ValidationException::withMessages([$field => 'Isi tanggal dan waktu yang valid.']);
        }

        if ($instant->copy()->setTimezone($timezone)->format('Y-m-d\TH:i') !== $local) {
            throw ValidationException::withMessages([$field => 'Zona waktu perangkat berubah. Muat ulang halaman, lalu pilih waktu kembali.']);
        }

        return $instant;
    }
}
