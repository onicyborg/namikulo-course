<?php

namespace App\Http\Controllers\Admin;

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
    public function index(): View
    {
        Gate::authorize('viewAny', Category::class);
        return view('admin.categories.index', ['categories' => Category::query()->withCount('courses')->orderBy('sort_order')->orderBy('name')->get()]);
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
