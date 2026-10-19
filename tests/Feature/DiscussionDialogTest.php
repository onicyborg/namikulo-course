<?php

namespace Tests\Feature;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Enums\DiscussionPostStatus;
use App\Enums\VideoStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonDiscussionPost;
use App\Models\Section;
use App\Models\User;
use App\Models\VideoAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscussionDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_fragment_and_direct_link_to_video_keep_comment_target_and_authorization(): void
    {
        [$member, $lesson, $root, $reply] = $this->thread();
        $url = route('learn.discussion.thread', [$lesson, $root]);

        $this->actingAs($member)->get($url.'?dialog=1&focus='.$reply->id)
            ->assertOk()->assertSee('id="dialog-discussion-post-'.$reply->id.'"', false)
            ->assertSee('dialog-reply-form')->assertDontSee('<!DOCTYPE html>', false);
        $this->actingAs($member)->get($url.'?focus='.$reply->id.'#discussion-post-'.$reply->id)
            ->assertRedirect(route('learn.show', $lesson).'?focus='.$reply->id.'#discussion-post-'.$reply->id);
        $this->actingAs($member)->get(route('learn.show', $lesson).'?focus='.$reply->id)
            ->assertOk()->assertSee('id="discussion-post-'.$reply->id.'"', false);

        $outsider = User::factory()->create();
        $this->actingAs($outsider)->get($url.'?dialog=1')->assertForbidden();
    }

    public function test_modal_reply_returns_json_and_validation_errors_without_redirect(): void
    {
        [$member, $lesson, $root] = $this->thread();
        $url = route('learn.discussion.store', $lesson);
        $payload = ['parent_id' => $root->id, 'reply_to_id' => $root->id,
            'body_format' => 'plain', 'body' => 'Balasan yang relevan untuk materi ini.'];

        $this->actingAs($member)->postJson($url, array_merge($payload, ['body' => 'pendek']))
            ->assertUnprocessable()->assertJsonValidationErrors('body');
        $response = $this->actingAs($member)->postJson($url, $payload)
            ->assertOk()->assertJsonStructure(['message', 'url', 'post_id']);
        $this->assertDatabaseHas('lesson_discussion_posts', ['id' => $response->json('post_id'),
            'parent_id' => $root->id, 'reply_to_id' => $root->id]);
    }

    public function test_notification_focus_loads_the_page_containing_a_later_reply(): void
    {
        [$member, $lesson, $root] = $this->thread();
        $last = null;
        for ($i = 1; $i <= 21; $i++) {
            $last = LessonDiscussionPost::create(['lesson_id' => $lesson->id, 'user_id' => $member->id,
                'parent_id' => $root->id, 'reply_to_id' => $root->id,
                'body' => 'Balasan berikutnya nomor '.$i.'.', 'body_format' => 'plain',
                'status' => DiscussionPostStatus::Visible]);
            $last->timestamps = false;
            $last->created_at = now()->addSeconds($i);
            $last->save();
        }

        $url = route('learn.discussion.thread', [$lesson, $root]).'?focus='.$last->id;
        $this->actingAs($member)->get($url)->assertRedirect(
            route('learn.show', $lesson).'?focus='.$last->id.'#discussion-post-'.$last->id);
        $this->actingAs($member)->get(route('learn.show', $lesson).'?focus='.$last->id)->assertOk()
            ->assertSee('id="discussion-post-'.$last->id.'"', false);
        $this->actingAs($member)->get($url.'&dialog=1')->assertOk()
            ->assertSee('id="dialog-discussion-post-'.$last->id.'"', false);
    }

    public function test_admin_video_page_reaches_a_reply_in_an_older_or_hidden_thread(): void
    {
        [$member, $lesson, $root, $reply] = $this->thread();
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        for ($i = 1; $i <= 11; $i++) {
            $newer = LessonDiscussionPost::create(['lesson_id' => $lesson->id, 'user_id' => $member->id,
                'body' => 'Pertanyaan berikutnya nomor '.$i.'.', 'body_format' => 'plain',
                'status' => DiscussionPostStatus::Visible]);
            $newer->timestamps = false;
            $newer->created_at = now()->addSeconds($i);
            $newer->save();
        }
        $root->update(['status' => DiscussionPostStatus::Hidden]);
        $reply->update(['status' => DiscussionPostStatus::Hidden]);

        $this->actingAs($admin)->get(route('admin.discussions.index', ['q' => 'Penjelasan singkat']))->assertOk()
            ->assertSee(route('learn.show', $lesson).'?focus='.$reply->id, false);
        $this->actingAs($admin)->get(route('learn.show', $lesson).'?focus='.$reply->id)
            ->assertOk()->assertSee('id="discussion-post-'.$root->id.'"', false)
            ->assertSee('id="discussion-post-'.$reply->id.'"', false);
    }

    private function thread(): array
    {
        $member = User::factory()->create();
        $course = Course::create(['title' => 'Diskusi modal', 'slug' => 'diskusi-modal', 'price' => 0]);
        $section = Section::create(['course_id' => $course->id, 'title' => 'Bab diskusi']);
        $lesson = Lesson::create(['course_id' => $course->id, 'section_id' => $section->id,
            'title' => 'Materi diskusi']);
        VideoAsset::create(['lesson_id' => $lesson->id, 'status' => VideoStatus::Ready,
            'hls_path' => 'test/'.$lesson->id, 'encryption_key' => random_bytes(16), 'duration_seconds' => 30]);
        Enrollment::create(['user_id' => $member->id, 'course_id' => $course->id,
            'source' => EnrollmentSource::Admin, 'status' => EnrollmentStatus::Active,
            'starts_at' => now()->subDay(), 'expires_at' => now()->addMonth()]);
        $root = LessonDiscussionPost::create(['lesson_id' => $lesson->id, 'user_id' => $member->id,
            'body' => 'Pertanyaan tentang materi diskusi?', 'body_format' => 'plain',
            'status' => DiscussionPostStatus::Visible]);
        $reply = LessonDiscussionPost::create(['lesson_id' => $lesson->id, 'user_id' => $member->id,
            'parent_id' => $root->id, 'reply_to_id' => $root->id,
            'body' => 'Penjelasan singkat untuk pertanyaan.', 'body_format' => 'plain',
            'status' => DiscussionPostStatus::Visible]);

        return [$member, $lesson, $root, $reply];
    }
}
