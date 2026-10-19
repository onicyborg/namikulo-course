<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use App\Services\ServerTable;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ActivityLogger;
use App\Services\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
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
        Gate::authorize('viewAny', Category::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'per_page' => ['nullable', 'integer', 'in:10,25,50,100']]);
        $query = Category::query()->withCount('courses');
        if ($filters['q'] ?? null) {
            $term = '%'.mb_strtolower(trim($filters['q'])).'%';
            $query->where(fn ($q) => $q->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(slug) LIKE ?', [$term]));
        }
        if ($asData) return $table->response($request, $query,
            ['name', 'slug', 'sort', 'courses', 'actions'], 'admin.categories._cell', 'category',
            [0 => 'name', 1 => 'slug', 2 => 'sort_order', 3 => 'courses_count'], 'sort_order', 'asc', Category::query()->count());
        return view('admin.categories.index', [
            'categories' => $query->orderBy('sort_order')->orderBy('id')->paginate((int) ($filters['per_page'] ?? 10))->withQueryString(),
            'filters' => $filters, 'modalRecords' => $table->modalRecords(Category::query(), old('_record')),
        ]);
    }

    public function modal(Category $category): View
    {
        Gate::authorize('update', $category);
        return view('admin.categories._edit-modal', compact('category'));
    }

    public function store(Request $request, ActivityLogger $logger, UniqueSlug $uniqueSlug): RedirectResponse
    {
        Gate::authorize('create', Category::class);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:categories,slug'], 'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295']]);
        $slug = ($data['slug'] ?? null) ?: $uniqueSlug->fromTitle($data['name'], Category::class);
        $this->ensureSlug($slug);
        DB::transaction(function () use ($data, $slug, $request, $logger): void {
            $category = Category::query()->create(['name' => $data['name'], 'slug' => $slug, 'sort_order' => $data['sort_order']]);
            $logger->log('created', $request->user(), 'categories', $category->id, null, $category->getAttributes());
        });
        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $category);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($category->id)], 'sort_order' => ['required', 'integer', 'min:0', 'max:4294967295']]);
        $slug = ($data['slug'] ?? null) ?: $category->slug;
        $this->ensureSlug($slug);
        DB::transaction(function () use ($category, $data, $slug, $request, $logger): void {
            $old = $category->getAttributes();
            $category->update(['name' => $data['name'], 'slug' => $slug, 'sort_order' => $data['sort_order']]);
            $logger->log('updated', $request->user(), 'categories', $category->id, $old, $category->getAttributes());
        });
        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Request $request, Category $category, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('delete', $category);
        DB::transaction(function () use ($category, $request, $logger): void {
            $old = $category->getAttributes();
            $category->delete();
            $logger->log('deleted', $request->user(), 'categories', $category->id, $old);
        });
        return back()->with('success', 'Kategori dihapus. Course yang terkait tetap tersimpan tanpa kategori.');
    }

    private function ensureSlug(string $slug): void
    {
        if ($slug === '') {
            throw \Illuminate\Validation\ValidationException::withMessages(['slug' => 'Slug tidak boleh kosong.']);
        }
    }
}
