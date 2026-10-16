<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Section;
use App\Services\ActivityLogger;
use App\Services\ContentOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LessonController extends Controller
{
    public function store(Request $request, Section $section, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $section);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000']]);
        DB::transaction(function () use ($section, $data, $request, $logger): void {
            $order = ((int) $section->lessons()->max('sort_order')) + 1;
            $lesson = $section->lessons()->create($data + ['course_id' => $section->course_id, 'sort_order' => $order, 'duration_seconds' => 0]);
            $logger->log('created', $request->user(), 'lessons', $lesson->id, null, $lesson->getAttributes());
        });
        return back()->with('success', 'Materi berhasil ditambahkan.');
    }

    public function update(Request $request, Section $section, Lesson $lesson, ActivityLogger $logger): RedirectResponse
    {
        $this->authorizeScope($section, $lesson);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000']]);
        DB::transaction(function () use ($lesson, $data, $request, $logger): void {
            $old = $lesson->getAttributes();
            $lesson->update($data);
            $logger->log('updated', $request->user(), 'lessons', $lesson->id, $old, $lesson->getAttributes());
        });
        return back()->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Request $request, Section $section, Lesson $lesson, ActivityLogger $logger): RedirectResponse
    {
        $this->authorizeScope($section, $lesson);
        DB::transaction(function () use ($lesson, $request, $logger): void {
            $old = $lesson->getAttributes();
            $lesson->delete();
            $logger->log('deleted', $request->user(), 'lessons', $lesson->id, $old);
        });
        return back()->with('success', 'Materi dihapus dari kurikulum.');
    }

    public function reorder(Request $request, Section $section, ContentOrder $order): RedirectResponse
    {
        Gate::authorize('update', $section);
        $data = $request->validate(['order' => ['present', 'array'], 'order.*' => ['required', 'uuid']]);
        $order->save($section->lessons()->getQuery(), $data['order'], 'lessons', $request->user());
        return back()->with('success', 'Urutan materi disimpan.');
    }

    private function authorizeScope(Section $section, Lesson $lesson): void
    {
        abort_unless($lesson->section_id === $section->id && $lesson->course_id === $section->course_id, 404);
        Gate::authorize('update', $lesson);
    }
}
