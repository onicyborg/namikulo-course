<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Services\ActivityLogger;
use App\Services\ContentOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function index(Course $course): View
    {
        Gate::authorize('view', $course);
        return view('admin.courses.curriculum', ['course' => $course->load(['sections.lessons.videoAsset'])]);
    }

    public function store(Request $request, Course $course, ActivityLogger $logger): RedirectResponse
    {
        Gate::authorize('update', $course);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000']]);
        DB::transaction(function () use ($course, $data, $request, $logger): void {
            $order = ((int) $course->sections()->max('sort_order')) + 1;
            $section = $course->sections()->create($data + ['sort_order' => $order]);
            $logger->log('created', $request->user(), 'sections', $section->id, null, $section->getAttributes());
        });
        return back()->with('success', 'Bab berhasil ditambahkan.');
    }

    public function update(Request $request, Course $course, Section $section, ActivityLogger $logger): RedirectResponse
    {
        $this->authorizeScope($course, $section);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000']]);
        DB::transaction(function () use ($section, $data, $request, $logger): void {
            $old = $section->getAttributes();
            $section->update($data);
            $logger->log('updated', $request->user(), 'sections', $section->id, $old, $section->getAttributes());
        });
        return back()->with('success', 'Bab berhasil diperbarui.');
    }

    public function destroy(Request $request, Course $course, Section $section, ActivityLogger $logger): RedirectResponse
    {
        $this->authorizeScope($course, $section);
        DB::transaction(function () use ($section, $request, $logger): void {
            foreach ($section->lessons()->get() as $lesson) {
                $oldLesson = $lesson->getAttributes();
                $lesson->delete();
                $logger->log('deleted', $request->user(), 'lessons', $lesson->id, $oldLesson);
            }
            $old = $section->getAttributes();
            $section->delete();
            $logger->log('deleted', $request->user(), 'sections', $section->id, $old);
        });
        return back()->with('success', 'Bab dan metadata materi di dalamnya dihapus.');
    }

    public function reorder(Request $request, Course $course, ContentOrder $order): RedirectResponse
    {
        Gate::authorize('update', $course);
        $data = $request->validate(['order' => ['present', 'array'], 'order.*' => ['required', 'uuid']]);
        $order->save($course->sections()->getQuery(), $data['order'], 'sections', $request->user());
        return back()->with('success', 'Urutan bab disimpan.');
    }

    private function authorizeScope(Course $course, Section $section): void
    {
        abort_unless($section->course_id === $course->id, 404);
        Gate::authorize('update', $section);
    }
}
