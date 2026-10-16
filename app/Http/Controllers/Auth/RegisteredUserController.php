<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $timezone = $request->input('timezone');
        if (! is_string($timezone) || ! Validator::make(['timezone' => $timezone], ['timezone' => ['required', 'timezone']])->passes()) {
            $timezone = null;
        }

        $user = DB::transaction(function () use ($validated, $timezone, $logger, $request): User {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => UserRole::Member,
                'is_active' => true,
                'timezone' => $timezone,
            ]);

            $logger->log(
                'created',
                $user,
                'users',
                $user->getKey(),
                newValues: ['name' => $user->name, 'email' => $user->email, 'role' => $user->role->value],
                description: 'Member mendaftar melalui halaman publik.',
                request: $request,
            );

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('member.dashboard')->with('success', 'Akun berhasil dibuat.');
    }
}
