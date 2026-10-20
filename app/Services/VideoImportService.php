<?php

namespace App\Services;

use App\Enums\UploadStatus;
use App\Enums\VideoStatus;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoAsset;
use App\Models\VideoUpload;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VideoImportService
{
    public function __construct(private readonly HlsManifestValidator $manifests, private readonly ActivityLogger $logger) {}

    public function create(User $owner, Lesson $lesson, string $keyBase64, string $ivHex, int $duration): VideoUpload
    {
        $key = base64_decode($keyBase64, true);
        if ($key === false || strlen($key) !== 16 || ! preg_match('/^[a-fA-F0-9]{32}$/', $ivHex)) {
            throw ValidationException::withMessages(['key' => 'Kunci atau IV video tidak valid.']);
        }
        if ($duration < 1 || $duration > config('video.source_max_duration_seconds')) {
            throw ValidationException::withMessages(['duration_seconds' => 'Durasi video melewati batas yang diizinkan.']);
        }

        return DB::transaction(function () use ($owner, $lesson, $keyBase64, $ivHex, $duration): VideoUpload {
            User::query()->whereKey($owner->id)->lockForUpdate()->firstOrFail();
            $active = VideoUpload::query()->where('user_id', $owner->id)
                ->whereIn('status', [UploadStatus::Preparing->value, UploadStatus::Uploading->value, UploadStatus::Validating->value])
                ->where('expires_at', '>', now())->exists();
            if ($active) throw ValidationException::withMessages(['upload' => 'Selesaikan atau batalkan persiapan video yang masih aktif.']);

            $id = (string) Str::uuid7();
            $upload = VideoUpload::query()->create([
                'id' => $id, 'user_id' => $owner->id, 'lesson_id' => $lesson->id,
                'status' => UploadStatus::Preparing, 'encryption_key' => $keyBase64,
                'iv_hex' => strtolower($ivHex), 'duration_seconds' => $duration,
                'storage_path' => $lesson->id.'/'.$id.'/hls/source',
                'last_activity_at' => now(), 'expires_at' => now()->addHours(24),
            ]);
            $this->logger->log('created', $owner, 'video_uploads', $upload->id, null, ['lesson_id' => $lesson->id, 'status' => 'preparing']);
            return $upload;
        });
    }

    public function saveManifest(VideoUpload $upload, User $owner, string $manifest, array $segments): array
    {
        return $this->locked($upload, function () use ($upload, $owner, $manifest, $segments): array {
            $upload = $this->writable($upload, $owner, [UploadStatus::Preparing, UploadStatus::Uploading]);
            $parsed = $this->manifests->validate($manifest, $upload->iv_hex);
            $names = $parsed['names'];
            if (abs($parsed['duration'] - $upload->duration_seconds) > 10) $this->rejectSegments();
            if (! array_is_list($segments) || count($names) !== count($segments)) $this->rejectSegments();
            $total = 0;
            foreach ($segments as $index => $segment) {
                if (! is_array($segment) || ($segment['name'] ?? null) !== $names[$index]
                    || ! is_int($segment['size'] ?? null) || $segment['size'] < 16
                    || $segment['size'] > config('video.segment_max_size_mb') * 1024 * 1024
                    || $segment['size'] % 16 !== 0
                    || ! is_string($segment['sha256'] ?? null)
                    || ! preg_match('/^[a-f0-9]{64}$/', $segment['sha256'])) $this->rejectSegments();
                $total += $segment['size'];
            }
            if ($total > config('video.upload_max_size_mb') * 1024 * 1024) $this->rejectSegments();
            $payload = ['playlist' => $manifest, 'segments' => $segments];
            if ($upload->manifest !== null && $upload->manifest !== $payload) {
                throw ValidationException::withMessages(['manifest' => 'Manifest sesi ini sudah ditetapkan dan tidak dapat diganti.']);
            }
            $upload->update(['manifest' => $payload, 'expected_bytes' => $total,
                'status' => UploadStatus::Uploading, 'last_activity_at' => now()]);
            return $this->status($upload);
        });
    }

    public function saveChunk(VideoUpload $upload, User $owner, string $name, int $index, string $body, string $sha256): array
    {
        return $this->locked($upload, function () use ($upload, $owner, $name, $index, $body, $sha256): array {
            $upload = $this->writable($upload, $owner, [UploadStatus::Uploading]);
            if (! preg_match('/^seg_\d{5}\.ts$/', $name) || ! preg_match('/^[a-f0-9]{64}$/', $sha256)
                || strlen($body) < 1 || strlen($body) > config('video.chunk_max_bytes')
                || ! hash_equals($sha256, hash('sha256', $body))) $this->rejectSegments();

            $segments = $upload->manifest['segments'] ?? [];
            $segment = collect($segments)->firstWhere('name', $name);
            if (! $segment) $this->rejectSegments();
            $chunkCount = (int) ceil($segment['size'] / config('video.chunk_max_bytes'));
            $expectedSize = $index === $chunkCount - 1
                ? $segment['size'] - $index * config('video.chunk_max_bytes') : config('video.chunk_max_bytes');
            if ($index < 0 || $index >= $chunkCount || strlen($body) !== $expectedSize) $this->rejectSegments();

            $disk = Storage::disk(config('video.disk'));
            $path = $upload->storage_path.'/chunks/'.$name.'.'.$index;
            $isNew = ! $disk->exists($path);
            if (! $isNew) {
                if (! hash_equals(hash('sha256', $disk->get($path)), $sha256)) {
                    throw ValidationException::withMessages(['chunk' => 'Potongan dengan nomor ini sudah ada dengan isi berbeda.']);
                }
            } else {
                if (! $disk->put($path, $body)) {
                    throw ValidationException::withMessages(['chunk' => 'Potongan video gagal disimpan. Coba lagi.']);
                }
            }
            $upload->update(['received_bytes' => $upload->received_bytes + ($isNew ? strlen($body) : 0), 'last_activity_at' => now()]);
            return ['message' => 'Potongan diterima.', 'name' => $name, 'index' => $index];
        });
    }

    public function finalize(VideoUpload $upload, User $owner): array
    {
        return $this->locked($upload, function () use ($upload, $owner): array {
            $upload = $this->writable($upload, $owner, [UploadStatus::Uploading, UploadStatus::Validating, UploadStatus::Ready]);
            if ($upload->status === UploadStatus::Ready) return $this->status($upload);
            if (! isset($upload->manifest['playlist'], $upload->manifest['segments'])) $this->rejectSegments();
            if ($upload->received_bytes !== $upload->expected_bytes) $this->rejectSegments();
            $upload->update(['status' => UploadStatus::Validating, 'last_activity_at' => now()]);
            $started = microtime(true);
            $segments = $upload->manifest['segments'];
            while ($upload->validation_cursor < count($segments) && microtime(true) - $started < 8) {
                try {
                    $this->validateSegment($upload, $segments[$upload->validation_cursor]);
                } catch (ValidationException $exception) {
                    $upload->update(['status' => UploadStatus::Failed, 'error_message' => 'Validasi segmen gagal.']);
                    throw $exception;
                }
                $upload->update(['validation_cursor' => $upload->validation_cursor + 1, 'last_activity_at' => now()]);
            }
            if ($upload->validation_cursor < count($segments)) return $this->status($upload);

            $disk = Storage::disk(config('video.disk'));
            if (! $disk->put($upload->storage_path.'/index.m3u8', $upload->manifest['playlist'])) {
                throw ValidationException::withMessages(['manifest' => 'Playlist video gagal disimpan. Coba lagi.']);
            }
            DB::transaction(function () use ($upload): void {
                Lesson::query()->whereKey($upload->lesson_id)->lockForUpdate()->firstOrFail();
                $asset = VideoAsset::query()->where('lesson_id', $upload->lesson_id)->lockForUpdate()->first();
                $oldPath = $asset?->hls_path;
                $values = ['status' => VideoStatus::Ready, 'original_path' => null,
                    'hls_path' => $upload->storage_path, 'encryption_key' => $upload->encryption_key,
                    'renditions' => ['source'], 'duration_seconds' => $upload->duration_seconds,
                    'size_bytes' => $upload->expected_bytes, 'error_message' => null, 'processed_at' => now()];
                if ($asset) $asset->update($values);
                else $asset = VideoAsset::query()->create(['lesson_id' => $upload->lesson_id] + $values);
                Lesson::query()->whereKey($upload->lesson_id)->update(['duration_seconds' => $upload->duration_seconds]);
                if ($oldPath && $oldPath !== $upload->storage_path) {
                    VideoUpload::query()->where('storage_path', $oldPath)->where('status', UploadStatus::Ready->value)
                        ->update(['status' => UploadStatus::Replaced]);
                }
                $upload->update(['status' => UploadStatus::Ready, 'last_activity_at' => now()]);
                $this->logger->log('updated', $upload->user, 'video_assets', $asset?->id, null,
                    ['lesson_id' => $upload->lesson_id, 'upload_id' => $upload->id, 'status' => 'ready']);
            });
            $disk->deleteDirectory($upload->storage_path.'/chunks');
            return $this->status($upload);
        });
    }

    public function cancel(VideoUpload $upload, User $owner): array
    {
        return $this->locked($upload, function () use ($upload, $owner): array {
            $upload = $this->writable($upload, $owner, [UploadStatus::Preparing, UploadStatus::Uploading, UploadStatus::Validating]);
            $upload->update(['status' => UploadStatus::Cancelled, 'last_activity_at' => now()]);
            $this->logger->log('updated', $owner, 'video_uploads', $upload->id, null, ['status' => 'cancelled']);
            return $this->status($upload);
        });
    }

    public function status(VideoUpload $upload): array
    {
        return ['status' => $upload->status->value, 'validated' => $upload->validation_cursor,
            'total' => count($upload->manifest['segments'] ?? []), 'expires_at' => $upload->expires_at?->toIso8601String()];
    }

    private function writable(VideoUpload $upload, User $owner, array $allowed): VideoUpload
    {
        $upload = VideoUpload::query()->whereKey($upload->id)->firstOrFail();
        abort_unless($upload->user_id === $owner->id && $upload->lesson !== null, 404);
        if ($upload->status === UploadStatus::Ready && in_array(UploadStatus::Ready, $allowed, true)) return $upload;
        if ($upload->expires_at->isPast()) {
            $upload->update(['status' => UploadStatus::Expired]);
            throw ValidationException::withMessages(['upload' => 'Sesi impor telah kedaluwarsa. Mulai lagi dari file lokal.']);
        }
        if (! in_array($upload->status, $allowed, true)) {
            throw ValidationException::withMessages(['upload' => 'Sesi impor tidak berada pada tahap yang sesuai.']);
        }
        return $upload;
    }

    private function locked(VideoUpload $upload, callable $callback): mixed
    {
        $lock = Cache::store('database')->lock('video-upload:'.$upload->id, 300);
        if (! $lock->get()) abort(409, 'Sesi impor sedang diproses. Coba lagi sebentar.');
        try { return $callback(); } finally { $lock->release(); }
    }

    private function validateSegment(VideoUpload $upload, array $segment): void
    {
        $disk = Storage::disk(config('video.disk'));
        $name = $segment['name'];
        $path = $upload->storage_path.'/'.$name;
        // Only one bounded segment is staged locally; durable media lives on the configured disk.
        $out = tmpfile();
        if ($out === false) $this->rejectSegments();
        $hash = hash_init('sha256');
        $size = 0;
        try {
            $chunkCount = (int) ceil($segment['size'] / config('video.chunk_max_bytes'));
            for ($index = 0; $index < $chunkCount; $index++) {
                $part = $upload->storage_path.'/chunks/'.$name.'.'.$index;
                if (! $disk->exists($part)) $this->rejectSegments();
                $input = $disk->readStream($part);
                if ($input === false) $this->rejectSegments();
                try {
                    while (! feof($input)) {
                        $bytes = fread($input, 65536);
                        if ($bytes === false || ($bytes === '' && ! feof($input))) $this->rejectSegments();
                        $size += strlen($bytes);
                        if ($size > $segment['size']) $this->rejectSegments();
                        hash_update($hash, $bytes);
                        if (fwrite($out, $bytes) !== strlen($bytes)) $this->rejectSegments();
                    }
                } finally { fclose($input); }
            }
            if ($size !== $segment['size'] || ! hash_equals($segment['sha256'], hash_final($hash))) $this->rejectSegments();
            if (! rewind($out)) $this->rejectSegments();
            $ciphertext = stream_get_contents($out);
            if ($ciphertext === false || strlen($ciphertext) % 16 !== 0) $this->rejectSegments();
            $key = base64_decode($upload->encryption_key, true);
            $plain = openssl_decrypt($ciphertext, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, hex2bin($upload->iv_hex));
            if ($plain === false || strlen($plain) < 188 || strlen($plain) % 188 !== 0) $this->rejectSegments();
            for ($offset = 0, $length = strlen($plain); $offset < $length; $offset += 188) {
                if ($plain[$offset] !== "\x47") $this->rejectSegments();
            }
            unset($ciphertext, $plain);
            if (! rewind($out) || ! $disk->writeStream($path, $out)) {
                throw ValidationException::withMessages(['segments' => 'Segmen video gagal disimpan. Coba lagi.']);
            }
        } finally { fclose($out); }
    }

    private function rejectSegments(): never
    {
        throw ValidationException::withMessages(['segments' => 'Data segmen video tidak lengkap atau tidak sesuai.']);
    }
}
