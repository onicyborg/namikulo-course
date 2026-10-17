<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->routeIs('admin.lessons.video.prepare', 'learn.show', 'learn.discussion.*')) {
            $asset = parse_url(asset('css/app.min.css'));
            $assetOrigin = isset($asset['scheme'], $asset['host'])
                ? $asset['scheme'].'://'.$asset['host'].(isset($asset['port']) ? ':'.$asset['port'] : '') : '';
            $sources = "'self'".($assetOrigin ? ' '.$assetOrigin : '');
            $response->headers->set('Content-Security-Policy',
                "default-src 'self'; script-src ".$sources." 'unsafe-inline' 'wasm-unsafe-eval'; ".
                "style-src ".$sources." 'unsafe-inline'; img-src ".$sources." data:; font-src ".$sources." data:; ".
                "connect-src ".$sources."; worker-src 'self' blob:; media-src 'self' blob:; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
        }

        return $response;
    }
}
