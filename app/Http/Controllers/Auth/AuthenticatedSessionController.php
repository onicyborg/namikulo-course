<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Str;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $logger->log('login_failed', description: 'Percobaan masuk gagal.');

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $logger->log('login_failed', $user, 'users', $user->getKey(), description: 'Percobaan masuk pada akun nonaktif.');

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $logger->log('login', $user, 'users', $user->getKey(), description: 'Pengguna berhasil masuk.');

        return redirect()->to($this->destination($request, $user));
    }

    public function destroy(Request $request, ActivityLogger $logger): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $logger->log('logout', $user, 'users', $user->getKey(), description: 'Pengguna keluar dari aplikasi.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar dari aplikasi.');
    }

    private function destination(Request $request, User $user): string
    {
        $fallback = $user->role === UserRole::Admin
            ? route('admin.dashboard')
            : route('member.dashboard');
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || ! $this->isAllowedIntendedUrl($intended, $user)) {
            return $fallback;
        }

        return $intended;
    }

    private function isAllowedIntendedUrl(string $url, User $user): bool
    {
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $urlHost = parse_url($url, PHP_URL_HOST);
        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($urlHost && $appHost && $urlHost !== $appHost) {
            return false;
        }

        return $user->role === UserRole::Admin
            ? $this->matchesPathPrefix($path, '/admin')
            : $this->matchesPathPrefix($path, '/my')
                || $this->matchesPathPrefix($path, '/checkout')
                || $this->matchesPathPrefix($path, '/orders');
    }

    private function matchesPathPrefix(string $path, string $prefix): bool
    {
        return $path === $prefix || str_starts_with($path, $prefix.'/');
    }
}
