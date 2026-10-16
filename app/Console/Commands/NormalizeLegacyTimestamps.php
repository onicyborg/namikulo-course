<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NormalizeLegacyTimestamps extends Command
{
    protected $signature = 'timezones:normalize-existing {--legacy-db-zone= : Timezone koneksi MySQL/MariaDB sebelum cutover, misalnya UTC atau Asia/Jakarta} {--dry-run : Tampilkan contoh konversi tanpa mengubah database}';

    protected $description = 'Ubah timestamp lama Asia/Jakarta menjadi UTC satu kali';

    private const COLUMNS = [
        'users' => ['email_verified_at', 'last_login_at', 'created_at', 'updated_at'],
        'password_reset_tokens' => ['created_at'],
        'system_logs' => ['created_at'],
        'categories' => ['created_at', 'updated_at'],
        'courses' => ['published_at', 'created_at', 'updated_at'],
        'sections' => ['created_at', 'updated_at'],
        'lessons' => ['created_at', 'updated_at'],
        'video_assets' => ['processed_at', 'created_at', 'updated_at'],
        'orders' => ['expires_at', 'paid_at', 'cancelled_at', 'created_at', 'updated_at'],
        'enrollments' => ['starts_at', 'expires_at', 'revoked_at', 'created_at', 'updated_at'],
        'operation_states' => ['last_started_at', 'last_succeeded_at', 'next_attempt_at', 'created_at', 'updated_at'],
    ];

    public function handle(): int
    {
        if (config('app.timezone') !== 'UTC') {
            $this->error('Atur APP_TIMEZONE=UTC dan jalankan php artisan optimize:clear terlebih dahulu.');
            return self::FAILURE;
        }

        $driver = DB::getDriverName();
        if (! in_array($driver, ['pgsql', 'mysql', 'mariadb'], true)) {
            $this->error('Perintah ini hanya mendukung PostgreSQL, MySQL, dan MariaDB.');
            return self::FAILURE;
        }

        $legacyDbZone = $this->option('legacy-db-zone');
        if ($driver !== 'pgsql' && ! $legacyDbZone) {
            $this->error('MySQL/MariaDB memerlukan --legacy-db-zone sesuai zona koneksi database sebelumnya.');
            return self::FAILURE;
        }
        if ($legacyDbZone) {
            try {
                new \DateTimeZone($legacyDbZone);
            } catch (\Throwable) {
                $this->error('Zona waktu koneksi lama tidak valid.');
                return self::FAILURE;
            }
        }

        if (! Schema::hasTable('operation_states')) {
            $this->error('Jalankan migration Phase 2 sebelum normalisasi waktu.');
            return self::FAILURE;
        }
        if (DB::table('operation_states')->where('task', 'legacy-utc-cutover')->exists()) {
            $this->info('Timestamp lama sudah dinormalisasi. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach (self::COLUMNS as $table => $columns) {
                if (! Schema::hasTable($table)) continue;
                foreach ($columns as $column) {
                    $row = DB::table($table)->whereNotNull($column)->first([$column]);
                    if (! $row) continue;
                    $value = (string) $row->{$column};
                    $this->line($table.'.'.$column.': '.$value.' → '.$this->convert($value, $driver, $legacyDbZone));
                    break;
                }
            }
            $this->info('Pratinjau selesai. Tidak ada data yang diubah.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($driver, $legacyDbZone): void {
            foreach (self::COLUMNS as $table => $columns) {
                if (! Schema::hasTable($table)) continue;
                $key = $table === 'password_reset_tokens' ? 'email' : 'id';
                $select = array_merge([$key], $columns);
                if ($table === 'system_logs') $select = array_merge($select, ['old_values', 'new_values']);

                DB::table($table)->select($select)->orderBy($key)->chunk(200, function ($rows) use ($table, $key, $columns, $driver, $legacyDbZone): void {
                    foreach ($rows as $row) {
                        $updates = [];
                        foreach ($columns as $column) {
                            if ($row->{$column} !== null) {
                                $updates[$column] = $this->convert((string) $row->{$column}, $driver, $legacyDbZone);
                            }
                        }
                        if ($table === 'system_logs') {
                            foreach (['old_values', 'new_values'] as $field) {
                                if ($row->{$field} !== null) {
                                    $values = json_decode($row->{$field}, true, 512, JSON_THROW_ON_ERROR);
                                    $updates[$field] = json_encode($this->convertLogValues($values), JSON_THROW_ON_ERROR);
                                }
                            }
                        }
                        if ($updates !== []) DB::table($table)->where($key, $row->{$key})->update($updates);
                    }
                });
            }

            DB::table('operation_states')->insert([
                'id' => (string) Str::uuid7(), 'task' => 'legacy-utc-cutover',
                'last_succeeded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }, 1);

        $this->info('Timestamp lama dinormalisasi menjadi UTC.');
        return self::SUCCESS;
    }

    private function convert(string $value, string $driver, ?string $legacyDbZone): string
    {
        if ($driver === 'pgsql') {
            return Carbon::parse($value, 'Asia/Jakarta')->utc()->format('Y-m-d H:i:s');
        }

        $legacyWallTime = Carbon::parse($value, 'UTC')->setTimezone($legacyDbZone)->format('Y-m-d H:i:s');
        return Carbon::parse($legacyWallTime, 'Asia/Jakarta')->utc()->format('Y-m-d H:i:s');
    }

    private function convertLogValues(mixed $values): mixed
    {
        if (! is_array($values)) return $values;

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->convertLogValues($value);
            } elseif (is_string($key) && str_ends_with($key, '_at') && is_string($value)
                && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
                $values[$key] = Carbon::parse($value, 'Asia/Jakarta')->utc()->format('Y-m-d H:i:s');
            }
        }

        return $values;
    }
}
