<?php

namespace App\Http\Controllers\Stream;

use App\Http\Controllers\Controller;
use App\Services\VideoDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HlsStreamController extends Controller
{
    public function master(Request $request, string $token, VideoDelivery $delivery): Response
    {
        $this->session($request, $token, $delivery);
        return $this->content($delivery->master($token), 'application/vnd.apple.mpegurl');
    }

    public function playlist(Request $request, string $token, VideoDelivery $delivery): Response
    {
        $session = $this->session($request, $token, $delivery);
        return $this->content($delivery->playlist($session, $token), 'application/vnd.apple.mpegurl');
    }

    public function key(Request $request, string $token, VideoDelivery $delivery): Response
    {
        $session = $this->session($request, $token, $delivery);
        return $this->content($delivery->key($session), 'application/octet-stream');
    }

    public function segment(Request $request, string $token, string $name, VideoDelivery $delivery): StreamedResponse
    {
        $session = $this->session($request, $token, $delivery);
        $path = $delivery->segmentPath($session, $name);
        $disk = Storage::disk(config('video.disk'));
        return response()->stream(function () use ($disk, $path): void {
            $stream = $disk->readStream($path);
            if ($stream === false) return;
            try { fpassthru($stream); } finally { fclose($stream); }
        }, 200, $this->headers('video/mp2t') + ['Content-Length' => (string) $disk->size($path)]);
    }

    private function session(Request $request, string $token, VideoDelivery $delivery): \App\Models\PlaybackSession
    {
        $site = $request->header('Sec-Fetch-Site');
        abort_if($site !== null && $site !== 'same-origin', 403, 'Permintaan video ditolak.');
        return $delivery->assertSession($request->user(), $token);
    }

    private function content(string $body, string $type): Response
    {
        return response($body, 200, $this->headers($type));
    }

    private function headers(string $type): array
    {
        return ['Content-Type' => $type, 'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache', 'X-Content-Type-Options' => 'nosniff'];
    }
}
