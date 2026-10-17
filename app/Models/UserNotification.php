<?php

namespace App\Models;

use App\Enums\UserNotificationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'actor_id', 'type', 'title', 'summary', 'dedupe_key', 'course_id', 'lesson_id', 'order_id', 'enrollment_id', 'discussion_post_id', 'read_at'];

    protected function casts(): array
    {
        return ['type' => UserNotificationType::class, 'read_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class)->withTrashed(); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function discussionPost(): BelongsTo { return $this->belongsTo(LessonDiscussionPost::class); }
}
