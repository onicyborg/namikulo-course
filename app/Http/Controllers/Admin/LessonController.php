<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Section;
use App\Services\ActivityLogger;
use App\Services\ContentOrder;
use App\Services\VideoContentDeletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LessonController extends Controller
{
    public function store(Request $request, Section $section, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $section);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:100000']]);
        $data['description'] = app(\App\Services\DescriptionSanitizer::class)->clean($data['description'] ?? null);
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
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:100000']]);
        $data['description'] = app(\App\Services\DescriptionSanitizer::class)->clean($data['description'] ?? null);
        DB::transaction(function () use ($lesson, $data, $request, $logger): void {
            $old = $lesson->getAttributes();
            $lesson->update($data);
            $logger->log('updated', $request->user(), 'lessons', $lesson->id, $old, $lesson->getAttributes());
        });
        return back()->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Request $request, Section $section, Lesson $lesson, VideoContentDeletion $deletion): RedirectResponse
    {
        $this->authorizeScope($section, $lesson);
        $ids = DB::transaction(fn () => $deletion->archiveLesson($lesson, $request->user()));
        $removed = $deletion->removeFiles($ids);
        return back()->with('success', $removed ? 'Materi dan berkas video privatnya dihapus.' : 'Materi dihapus. Berkas privat yang tersisa akan dibersihkan oleh pemicu berkala.');
    }

    public function reorder(Request $request, Section $section, ContentOrder $order): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $section);
        $data = $request->validate(['order' => ['present', 'array'], 'order.*' => ['required', 'uuid']]);
        $order->save($section->lessons()->getQuery(), $data['order'], 'lessons', $request->user());
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Urutan materi disimpan.']);
        }
        return back()->with('success', 'Urutan materi disimpan.');
    }

    private function authorizeScope(Section $section, Lesson $lesson): void
    {
        abort_unless($lesson->section_id === $section->id && $lesson->course_id === $section->course_id, 404);
        Gate::authorize('update', $lesson);
    }
}
