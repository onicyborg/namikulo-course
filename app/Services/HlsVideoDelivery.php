<?php

namespace App\Services;

use App\Enums\VideoStatus;
use App\Models\Lesson;
use App\Models\PlaybackSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HlsVideoDelivery implements VideoDelivery
{
    public function __construct(private readonly EnrollmentService $enrollments, private readonly ActivityLogger $logger,
        private readonly LessonSequence $sequence) {}

    public function startSession(User $user, Lesson $lesson): array
    {
        $lesson->loadMissing(['course', 'videoAsset']);
        abort_unless($user->isAdmin() || $this->enrollments->activeFor($user, $lesson->course), 403, 'Akses course tidak aktif.');
        $this->sequence->assertAccessible($user, $lesson);
        $asset = $lesson->videoAsset;
        abort_unless($asset && $asset->status === VideoStatus::Ready && $asset->hls_path && $asset->encryption_key, 404, 'Video belum siap.');

        $token = bin2hex(random_bytes(32));
        DB::transaction(function () use ($user, $lesson, $asset, $token): void {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            PlaybackSession::query()->where('user_id', $user->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $session = PlaybackSession::query()->create([
                'user_id' => $user->id, 'lesson_id' => $lesson->id, 'video_asset_id' => $asset->id,
                'token_hash' => hash('sha256', $token), 'hls_path' => $asset->hls_path,
                'encryption_key' => $asset->encryption_key, 'is_preview' => $user->isAdmin(),
                'expires_at' => now()->addMinutes(config('video.session_ttl_minutes')),
                'hard_expires_at' => now()->addHours(12),
            ]);
            $this->logger->log('playback_started', $user, 'playback_sessions', $session->id, null,
                ['lesson_id' => $lesson->id, 'is_preview' => $user->isAdmin()]);
        });

        return ['manifest_url' => route('stream.master', ['token' => $token]),
            'heartbeat_url' => route('learn.heartbeat', ['token' => $token]),
            'progress_url' => $user->isMember() ? route('learn.progress', $lesson) : null,
            'progress_token' => $user->isMember() ? $token : null,
            'ttl_seconds' => config('video.session_ttl_minutes') * 60,
            'heartbeat_seconds' => config('video.heartbeat_seconds'),
            'watermark' => $user->name.' · '.$user->email];
    }

    public function assertSession(User $user, string $token): PlaybackSession
    {
        abort_unless(preg_match('/^[a-f0-9]{64}$/', $token), 403, 'Sesi video tidak valid.');
        $session = PlaybackSession::query()->where('token_hash', hash('sha256', $token))
            ->where('user_id', $user->id)->first();
        abort_unless($session && ! $session->revoked_at && $session->expires_at->isFuture()
            && $session->hard_expires_at->isFuture(), 403, 'Sesi video berakhir atau digunakan di perangkat lain.');
        $lesson = $session->lesson;
        abort_unless($lesson && ($user->isAdmin() || $this->enrollments->activeFor($user, $lesson->course)),
            403, 'Akses course tidak aktif.');
        $this->sequence->assertAccessible($user, $lesson);
        return $session;
    }

    public function heartbeat(User $user, string $token): array
    {
        $session = $this->assertSession($user, $token);
        DB::transaction(function () use ($session): void {
            $locked = PlaybackSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->revoked_at || $locked->expires_at->isPast() || $locked->hard_expires_at->isPast(), 403, 'Sesi video berakhir.');
            $next = now()->addMinutes(config('video.session_ttl_minutes'));
            $locked->update(['expires_at' => $next->min($locked->hard_expires_at), 'last_heartbeat_at' => now()]);
        });
        return ['status' => 'active'];
    }

    public function master(string $token): string
    {
        return "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-STREAM-INF:BANDWIDTH=1800000\n".
            route('stream.playlist', ['token' => $token])."\n";
    }

    public function playlist(PlaybackSession $session, string $token): string
    {
        $manifest = Storage::disk(config('video.disk'))->get($session->hls_path.'/index.m3u8');
        $lines = preg_split('/\r?\n/', trim($manifest));
        foreach ($lines as &$line) {
            if (str_starts_with($line, '#EXT-X-KEY:')) {
                $line = preg_replace('/URI="[^"]+"/', 'URI="'.route('stream.key', ['token' => $token]).'"', $line);
            } elseif (preg_match('/^seg_\d{5}\.ts$/', $line)) {
                $line = route('stream.segment', ['token' => $token, 'name' => $line]);
            }
        }
        unset($line);
        return implode("\n", $lines)."\n";
    }

    public function key(PlaybackSession $session): string
    {
        $key = base64_decode($session->encryption_key, true);
        abort_unless($key !== false && strlen($key) === 16, 503, 'Kunci video tidak tersedia.');
        return $key;
    }

    public function segmentPath(PlaybackSession $session, string $name): string
    {
        abort_unless(preg_match('/^seg_\d{5}\.ts$/', $name), 404);
        $path = $session->hls_path.'/'.$name;
        abort_unless(Storage::disk(config('video.disk'))->exists($path), 404);
        return $path;
    }
}
