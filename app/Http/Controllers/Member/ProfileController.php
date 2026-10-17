<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('member.profile.edit', ['member' => $request->user()]);
    }

    public function update(Request $request, ActivityLogger $logger): RedirectResponse
    {
        if ($request->input('section') === 'password') {
            $data = $request->validate([
                'section' => ['required', 'in:password'],
                'current_password' => ['required', 'current_password'],
                'password' => ['required', 'confirmed', Password::min(8)],
            ]);
            DB::transaction(function () use ($request, $data, $logger): void {
                $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $user->update(['password' => $data['password']]);
                $logger->log('updated', $user, 'users', $user->id, null, null, 'Password profil diperbarui.');
            });
            $request->session()->regenerate();
            return back()->with('success', 'Password berhasil diubah.');
        }

        $data = $request->validate([
            'section' => ['required', 'in:profile'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        $avatarPath = $request->file('avatar')?->store('avatars', 'public');
        if ($request->hasFile('avatar') && ! $avatarPath) {
            throw ValidationException::withMessages(['avatar' => 'Foto profil gagal disimpan. Coba lagi.']);
        }
        $oldAvatar = null;
        try {
            DB::transaction(function () use ($request, $data, $avatarPath, $logger, &$oldAvatar): void {
                $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $oldAvatar = $user->avatar_path;
                $old = ['name' => $user->name, 'phone' => $user->phone];
                $values = ['name' => $data['name'], 'phone' => $data['phone'] ?? null];
                if ($avatarPath) $values['avatar_path'] = $avatarPath;
                $user->update($values);
                $logger->log('updated', $user, 'users', $user->id, $old,
                    ['name' => $user->name, 'phone' => $user->phone, 'avatar_changed' => (bool) $avatarPath]);
            });
        } catch (\Throwable $error) {
            if ($avatarPath) Storage::disk('public')->delete($avatarPath);
            throw $error;
        }
        if ($avatarPath && $oldAvatar && str_starts_with($oldAvatar, 'avatars/')) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
