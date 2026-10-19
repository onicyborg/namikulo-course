<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use App\Services\ServerTable;
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
    public function index(Request $request, Course $course, ServerTable $table): View
    {
        return $this->listing($request, $course, $table, false);
    }

    public function data(Request $request, Course $course, ServerTable $table): JsonResponse
    {
        return $this->listing($request, $course, $table, true);
    }

    private function listing(Request $request, Course $course, ServerTable $table, bool $asData): View|JsonResponse
    {
        Gate::authorize('view', $course);
        Gate::authorize('viewAny', Enrollment::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::enum(EnrollmentStatus::class)], 'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])]]);
        $query = $course->enrollments()->with(['user', 'grantedBy', 'revokedBy']);
        if ($filters['q'] ?? null) {
            $term = '%'.mb_strtolower(trim($filters['q'])).'%';
            $query->whereHas('user', fn ($q) => $q->where(fn ($match) => $match->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(email) LIKE ?', [$term])));
        }
        if (($filters['status'] ?? null) === 'active') $query->where('status', 'active')->where('expires_at', '>', now());
        elseif (($filters['status'] ?? null) === 'expired') $query->where(fn ($q) => $q->where('status', 'expired')
            ->orWhere(fn ($q) => $q->where('status', 'active')->where('expires_at', '<=', now())));
        elseif ($filters['status'] ?? null) $query->where('status', $filters['status']);
        if ($asData) return $table->response($request, $query,
            ['member', 'source', 'starts', 'expires', 'status', 'actions'], 'admin.courses._member-cell', 'enrollment',
            [1 => 'source', 2 => 'starts_at', 3 => 'expires_at', 4 => 'status'], 'enrollments.created_at', 'desc', $course->enrollments()->count());
        return view('admin.courses.members', [
            'course' => $course, 'enrollments' => $query->orderByDesc('created_at')->orderBy('id')->paginate((int) ($filters['per_page'] ?? 10))->withQueryString(),
            'filters' => $filters,
            'modalRecords' => $table->modalRecords($course->enrollments()->with('user'), substr((string) old('_modal'), -36)),
            'availableMembers' => User::query()->where('role', UserRole::Member->value)->where('is_active', true)
                ->orderBy('name')->limit(50)->get(['id', 'name', 'email', 'timezone']),
            'selectedUser' => Str::isUuid((string) old('user_id')) ? User::query()->find(old('user_id')) : null,
        ]);
    }

    public function modal(Enrollment $enrollment): View
    {
        Gate::authorize('update', $enrollment);
        abort_if($enrollment->status === EnrollmentStatus::Revoked, 409, 'Akses sudah dicabut. Muat ulang tabel.');
        $enrollment->load('user');
        return view('admin.courses._member-modals', compact('enrollment'));
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
