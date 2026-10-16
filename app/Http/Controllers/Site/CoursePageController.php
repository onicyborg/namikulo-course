<?php

namespace App\Http\Controllers\Site;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\VideoStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\VideoAsset;
use App\Services\EnrollmentService;
use App\Services\DescriptionSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CoursePageController extends Controller
{
    public function home(Request $request): View
    {
        $published = Course::query()->where('status', CourseStatus::Published->value);
        $featured = (clone $published)->where('is_featured', true)
            ->with('category')->withCount(['sections', 'lessons'])
            ->withSum('lessons as lessons_duration_seconds', 'duration_seconds')
            ->orderBy('featured_order')->orderBy('title')->limit(8)->get();

        return view('site.home', [
            'featured' => $featured,
            'activeCourseIds' => $this->activeCourseIds($request, $featured->pluck('id')->all()),
            'stats' => [
                'members' => Enrollment::query()->where('status', EnrollmentStatus::Active->value)
                    ->where('expires_at', '>', now())->distinct()->count('user_id'),
                'courses' => (clone $published)->count(),
                'videos' => VideoAsset::query()->where('status', VideoStatus::Ready->value)
                    ->whereHas('lesson.course', fn ($query) => $query->where('status', CourseStatus::Published->value))->count(),
            ],
        ]);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'in:newest,price_asc,price_desc'],
        ]);

        $query = Course::query()->where('status', CourseStatus::Published->value)
            ->with('category')->withCount(['sections', 'lessons'])
            ->withSum('lessons as lessons_duration_seconds', 'duration_seconds');

        if ($filters['q'] ?? null) {
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower(trim($filters['q'])).'%']);
        }
        if ($filters['category'] ?? null) {
            $query->whereHas('category', fn ($category) => $category->where('slug', $filters['category']));
        }

        match ($filters['sort'] ?? 'newest') {
            // COALESCE works on PostgreSQL and MySQL; effective price includes a course discount.
            'price_asc' => $query->orderByRaw('COALESCE(discount_price, price) ASC')->orderBy('title'),
            'price_desc' => $query->orderByRaw('COALESCE(discount_price, price) DESC')->orderBy('title'),
            default => $query->orderByDesc('published_at')->orderBy('title'),
        };

        $courses = $query->paginate(12)->withQueryString();

        return view('site.courses.index', [
            'courses' => $courses,
            'activeCourseIds' => $this->activeCourseIds($request, $courses->getCollection()->pluck('id')->all()),
            'categories' => Category::query()->whereHas('courses', fn ($courses) => $courses->where('status', CourseStatus::Published->value))
                ->orderBy('sort_order')->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Course $course, EnrollmentService $enrollments, DescriptionSanitizer $sanitizer): View
    {
        abort_unless($course->status === CourseStatus::Published, 404);

        return $this->detail($request, $course, $enrollments, $sanitizer, false);
    }

    public function preview(Request $request, Course $course, EnrollmentService $enrollments, DescriptionSanitizer $sanitizer): View
    {
        Gate::authorize('view', $course);

        return $this->detail($request, $course, $enrollments, $sanitizer, true);
    }

    private function detail(Request $request, Course $course, EnrollmentService $enrollments, DescriptionSanitizer $sanitizer, bool $preview): View
    {
        $course->load(['category', 'sections' => fn ($sections) => $sections->with([
            'lessons' => fn ($lessons) => $lessons->select('id', 'section_id', 'title', 'duration_seconds', 'sort_order'),
        ])])->loadCount(['sections', 'lessons'])->loadSum('lessons as lessons_duration_seconds', 'duration_seconds');

        return view('site.courses.show', [
            'course' => $course,
            'preview' => $preview,
            'accessState' => $enrollments->stateFor($request->user(), $course),
            'safeDescription' => $sanitizer->clean($course->description),
        ]);
    }

    private function activeCourseIds(Request $request, array $courseIds): array
    {
        if (! $request->user() || $courseIds === []) return [];

        return Enrollment::query()->where('user_id', $request->user()->id)
            ->whereIn('course_id', $courseIds)->where('status', EnrollmentStatus::Active->value)
            ->where('expires_at', '>', now())->pluck('course_id')->all();
    }
}
