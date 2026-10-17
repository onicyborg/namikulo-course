<?php

namespace App\Console\Commands;

use App\Models\PaymentNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RedactPaymentNotifications extends Command
{
    protected $signature = 'payments:redact-notifications {--apply : Simpan payload yang sudah dibatasi}';

    protected $description = 'Hapus signature dan field sensitif dari payload notifikasi pembayaran lama';

    public function handle(): int
    {
        if (! Schema::hasTable('payment_notifications')) {
            $this->error('Tabel notifikasi pembayaran belum tersedia.');
            return self::FAILURE;
        }

        $changed = 0;
        $invalid = 0;
        DB::table('payment_notifications')->select('id', 'payload')->orderBy('id')
            ->chunkById(100, function ($rows) use (&$changed, &$invalid): void {
                foreach ($rows as $row) {
                    $payload = json_decode((string) $row->payload, true);
                    if (! is_array($payload)) {
                        $invalid++;
                        continue;
                    }
                    $safe = PaymentNotification::safePayload($payload);
                    if ($safe === $payload) continue;
                    $changed++;
                    if ($this->option('apply')) {
                        DB::table('payment_notifications')->where('id', $row->id)
                            ->update(['payload' => json_encode($safe, JSON_THROW_ON_ERROR)]);
                    }
                }
            });

        $this->info(($this->option('apply') ? 'Notifikasi diperbarui: ' : 'Notifikasi yang perlu diperbarui: ').$changed);
        if ($invalid) $this->warn('Payload tidak dapat dibaca dan perlu ditinjau terpisah: '.$invalid);

        return $invalid ? self::FAILURE : self::SUCCESS;
    }
}
