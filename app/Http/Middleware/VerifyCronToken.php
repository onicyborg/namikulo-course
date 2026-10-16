<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCronToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('operations.cron_token');
        if (strlen($expected) < 32) {
            return response()->json(['message' => 'Pemicu operasi belum dikonfigurasi.'], 503);
        }
        $header = (string) $request->header('Authorization', '');
        $provided = preg_match('/^Bearer ([^\s]+)$/', $header, $matches) ? $matches[1] : '';
        if (! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Token operasi tidak valid.'], 401);
        }
        return $next($request);
    }
}
