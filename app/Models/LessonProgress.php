<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    use HasUuids;

    protected $table = 'lesson_progress';

    protected $fillable = ['user_id', 'course_id', 'lesson_id', 'last_position_seconds', 'watched_seconds', 'completed_at'];

    protected function casts(): array
    {
        return ['last_position_seconds' => 'integer', 'watched_seconds' => 'integer', 'completed_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function lesson(): BelongsTo { return $this->belongsTo(Lesson::class); }
}
