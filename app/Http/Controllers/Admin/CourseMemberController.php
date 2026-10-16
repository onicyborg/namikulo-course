<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\LocalDateTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CourseMemberController extends Controller
{
    public function index(Request $request, Course $course): View
    {
        Gate::authorize('view', $course);
        Gate::authorize('viewAny', Enrollment::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::enum(EnrollmentStatus::class)]]);
        $query = $course->enrollments()->with(['user', 'grantedBy', 'revokedBy'])->orderByDesc('created_at');
        if ($filters['q'] ?? null) {
            $term = '%'.mb_strtolower($filters['q']).'%';
            $query->whereHas('user', fn ($q) => $q->where(fn ($match) => $match->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(email) LIKE ?', [$term])));
        }
        if ($filters['status'] ?? null) $query->where('status', $filters['status']);
        $serverSide = $course->enrollments()->count() > 2000;
        return view('admin.courses.members', [
            'course' => $course, 'enrollments' => $serverSide ? $query->paginate(25)->withQueryString() : $query->get(),
            'serverSide' => $serverSide, 'filters' => $filters,
            'availableMembers' => User::query()->where('role', UserRole::Member->value)->where('is_active', true)
                ->orderBy('name')->limit(50)->get(['id', 'name', 'email', 'timezone']),
            'selectedUser' => Str::isUuid((string) old('user_id')) ? User::query()->find(old('user_id')) : null,
        ]);
    }

    public function store(Request $request, Course $course, EnrollmentService $service, LocalDateTime $localDateTime): RedirectResponse
    {
        Gate::authorize('update', $course);
        Gate::authorize('create', Enrollment::class);
        $data = $request->validate([
            'user_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('role', UserRole::Member->value)->where('is_active', true)],
            'timezone' => ['required', 'timezone'],
            'starts_at' => ['required', 'string'], 'starts_at_utc' => ['required', 'string'],
            'expires_at' => ['nullable', 'string'], 'expires_at_utc' => ['nullable', 'required_with:expires_at', 'string'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $startsAt = $localDateTime->toUtc($data['starts_at'], $data['starts_at_utc'], $data['timezone'], 'starts_at');
        $endsAt = isset($data['expires_at']) ? $localDateTime->toUtc($data['expires_at'], $data['expires_at_utc'], $data['timezone'], 'expires_at') : null;
        $service->grantManual(User::query()->findOrFail($data['user_id']), $course, $request->user(), $startsAt, $endsAt, $data['note'] ?? null);
        return back()->with('success', 'Akses manual berhasil diberikan.');
    }

    public function update(Request $request, Enrollment $enrollment, EnrollmentService $service, LocalDateTime $localDateTime): RedirectResponse
    {
        Gate::authorize('update', $enrollment);
        $data = $request->validate([
            'timezone' => ['required', 'timezone'], 'expires_at' => ['required', 'string'],
            'expires_at_utc' => ['required', 'string'], 'note' => ['required', 'string', 'max:255'],
        ]);
        $expiresAt = $localDateTime->toUtc($data['expires_at'], $data['expires_at_utc'], $data['timezone'], 'expires_at');
        $service->changeExpiry($enrollment, $request->user(), $expiresAt, $data['note']);
        return back()->with('success', 'Tanggal akhir akses diperbarui.');
    }

    public function revoke(Request $request, Enrollment $enrollment, EnrollmentService $service): RedirectResponse
    {
        Gate::authorize('update', $enrollment);
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);
        $service->revoke($enrollment, $request->user(), $data['note']);
        return back()->with('success', 'Akses dicabut.');
    }
}
