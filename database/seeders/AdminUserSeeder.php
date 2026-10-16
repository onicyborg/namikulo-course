<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $values = config('seeding.admin');
        $values['name'] = trim((string) $values['name']);
        $values['email'] = Str::lower(trim((string) $values['email']));
        $validator = Validator::make($values, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException('Isi SEED_ADMIN_NAME, SEED_ADMIN_EMAIL, dan SEED_ADMIN_PASSWORD yang valid sebelum menjalankan seeder.');
        }

        $existing = User::query()->where('email', $values['email'])->first();

        if ($existing) {
            $this->command?->info('Admin tidak dibuat karena email tersebut sudah terdaftar. Data dan password lama dipertahankan.');

            return;
        }

        User::query()->create([
            'name' => $values['name'],
            'email' => $values['email'],
            'password' => $values['password'],
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        $this->command?->info('Admin lokal berhasil dibuat.');
    }
}
