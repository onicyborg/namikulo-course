<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\VideoUpload;
use App\Services\VideoImportService;
use App\Services\VideoContentDeletion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VideoUploadController extends Controller
{
    public function prepare(Lesson $lesson): View
    {
        Gate::authorize('update', $lesson);
        $lesson->load(['course', 'section', 'videoAsset']);

        return view('admin.lessons.video-prepare', [
            'lesson' => $lesson,
            'limits' => [
                'source_bytes' => config('video.source_max_size_mb') * 1024 * 1024,
                'duration_seconds' => config('video.source_max_duration_seconds'),
                'width' => config('video.max_width'), 'height' => config('video.max_height'),
                'chunk_bytes' => config('video.chunk_max_bytes'),
            ],
        ]);
    }

    public function store(Request $request, Lesson $lesson, VideoImportService $imports): JsonResponse
    {
        Gate::authorize('update', $lesson);
        $data = $request->validate([
            'key' => ['required', 'string', 'max:100'],
            'iv_hex' => ['required', 'string', 'size:32'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:'.config('video.source_max_duration_seconds')],
        ]);
        $upload = $imports->create($request->user(), $lesson, $data['key'], $data['iv_hex'], $data['duration_seconds']);
        return response()->json(['upload_id' => $upload->id, 'status' => $upload->status->value], 201);
    }

    public function manifest(Request $request, VideoUpload $upload, VideoImportService $imports): JsonResponse
    {
        $this->authorizeUpload($request, $upload);
        $data = $request->validate([
            'manifest' => ['required', 'string', 'max:'.config('video.manifest_max_bytes')],
            'segments' => ['required', 'array', 'min:1', 'max:'.config('video.max_segments')],
            'segments.*.name' => ['required', 'string', 'max:20'],
            'segments.*.size' => ['required', 'integer', 'min:16'],
            'segments.*.sha256' => ['required', 'string', 'size:64'],
        ]);
        return response()->json($imports->saveManifest($upload, $request->user(), $data['manifest'], $data['segments']));
    }

    public function chunk(Request $request, VideoUpload $upload, string $name, int $index, VideoImportService $imports): JsonResponse
    {
        $this->authorizeUpload($request, $upload);
        $sha256 = (string) $request->header('X-Chunk-SHA256');
        return response()->json($imports->saveChunk($upload, $request->user(), $name, $index, $request->getContent(), $sha256));
    }

    public function finalize(Request $request, VideoUpload $upload, VideoImportService $imports): JsonResponse
    {
        $this->authorizeUpload($request, $upload);
        return response()->json($imports->finalize($upload, $request->user()));
    }

    public function status(Request $request, VideoUpload $upload, VideoImportService $imports): JsonResponse
    {
        $this->authorizeUpload($request, $upload);
        return response()->json($imports->status($upload));
    }

    public function cancel(Request $request, VideoUpload $upload, VideoImportService $imports): JsonResponse
    {
        $this->authorizeUpload($request, $upload);
        return response()->json($imports->cancel($upload, $request->user()));
    }

    public function destroy(Request $request, Lesson $lesson, VideoContentDeletion $deletion): RedirectResponse
    {
        Gate::authorize('update', $lesson);
        $ids = DB::transaction(function () use ($lesson, $request, $deletion): array {
            $locked = Lesson::query()->whereKey($lesson->id)->lockForUpdate()->firstOrFail();
            return $deletion->removeVideo($locked, $request->user());
        });
        $removed = $deletion->removeFiles($ids);
        return back()->with('success', $removed
            ? 'Video privat berhasil dihapus dari materi.'
            : 'Video dilepas dari materi. Berkas privat yang tersisa akan dibersihkan oleh pemicu berkala.');
    }

    private function authorizeUpload(Request $request, VideoUpload $upload): void
    {
        abort_unless($upload->user_id === $request->user()->id, 404);
        Gate::authorize('update', $upload->lesson);
    }
}
