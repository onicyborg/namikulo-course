<?php

namespace App\Models;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'course_id', 'order_id', 'source', 'status', 'starts_at', 'expires_at', 'granted_by', 'revoked_at', 'revoked_by', 'note'];

    protected function casts(): array
    {
        return ['source' => EnrollmentSource::class, 'status' => EnrollmentStatus::class, 'starts_at' => 'datetime', 'expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function grantedBy(): BelongsTo { return $this->belongsTo(User::class, 'granted_by'); }
    public function revokedBy(): BelongsTo { return $this->belongsTo(User::class, 'revoked_by'); }
}
