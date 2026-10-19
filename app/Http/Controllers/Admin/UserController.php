<?php

namespace App\Http\Controllers\Admin;

use App\Services\ServerTable;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);
        $data = $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $query = User::query()->where('role', UserRole::Member->value)->where('is_active', true);
        $search = trim($data['q'] ?? '');
        if ($search !== '') {
            $term = '%'.mb_strtolower($search).'%';
            $query->where(fn ($match) => $match->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(email) LIKE ?', [$term]));
        }
        $users = $query->orderBy('name')->limit(20)->get(['id', 'name', 'email', 'timezone']);
        return response()->json(['results' => $users->map(fn (User $user) => ['id' => $user->id, 'text' => $user->name.' · '.$user->email, 'timezone' => $user->timezone])]);
    }

    public function index(Request $request, ServerTable $table): View
    {
        return $this->listing($request, $table, false);
    }

    public function data(Request $request, ServerTable $table): JsonResponse
    {
        return $this->listing($request, $table, true);
    }

    private function listing(Request $request, ServerTable $table, bool $asData): View|JsonResponse
    {
        Gate::authorize('viewAny', User::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'role' => ['nullable', Rule::enum(UserRole::class)], 'status' => ['nullable', Rule::in(['active', 'inactive'])], 'per_page' => ['nullable', 'integer', 'in:10,25,50,100']]);
        $query = User::query()->withCount('enrollments');
        if ($filters['q'] ?? null) {
            $term = '%'.mb_strtolower(trim($filters['q'])).'%';
            $query->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(email) LIKE ?', [$term]));
        }
        if ($filters['role'] ?? null) $query->where('role', $filters['role']);
        if ($filters['status'] ?? null) $query->where('is_active', $filters['status'] === 'active');
        if ($asData) return $table->response($request, $query,
            ['name', 'email', 'role', 'status', 'access', 'actions'], 'admin.users._cell', 'user',
            [0 => 'name', 1 => 'email', 2 => 'role', 3 => 'is_active', 4 => 'enrollments_count'], 'name', 'asc', User::query()->count());
        return view('admin.users.index', ['users' => $query->orderBy('name')->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 10))->withQueryString(), 'filters' => $filters,
            'modalRecords' => $table->modalRecords(User::query(), old('_record'))]);
    }

    public function modal(User $user): View
    {
        Gate::authorize('update', $user);
        return view('admin.users._edit-modal', compact('user'));
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('create', User::class);
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'role' => ['required', Rule::enum(UserRole::class)], 'phone' => ['nullable', 'string', 'max:30'], 'password' => ['required', 'confirmed', PasswordRule::min(8)]]);
        DB::transaction(function () use ($data, $request, $logger): void {
            $user = User::query()->create($data + ['is_active' => true]);
            $logger->log('created', $request->user(), 'users', $user->id, null, $user->getAttributes());
        });
        return back()->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $user);
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)], 'role' => ['required', Rule::enum(UserRole::class)], 'phone' => ['nullable', 'string', 'max:30']]);
        DB::transaction(function () use ($user, $data, $request, $logger): void {
            $admins = User::query()->where('role', UserRole::Admin->value)->orderBy('id')->lockForUpdate()->get();
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($locked->role === UserRole::Admin && $data['role'] !== UserRole::Admin->value) {
                if ($request->user()->id === $locked->id || $admins->where('is_active', true)->count() <= 1) {
                    throw ValidationException::withMessages(['role' => 'Tidak dapat menurunkan peran diri sendiri atau admin aktif terakhir.']);
                }
            }
            $old = $locked->getAttributes();
            $locked->update($data);
            $logger->log('updated', $request->user(), 'users', $locked->id, $old, $locked->getAttributes());
        });
        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('delete', $user);
        return $this->setActive($request, $user, false, $logger);
    }

    public function toggleActive(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $user);
        return $this->setActive($request, $user, ! $user->is_active, $logger);
    }

    public function resetPassword(Request $request, User $user, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $user);
        if (config('mail.default') === 'log') {
            return back()->with('error', 'Atur SMTP sebelum mengirim tautan reset password.');
        }
        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable) {
            return back()->with('error', 'Tautan reset belum dapat dikirim. Periksa konfigurasi email.');
        }
        if ($status !== Password::RESET_LINK_SENT) {
            return back()->with('error', 'Tautan reset belum dapat dikirim. Periksa konfigurasi email.');
        }
        $logger->log('updated', $request->user(), 'users', $user->id, null, null, 'Tautan reset password diminta oleh admin');
        return back()->with('success', 'Tautan reset password dikirim ke email user.');
    }

    private function setActive(Request $request, User $user, bool $active, ActivityLogger $logger): RedirectResponse
    {
        DB::transaction(function () use ($request, $user, $active, $logger): void {
            $admins = User::query()->where('role', UserRole::Admin->value)->orderBy('id')->lockForUpdate()->get();
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $active && $locked->role === UserRole::Admin && ($request->user()->id === $locked->id || $admins->where('is_active', true)->count() <= 1)) {
                throw ValidationException::withMessages(['status' => 'Tidak dapat menonaktifkan diri sendiri atau admin aktif terakhir.']);
            }
            $old = $locked->getAttributes();
            $locked->update(['is_active' => $active]);
            $logger->log('updated', $request->user(), 'users', $locked->id, $old, $locked->getAttributes(), $active ? 'Akun diaktifkan' : 'Akun dinonaktifkan');
        });
        return back()->with('success', $active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
    }
}
