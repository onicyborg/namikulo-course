<?php

namespace App\Models;

use App\Enums\DiscussionPostStatus;
use App\Enums\DiscussionBodyFormat;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonDiscussionPost extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['lesson_id', 'user_id', 'parent_id', 'reply_to_id', 'body', 'body_format', 'status'];

    public function plainBody(): string
    {
        return app(\App\Services\DiscussionContent::class)->text($this->body, $this->body_format?->value ?? 'plain');
    }

    public function formattedBody(): string
    {
        return app(\App\Services\DiscussionContent::class)->html($this->body, $this->body_format?->value ?? 'plain');
    }

    protected function casts(): array
    {
        return ['status' => DiscussionPostStatus::class, 'body_format' => DiscussionBodyFormat::class];
    }

    public function lesson(): BelongsTo { return $this->belongsTo(Lesson::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function root(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function replyTo(): BelongsTo { return $this->belongsTo(self::class, 'reply_to_id'); }
    public function replies(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
}
