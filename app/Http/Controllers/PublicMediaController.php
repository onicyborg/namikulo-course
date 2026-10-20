<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicMediaController extends Controller
{
    public function show(string $path): StreamedResponse
    {
        // The public disk shares a private bucket with protected media under other prefixes.
        abort_unless(preg_match('~\A(?:avatars|courses/thumbnails)/[A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp)\z~i', $path), 404);
        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
        };

        return $disk->response($path, null, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }
}
