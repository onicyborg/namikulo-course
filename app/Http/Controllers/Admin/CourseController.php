<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use App\Services\ServerTable;
use App\Enums\CourseStatus;
use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Services\ActivityLogger;
use App\Services\DescriptionSanitizer;
use App\Services\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourseController extends Controller
{
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
        Gate::authorize('viewAny', Course::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'per_page' => ['nullable', 'integer', 'in:10,25,50,100']]);
        $query = Course::query()->with('category')->withCount(['sections', 'lessons']);
        if ($filters['q'] ?? null) {
            $term = '%'.mb_strtolower(trim($filters['q'])).'%';
            $query->where(fn ($q) => $q->whereRaw('LOWER(courses.title) LIKE ?', [$term])
                ->orWhereRaw('LOWER(courses.instructor_name) LIKE ?', [$term])
                ->orWhereHas('category', fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', [$term])));
        }
        if ($asData) return $table->response($request, $query,
            ['title', 'category', 'price', 'curriculum', 'status', 'actions'], 'admin.courses._cell', 'course',
            [0 => 'courses.title', 2 => fn ($query, $direction) => $query->orderByRaw('COALESCE(courses.discount_price, courses.price) '.$direction), 4 => 'courses.status'], 'courses.created_at', 'desc', Course::query()->count());
        return view('admin.courses.index', [
            'courses' => $query->latest('created_at')->orderBy('id')->paginate((int) ($filters['per_page'] ?? 10))->withQueryString(),
            'categories' => Category::query()->orderBy('name')->get(), 'filters' => $filters,
            'modalRecords' => $table->modalRecords(Course::query(), old('_record')),
        ]);
    }

    public function modal(Course $course): View
    {
        Gate::authorize('update', $course);
        return view('admin.courses._edit-modal', ['course' => $course, 'categories' => Category::query()->orderBy('name')->get()]);
    }

    public function store(Request $request, DescriptionSanitizer $sanitizer, ActivityLogger $logger, UniqueSlug $uniqueSlug): RedirectResponse
    {
        Gate::authorize('create', Course::class);
        $data = $this->validated($request);
        $slug = ($data['slug'] ?? null) ?: $uniqueSlug->fromTitle($data['title'], Course::class);
        $this->ensureSlug($slug);
        $path = $request->file('thumbnail')?->store('courses/thumbnails', 'public');
        try {
            DB::transaction(function () use ($data, $slug, $path, $request, $sanitizer, $logger): void {
                $course = Course::query()->create($this->attributes($data, $slug, $sanitizer) + [
                    'thumbnail_path' => $path,
                    'access_months' => config('course.access_months'),
                    'status' => CourseStatus::Draft,
                ]);
                $logger->log('created', $request->user(), 'courses', $course->id, null, $course->getAttributes());
            });
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('public')->delete($path);
            throw $exception;
        }
        return back()->with('success', 'Course berhasil ditambahkan sebagai draf.');
    }

    public function update(Request $request, Course $course, DescriptionSanitizer $sanitizer, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $course);
        $data = $this->validated($request, $course);
        $slug = ($data['slug'] ?? null) ?: $course->slug;
        $this->ensureSlug($slug);
        $slugChanged = $course->status === CourseStatus::Published && $slug !== $course->slug;
        $path = $request->file('thumbnail')?->store('courses/thumbnails', 'public');
        $oldPath = $course->thumbnail_path;
        try {
            DB::transaction(function () use ($course, $data, $slug, $path, $request, $sanitizer, $logger): void {
                $old = $course->getAttributes();
                $attributes = $this->attributes($data, $slug, $sanitizer);
                if ($path) $attributes['thumbnail_path'] = $path;
                $course->update($attributes);
                $logger->log('updated', $request->user(), 'courses', $course->id, $old, $course->getAttributes());
            });
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('public')->delete($path);
            throw $exception;
        }
        if ($path && $oldPath) Storage::disk('public')->delete($oldPath);
        return back()->with('success', $slugChanged
            ? 'Course diperbarui. Tautan lama tidak lagi berlaku karena slug berubah.'
            : 'Course berhasil diperbarui.');
    }

    public function publish(Request $request, Course $course, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $course);
        if (! $course->title || ! $course->thumbnail_path || $course->price === null || ! $course->lessons()->whereHas('videoAsset', fn ($query) => $query->where('status', VideoStatus::Ready->value))->exists()) {
            throw ValidationException::withMessages(['publish' => 'Course memerlukan judul, harga, thumbnail, dan sedikitnya satu video siap sebelum diterbitkan.']);
        }
        DB::transaction(function () use ($course, $request, $logger): void {
            $old = $course->getAttributes();
            $course->update(['status' => CourseStatus::Published, 'published_at' => $course->published_at ?? now()]);
            $logger->log('updated', $request->user(), 'courses', $course->id, $old, $course->getAttributes(), 'Course diterbitkan');
        });
        return back()->with('success', 'Course diterbitkan.');
    }

    public function unpublish(Request $request, Course $course, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $course);
        $this->changeStatus($request, $course, $logger, CourseStatus::Draft);
        return back()->with('success', 'Course kembali menjadi draf.');
    }

    public function archive(Request $request, Course $course, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $course);
        if ($course->status !== CourseStatus::Published) {
            throw ValidationException::withMessages(['status' => 'Hanya course yang sudah terbit dapat diarsipkan.']);
        }
        $this->changeStatus($request, $course, $logger, CourseStatus::Archived);
        return back()->with('success', 'Course diarsipkan.');
    }

    public function featured(Request $request, Course $course, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $course);
        $data = $request->validate(['is_featured' => ['required', 'boolean'], 'featured_order' => ['nullable', 'integer', 'min:0', 'max:4294967295']]);
        if ($data['is_featured'] && $course->status !== CourseStatus::Published) {
            throw ValidationException::withMessages(['is_featured' => 'Hanya course terbit yang dapat diunggulkan.']);
        }
        DB::transaction(function () use ($course, $data, $request, $logger): void {
            $old = $course->getAttributes();
            $course->update(['is_featured' => $data['is_featured'], 'featured_order' => $data['is_featured'] ? ($data['featured_order'] ?? 0) : null]);
            $logger->log('updated', $request->user(), 'courses', $course->id, $old, $course->getAttributes(), 'Pengaturan unggulan');
        });
        return back()->with('success', 'Pengaturan unggulan disimpan.');
    }

    public function destroy(Request $request, Course $course, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('delete', $course);
        DB::transaction(function () use ($course, $request, $logger): void {
            $old = $course->getAttributes();
            $course->delete();
            $logger->log('deleted', $request->user(), 'courses', $course->id, $old);
        });
        return back()->with('success', 'Course dihapus dari daftar. Berkas thumbnail disimpan untuk pemulihan.');
    }

    private function validated(Request $request, ?Course $course = null): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'uuid', Rule::exists('categories', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('courses', 'slug')->ignore($course?->id)],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:100000'],
            'instructor_name' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'integer', 'min:0'],
            'discount_price' => ['nullable', 'integer', 'min:0', 'lt:price'],
            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    private function attributes(array $data, string $slug, DescriptionSanitizer $sanitizer): array
    {
        return ['category_id' => $data['category_id'] ?? null, 'title' => $data['title'], 'slug' => $slug, 'summary' => $data['summary'] ?? null, 'description' => $sanitizer->clean($data['description'] ?? null), 'instructor_name' => $data['instructor_name'] ?? null, 'price' => $data['price'], 'discount_price' => $data['discount_price'] ?? null];
    }

    private function ensureSlug(string $slug): void
    {
        if ($slug === '') throw ValidationException::withMessages(['slug' => 'Slug tidak boleh kosong.']);
    }

    private function changeStatus(Request $request, Course $course, ActivityLogger $logger, CourseStatus $status): void
    {
        DB::transaction(function () use ($request, $course, $logger, $status): void {
            $old = $course->getAttributes();
            $course->update(['status' => $status, 'is_featured' => false, 'featured_order' => null]);
            $logger->log('updated', $request->user(), 'courses', $course->id, $old, $course->getAttributes(), 'Status course: '.$status->label());
        });
    }
}
