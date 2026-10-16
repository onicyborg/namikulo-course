<?php

namespace App\Models;

use App\Enums\CourseStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = ['category_id', 'title', 'slug', 'summary', 'description', 'thumbnail_path', 'instructor_name', 'price', 'discount_price', 'access_months', 'status', 'is_featured', 'featured_order', 'published_at'];

    protected function casts(): array
    {
        return ['status' => CourseStatus::class, 'is_featured' => 'boolean', 'published_at' => 'datetime', 'price' => 'integer', 'discount_price' => 'integer', 'access_months' => 'integer', 'featured_order' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('sort_order');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
