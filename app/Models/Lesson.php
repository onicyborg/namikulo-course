<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['course_id', 'section_id', 'title', 'description', 'sort_order', 'duration_seconds'];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function videoAsset(): HasOne
    {
        return $this->hasOne(VideoAsset::class);
    }

    public function discussionPosts(): HasMany
    {
        return $this->hasMany(LessonDiscussionPost::class);
    }
}
