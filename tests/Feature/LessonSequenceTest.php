<?php

namespace Tests\Feature;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Enums\VideoStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Section;
use App\Models\User;
use App\Models\VideoAsset;
use App\Services\LessonSequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_open_later_lesson_or_discussion_until_previous_ready_lesson_is_complete(): void
    {
        [$member, $course, $first, $second] = $this->courseWithTwoReadyLessons();

        $this->actingAs($member)->get(route('learn.show', $first))->assertOk();
        $this->actingAs($member)->get(route('member.courses.show', $course->slug))
            ->assertOk()->assertSee('Terkunci · selesaikan materi sebelumnya');
        $this->actingAs($member)->get(route('learn.show', $second))->assertForbidden();
        $this->actingAs($member)->get(route('learn.discussion.index', $second))->assertForbidden();
        $this->actingAs($member)->post(route('learn.discussion.store', $second),
            ['body' => 'Pertanyaan yang belum boleh dikirim'])->assertForbidden();
        $this->actingAs($member)->post(route('learn.playback', $second))->assertForbidden();

        LessonProgress::create(['user_id' => $member->id, 'course_id' => $course->id,
            'lesson_id' => $first->id, 'completed_at' => now()]);

        $this->app->make(LessonSequence::class)->assertAccessible($member, $second);
        $this->actingAs($member)->get(route('learn.show', $second))->assertOk();
    }

    public function test_completed_lesson_remains_accessible_when_earlier_lesson_is_incomplete_and_admin_can_preview(): void
    {
        [$member, $course, $first, $second] = $this->courseWithTwoReadyLessons();
        LessonProgress::create(['user_id' => $member->id, 'course_id' => $course->id,
            'lesson_id' => $second->id, 'completed_at' => now()]);

        $sequence = $this->app->make(LessonSequence::class);
        $sequence->assertAccessible($member, $second);
        $this->assertArrayHasKey($first->id, $sequence->unlockedIds($sequence->readyLessons($course),
            LessonProgress::where('user_id', $member->id)->get()->keyBy('lesson_id')));

        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $sequence->assertAccessible($admin, $first);
    }

    public function test_skips_unready_video_but_requires_every_earlier_ready_lesson(): void
    {
        [$member, $course, $first, $second] = $this->courseWithTwoReadyLessons();
        $section = $first->section;
        $unready = Lesson::create(['course_id' => $course->id, 'section_id' => $section->id,
            'title' => 'Belum tersedia', 'sort_order' => 2]);
        $third = Lesson::create(['course_id' => $course->id, 'section_id' => $section->id,
            'title' => 'Materi ketiga', 'sort_order' => 3]);
        VideoAsset::create(['lesson_id' => $third->id, 'status' => VideoStatus::Ready,
            'hls_path' => 'test/'.$third->id, 'encryption_key' => random_bytes(16), 'duration_seconds' => 30]);
        LessonProgress::create(['user_id' => $member->id, 'course_id' => $course->id,
            'lesson_id' => $first->id, 'completed_at' => now()]);
        $sequence = $this->app->make(LessonSequence::class);

        $this->assertCount(3, $sequence->readyLessons($course));
        $this->actingAs($member)->get(route('learn.show', $third))->assertForbidden();
        $this->actingAs($member)->get(route('learn.show', $unready))->assertNotFound();

        LessonProgress::create(['user_id' => $member->id, 'course_id' => $course->id,
            'lesson_id' => $second->id, 'completed_at' => now()]);
        $sequence->assertAccessible($member, $third);
    }

    private function courseWithTwoReadyLessons(): array
    {
        $member = User::factory()->create();
        $course = Course::create(['title' => 'Urutan materi', 'slug' => 'urutan-materi', 'price' => 0]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Bab pertama', 'sort_order' => 0]);
        $first = Lesson::create(['course_id' => $course->id, 'section_id' => $section->id,
            'title' => 'Materi pertama', 'sort_order' => 0]);
        $second = Lesson::create(['course_id' => $course->id, 'section_id' => $section->id,
            'title' => 'Materi kedua', 'sort_order' => 1]);
        foreach ([$first, $second] as $lesson) {
            VideoAsset::create(['lesson_id' => $lesson->id, 'status' => VideoStatus::Ready,
                'hls_path' => 'test/'.$lesson->id, 'encryption_key' => random_bytes(16), 'duration_seconds' => 30]);
        }
        Enrollment::create(['user_id' => $member->id, 'course_id' => $course->id,
            'source' => EnrollmentSource::Admin, 'status' => EnrollmentStatus::Active,
            'starts_at' => now()->subDay(), 'expires_at' => now()->addMonth()]);

        return [$member, $course, $first, $second];
    }
}
