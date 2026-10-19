<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\EditorImage;
use App\Models\Lesson;
use App\Services\EnrollmentService;
use App\Services\LessonDiscussionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditorImageController extends Controller
{
    public function store(Request $request, LessonDiscussionService $discussions): JsonResponse
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:3072'],
            'scope' => ['required', 'in:public,course,lesson'],
            'course_id' => ['required_if:scope,course', 'nullable', 'uuid'],
            'lesson_id' => ['required_if:scope,lesson', 'nullable', 'uuid'],
        ]);
        $user = $request->user();
        $course = null;
        $lesson = null;

        if ($data['scope'] === 'public') {
            abort_unless($user->isAdmin(), 403);
        } elseif ($data['scope'] === 'course') {
            abort_unless($user->isAdmin(), 403);
            $course = Course::query()->findOrFail($data['course_id']);
        } else {
            $lesson = Lesson::query()->with('course')->findOrFail($data['lesson_id']);
            $discussions->authorizeLesson($user, $lesson);
            $course = $lesson->course;
        }

        $limit = $user->isAdmin() ? 200 : 30;
        if (EditorImage::query()->where('user_id', $user->id)->where('created_at', '>=', now()->subDay())->count() >= $limit) {
            throw ValidationException::withMessages(['image' => 'Batas unggahan gambar tercapai. Coba lagi besok.']);
        }

        $file = $data['image'];
        $details = @getimagesize($file->getRealPath());
        if (! $details || $details[0] < 1 || $details[1] < 1 || $details[0] > 3000 || $details[1] > 3000) {
            throw ValidationException::withMessages(['image' => 'Ukuran gambar maksimal 3000 × 3000 piksel.']);
        }
        $mime = $details['mime'] ?? '';
        $extension = match ($mime) {
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', default => null,
        };
        $encoder = match ($extension) {
            'jpg' => 'imagejpeg', 'png' => 'imagepng', 'webp' => 'imagewebp', default => null,
        };
        if (! $encoder || ! function_exists('imagecreatefromstring') || ! function_exists($encoder)) {
            throw ValidationException::withMessages(['image' => 'Format gambar tidak didukung server.']);
        }

        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $source) {
            throw ValidationException::withMessages(['image' => 'Gambar tidak dapat dibaca.']);
        }
        $width = $details[0];
        $height = $details[1];
        $scale = min(1, 1600 / max($width, $height));
        $target = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
        if (! $target) {
            imagedestroy($source);
            throw ValidationException::withMessages(['image' => 'Gambar tidak dapat diproses.']);
        }
        if ($extension !== 'jpg') {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        }
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);
        imagedestroy($source);
        ob_start();
        $encoded = match ($extension) {
            'jpg' => imagejpeg($target, null, 82),
            'png' => imagepng($target, null, 6),
            'webp' => imagewebp($target, null, 82),
        };
        $bytes = ob_get_clean();
        imagedestroy($target);
        if (! $encoded || ! is_string($bytes) || strlen($bytes) > 3 * 1024 * 1024) {
            throw ValidationException::withMessages(['image' => 'Gambar tidak dapat diproses atau hasilnya melebihi 3 MB.']);
        }

        $image = new EditorImage([
            'user_id' => $user->id, 'course_id' => $course?->id, 'lesson_id' => $lesson?->id,
            'scope' => $data['scope'], 'mime_type' => $mime, 'size_bytes' => strlen($bytes),
        ]);
        $image->id = (string) Str::uuid7();
        $image->path = 'editor-images/'.$image->id.'.'.$extension;
        if (! Storage::disk('local')->put($image->path, $bytes)) {
            throw ValidationException::withMessages(['image' => 'Gagal menyimpan gambar. Coba lagi.']);
        }
        try {
            $image->save();
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($image->path);
            throw $exception;
        }

        return response()->json([
            'url' => route('editor.images.show', $image, false),
            'message' => 'Gambar berhasil diunggah.',
        ], 201);
    }

    public function show(Request $request, EditorImage $image, EnrollmentService $enrollments,
        LessonDiscussionService $discussions): StreamedResponse
    {
        if ($image->scope !== 'public') {
            $viewer = $request->user();
            abort_unless($viewer && $viewer->is_active, 403);
            if ($image->scope === 'lesson') {
                $lesson = $image->lesson;
                abort_unless($lesson, 404);
                $discussions->authorizeLesson($viewer, $lesson);
            } else {
                $course = $image->course;
                abort_unless($course && ! $course->trashed(), 404);
                abort_unless($viewer->isAdmin() || $enrollments->activeFor($viewer, $course), 403);
            }
        }
        abort_unless(Storage::disk('local')->exists($image->path), 404);
        return Storage::disk('local')->response($image->path, null, [
            'Content-Type' => $image->mime_type,
            'Content-Disposition' => 'inline',
            'Cache-Control' => $image->scope === 'public' ? 'public, max-age=86400' : 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
