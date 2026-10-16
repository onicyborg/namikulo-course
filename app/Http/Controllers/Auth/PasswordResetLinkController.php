<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $request->validate(['email' => ['required', 'email']]);
        if (config('mail.default') === 'log') {
            return back()->with('error', 'Reset password belum tersedia. Hubungi admin untuk mengatur pengiriman email.');
        }
        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable) {
            return back()->with('error', 'Tautan reset belum dapat dikirim. Coba lagi nanti.');
        }

        return back()->with('success', 'Jika email terdaftar, tautan reset password akan dikirim.');
    }
}
