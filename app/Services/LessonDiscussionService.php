<?php

namespace App\Services;

use App\Enums\DiscussionPostStatus;
use App\Enums\UserRole;
use App\Models\Lesson;
use App\Models\LessonDiscussionPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LessonDiscussionService
{
    public function __construct(private readonly EnrollmentService $enrollments,
        private readonly NotificationService $notifications, private readonly ActivityLogger $logger) {}

    public function authorizeLesson(User $viewer, Lesson $lesson): void
    {
        $lesson->loadMissing('course');
        abort_if($lesson->trashed() || ! $lesson->course || $lesson->course->trashed(), 404);
        abort_unless($viewer->isAdmin() || $this->enrollments->activeFor($viewer, $lesson->course), 403, 'Akses course tidak aktif.');
    }

    public function visibleRoot(Lesson $lesson, LessonDiscussionPost $post, User $viewer): LessonDiscussionPost
    {
        $this->authorizeLesson($viewer, $lesson);
        abort_unless($post->lesson_id === $lesson->id && $post->parent_id === null, 404);
        abort_if($post->status !== DiscussionPostStatus::Visible && ! $viewer->isAdmin(), 404);
        return $post;
    }

    public function create(User $author, Lesson $lesson, string $body, ?string $parentId, ?string $replyToId): LessonDiscussionPost
    {
        $this->authorizeLesson($author, $lesson);
        $body = trim($body);
        if (mb_strlen($body) < 10 || mb_strlen($body) > 2000) {
            throw ValidationException::withMessages(['body' => 'Tulis 10 sampai 2.000 karakter.']);
        }
        if (! $parentId && $replyToId) {
            throw ValidationException::withMessages(['reply_to_id' => 'Pilih pertanyaan sebelum membalas.']);
        }

        return DB::transaction(function () use ($author, $lesson, $body, $parentId, $replyToId): LessonDiscussionPost {
            $lockedAuthor = User::query()->whereKey($author->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedAuthor->is_active, 403);
            $this->authorizeLesson($author, $lesson);
            $today = now()->utc()->startOfDay();
            if (LessonDiscussionPost::query()->withTrashed()->where('user_id', $author->id)->where('created_at', '>=', $today)->count() >= 50) {
                throw ValidationException::withMessages(['body' => 'Batas 50 kiriman hari ini telah tercapai. Coba lagi besok.']);
            }

            $root = null;
            $replyTo = null;
            if ($parentId) {
                $root = LessonDiscussionPost::query()->whereKey($parentId)->where('lesson_id', $lesson->id)
                    ->whereNull('parent_id')->where('status', DiscussionPostStatus::Visible->value)->lockForUpdate()->first();
                if (! $root) throw ValidationException::withMessages(['parent_id' => 'Pertanyaan tidak tersedia untuk dibalas.']);
                $replyTo = $replyToId
                    ? LessonDiscussionPost::query()->whereKey($replyToId)->where('lesson_id', $lesson->id)
                        ->where('status', DiscussionPostStatus::Visible->value)->lockForUpdate()->first() : $root;
                if (! $replyTo || ($replyTo->id !== $root->id && $replyTo->parent_id !== $root->id)) {
                    throw ValidationException::withMessages(['reply_to_id' => 'Komentar yang ditanggapi tidak tersedia.']);
                }
            }

            $duplicate = LessonDiscussionPost::query()->withTrashed()->where('lesson_id', $lesson->id)
                ->where('user_id', $author->id)->where('parent_id', $root?->id)
                ->where('body', $body)->where('created_at', '>=', now()->subSeconds(30))->exists();
            if ($duplicate) throw ValidationException::withMessages(['body' => 'Kiriman yang sama baru saja dibuat.']);

            $post = LessonDiscussionPost::query()->create([
                'lesson_id' => $lesson->id, 'user_id' => $author->id, 'parent_id' => $root?->id,
                'reply_to_id' => $replyTo?->id, 'body' => $body, 'status' => DiscussionPostStatus::Visible,
            ]);
            $this->logger->log('created', $author, 'lesson_discussion_posts', $post->id, null,
                ['lesson_id' => $lesson->id, 'parent_id' => $root?->id]);

            if (! $root) {
                User::query()->where('role', UserRole::Admin->value)->where('is_active', true)->select('id')->chunkById(100,
                    function ($admins) use ($post, $author): void {
                        foreach ($admins as $admin) {
                            if ($admin->id !== $author->id) $this->notifications->discussionQuestion($post, $admin->id, $author->id);
                        }
                    });
            } else {
                $recipientIds = array_unique(array_filter([$root->user_id, $replyTo->user_id], fn ($id) => $id !== $author->id));
                $recipients = User::query()->whereIn('id', $recipientIds)->where('is_active', true)->get();
                foreach ($recipients as $recipient) {
                    if ($recipient->isAdmin() || $this->enrollments->activeFor($recipient, $lesson->course)) {
                        $this->notifications->discussionReply($post, $recipient->id, $author->id);
                    }
                }
            }
            return $post;
        });
    }

    public function delete(User $actor, Lesson $lesson, LessonDiscussionPost $post): void
    {
        $this->authorizeLesson($actor, $lesson);
        abort_unless($post->lesson_id === $lesson->id && ($post->user_id === $actor->id || $actor->isAdmin()), 403);
        DB::transaction(function () use ($actor, $post): void {
            $locked = LessonDiscussionPost::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
            $locked->delete();
            $this->logger->log('deleted', $actor, 'lesson_discussion_posts', $locked->id,
                ['lesson_id' => $locked->lesson_id, 'status' => $locked->status->value], null, 'Kiriman diskusi dihapus');
        });
    }

    public function moderate(User $admin, LessonDiscussionPost $post, DiscussionPostStatus $status): void
    {
        abort_unless($admin->isAdmin(), 403);
        DB::transaction(function () use ($admin, $post, $status): void {
            $locked = LessonDiscussionPost::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === $status) return;
            $old = $locked->status->value;
            $locked->update(['status' => $status]);
            $this->logger->log('updated', $admin, 'lesson_discussion_posts', $locked->id,
                ['lesson_id' => $locked->lesson_id, 'status' => $old], ['status' => $status->value], 'Status moderasi diubah');
        });
    }
}
