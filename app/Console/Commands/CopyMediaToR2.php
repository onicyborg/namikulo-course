<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CopyMediaToR2 extends Command
{
    protected $signature = 'storage:migrate-r2
        {--group=all : all, public, video, or private}
        {--copy : Copy missing objects and verify their SHA-256}
        {--verify : Verify every local object against R2 without writing}';

    protected $description = 'Inventory or copy local media to private R2 prefixes without deleting originals';

    public function handle(): int
    {
        $groups = [
            'public' => ['app/public', 'r2_public'],
            'video' => ['app/video-private', 'r2_video'],
            'private' => ['app/private', 'r2_private'],
        ];
        $group = $this->option('group');
        if ($group !== 'all' && ! isset($groups[$group])) {
            $this->error('Group harus all, public, video, atau private.');
            return self::FAILURE;
        }
        if ($this->option('copy') && $this->option('verify')) {
            $this->error('Pilih --copy atau --verify, bukan keduanya.');
            return self::FAILURE;
        }
        if ($group !== 'all') $groups = [$group => $groups[$group]];
        $active = $this->option('copy') || $this->option('verify');
        if ($active && ! app()->isDownForMaintenance()) {
            $this->error('Aktifkan maintenance dengan php artisan down dan jeda cron sebelum copy/verify.');
            return self::FAILURE;
        }
        if ($active) {
            foreach (['key', 'secret', 'bucket', 'endpoint'] as $setting) {
                if (! config('filesystems.disks.r2_video.'.$setting)) {
                    $this->error('Konfigurasi R2 belum lengkap. Isi AWS_* lalu bangun ulang config cache.');
                    return self::FAILURE;
                }
            }
        }
        try {
            foreach ($groups as $name => [$directory, $targetName]) {
                $source = Storage::build(['driver' => 'local', 'root' => storage_path($directory), 'throw' => true]);
                $target = $active ? Storage::disk($targetName) : null;
                $count = $bytes = $copied = 0;
                foreach ($source->getDriver()->listContents('', true) as $entry) {
                    if (! $entry->isFile() || basename($entry->path()) === '.gitignore') continue;
                    $path = $entry->path();
                    $size = $source->size($path);
                    if ($active) {
                        if (! $target->exists($path)) {
                            if (! $this->option('copy')) throw new RuntimeException('Objek belum ada di R2: '.$name.'/'.$path);
                            $stream = $source->readStream($path);
                            if (! is_resource($stream)) throw new RuntimeException('File lokal gagal dibaca.');
                            try {
                                if (! $target->writeStream($path, $stream)) throw new RuntimeException('Objek gagal disimpan di R2.');
                            } finally { fclose($stream); }
                            $copied++;
                        }
                        if ($target->size($path) !== $size || ! hash_equals($this->digest($source, $path), $this->digest($target, $path))) {
                            $this->error('Isi berbeda: '.$name.'/'.$path.'. Objek tidak ditimpa; periksa sebelum cutover.');
                            return self::FAILURE;
                        }
                    }
                    $count++;
                    $bytes += $size;
                    if ($active && $count % 100 === 0) $this->line($name.': '.$count.' file terverifikasi.');
                }
                $this->line($name.': '.$count.' file, '.$bytes.' byte'.($active ? ', '.$copied.' disalin, seluruh file lokal terverifikasi.' : ' (inventaris lokal).'));
            }
        } catch (\Throwable $exception) {
            // SDK exception messages may contain signed request URLs or credentials.
            $this->error('Migrasi dihentikan ('.$exception::class.'). Periksa koneksi, izin bucket, ruang disk, dan konfigurasi; file lokal tetap tersedia.');
            return self::FAILURE;
        }
        $this->info($active ? 'Selesai. File lokal tidak dihapus dan konfigurasi disk tidak diubah.' : 'Inventaris selesai. Gunakan --copy saat maintenance untuk menyalin ke R2.');
        return self::SUCCESS;
    }

    private function digest(FilesystemAdapter $disk, string $path): string
    {
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) throw new RuntimeException('Stream verifikasi tidak tersedia.');
        try {
            $hash = hash_init('sha256');
            while (! feof($stream)) {
                $data = fread($stream, 1024 * 1024);
                if ($data === false || ($data === '' && ! feof($stream))) throw new RuntimeException('Stream verifikasi terputus.');
                hash_update($hash, $data);
            }
            return hash_final($hash);
        } finally { fclose($stream); }
    }
}
