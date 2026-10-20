<?php

namespace Tests\Feature;

use App\Models\VideoUpload;
use App\Services\ActivityLogger;
use App\Services\HlsManifestValidator;
use App\Services\VideoImportService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class R2StorageTest extends TestCase
{
    public function test_public_images_are_served_without_exposing_other_bucket_prefixes(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/example.png', 'image bytes');
        Storage::disk('public')->put('editor-images/private.png', 'private bytes');

        $this->get('/media/public/avatars/example.png')->assertOk()
            ->assertHeader('Content-Type', 'image/png')->assertStreamedContent('image bytes');
        $this->get('/media/public/editor-images/private.png')->assertNotFound();
        $this->get('/media/public/video-private/segment.ts')->assertNotFound();
        $this->get('/media/public/avatars/missing.png')->assertNotFound();
    }

    public function test_r2_public_urls_use_application_route_with_bucket_prefix(): void
    {
        config(['filesystems.disks.r2_public.key' => 'test-key',
            'filesystems.disks.r2_public.secret' => 'test-secret',
            'filesystems.disks.r2_public.region' => 'auto',
            'filesystems.disks.r2_public.bucket' => 'test-bucket',
            'filesystems.disks.r2_public.endpoint' => 'https://example.r2.cloudflarestorage.com',
            'filesystems.disks.r2_public.url' => '/media']);
        $this->assertSame('/media/public/avatars/example.png', Storage::disk('r2_public')->url('avatars/example.png'));
        $this->assertSame('private', Storage::disk('r2_public')->getConfig()['visibility']);
    }

    public function test_video_segment_is_assembled_and_validated_using_streams(): void
    {
        $this->validateSegment(false);
    }

    public function test_corrupted_segment_is_rejected_before_writing_final_object(): void
    {
        $this->expectException(ValidationException::class);
        $this->validateSegment(true);
    }

    public function test_migration_copies_verifies_and_preserves_source(): void
    {
        $source = Storage::fake('migration-source');
        $target = Storage::fake('r2_public');
        $source->put('avatars/old.png', 'original bytes');
        $this->prepareMigration($source, $target);
        $this->artisan('storage:migrate-r2', ['--copy' => true, '--group' => 'public'])->assertSuccessful();
        $this->assertSame('original bytes', $target->get('avatars/old.png'));
        $this->assertSame('original bytes', $source->get('avatars/old.png'));
        $this->artisan('storage:migrate-r2', ['--verify' => true, '--group' => 'public'])->assertSuccessful();
    }

    public function test_migration_refuses_to_overwrite_different_existing_object(): void
    {
        $source = Storage::fake('migration-source');
        $target = Storage::fake('r2_public');
        $source->put('avatars/old.png', 'original bytes');
        $target->put('avatars/old.png', 'modified bytes');
        $this->prepareMigration($source, $target);
        $this->artisan('storage:migrate-r2', ['--copy' => true, '--group' => 'public'])->assertFailed();
        $this->assertSame('modified bytes', $target->get('avatars/old.png'));
        $this->assertSame('original bytes', $source->get('avatars/old.png'));
    }

    private function prepareMigration(FilesystemAdapter $source, FilesystemAdapter $target): void
    {
        $maintenance = Mockery::mock(MaintenanceMode::class);
        $maintenance->shouldReceive('active')->andReturn(true);
        $this->app->instance(MaintenanceMode::class, $maintenance);
        foreach (['key', 'secret', 'bucket', 'endpoint'] as $setting) {
            config(['filesystems.disks.r2_video.'.$setting => 'test-value']);
        }
        Storage::shouldReceive('build')->andReturn($source);
        Storage::shouldReceive('disk')->with('r2_public')->andReturn($target);
    }

    private function validateSegment(bool $corrupt): void
    {
        config(['video.chunk_max_bytes' => 128]);
        $key = random_bytes(16);
        $iv = random_bytes(16);
        $plain = str_repeat("\x47".str_repeat("\0", 187), 4);
        $cipher = openssl_encrypt($plain, 'aes-128-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $chunks = str_split($cipher, 128);
        $upload = new VideoUpload(['storage_path' => 'lesson/upload/hls/source',
            'encryption_key' => base64_encode($key), 'iv_hex' => bin2hex($iv)]);
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldNotReceive('path');
        $disk->shouldReceive('exists')->andReturn(true);
        $disk->shouldReceive('readStream')->andReturnUsing(function ($path) use ($chunks) {
            $index = (int) substr($path, strrpos($path, '.') + 1);
            $stream = fopen('php://temp', 'w+b');
            fwrite($stream, $chunks[$index]);
            rewind($stream);
            return $stream;
        });
        if ($corrupt) $disk->shouldNotReceive('writeStream');
        else $disk->shouldReceive('writeStream')->once()->withArgs(function ($path, $stream) use ($cipher) {
            return $path === 'lesson/upload/hls/source/seg_00000.ts' && stream_get_contents($stream) === $cipher;
        })->andReturn(true);
        Storage::shouldReceive('disk')->with(config('video.disk'))->andReturn($disk);
        $service = new VideoImportService(new HlsManifestValidator, new ActivityLogger);
        $method = new \ReflectionMethod($service, 'validateSegment');
        $method->invoke($service, $upload, ['name' => 'seg_00000.ts', 'size' => strlen($cipher),
            'sha256' => $corrupt ? str_repeat('0', 64) : hash('sha256', $cipher)]);
    }
}
