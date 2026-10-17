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
        $size = $disk->size($path);
        [$start, $end] = $this->range($request->header('Range'), $size);
        $length = $end - $start + 1;
        $headers = $this->headers('video/mp2t') + [
            'Accept-Ranges' => 'bytes', 'Content-Length' => (string) $length,
        ];
        $partial = $request->hasHeader('Range');
        if ($partial) $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";

        return response()->stream(function () use ($disk, $path, $start, $length): void {
            $stream = $disk->readStream($path);
            if ($stream === false) return;
            try {
                $skip = $start;
                if ($skip && stream_get_meta_data($stream)['seekable']) {
                    if (fseek($stream, $skip) === 0) $skip = 0;
                }
                while ($skip > 0 && ! feof($stream)) {
                    $chunk = fread($stream, min(65536, $skip));
                    if ($chunk === false || $chunk === '') break;
                    $skip -= strlen($chunk);
                }
                $remaining = $length;
                while ($skip === 0 && $remaining > 0 && ! feof($stream)) {
                    $chunk = fread($stream, min(65536, $remaining));
                    if ($chunk === false || $chunk === '') break;
                    echo $chunk;
                    $remaining -= strlen($chunk);
                }
            } finally { fclose($stream); }
        }, $partial ? 206 : 200, $headers);
    }

    private function range(?string $header, int $size): array
    {
        abort_if($size < 1, 404, 'Segmen video kosong.');
        if ($header === null) return [0, $size - 1];
        if (! preg_match('/^bytes=(\d*)-(\d*)$/', trim($header), $matches)
            || ($matches[1] === '' && $matches[2] === '')) {
            abort(416, 'Rentang video tidak valid.', ['Content-Range' => "bytes */{$size}"]);
        }
        if ($matches[1] === '') {
            $suffix = (int) $matches[2];
            abort_if($suffix < 1, 416, 'Rentang video tidak valid.', ['Content-Range' => "bytes */{$size}"]);
            return [max(0, $size - $suffix), $size - 1];
        }
        $start = (int) $matches[1];
        $end = $matches[2] === '' ? $size - 1 : min((int) $matches[2], $size - 1);
        abort_if($start >= $size || $end < $start, 416, 'Rentang video tidak valid.', ['Content-Range' => "bytes */{$size}"]);
        return [$start, $end];
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
