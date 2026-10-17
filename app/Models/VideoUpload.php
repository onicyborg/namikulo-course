<?php

namespace App\Models;

use App\Enums\UploadStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoUpload extends Model
{
    use HasUuids;

    protected $fillable = ['id', 'user_id', 'lesson_id', 'status', 'encryption_key', 'iv_hex', 'duration_seconds', 'manifest', 'expected_bytes', 'received_bytes', 'validation_cursor', 'storage_path', 'error_message', 'last_activity_at', 'expires_at', 'cleaned_at'];
    protected $hidden = ['encryption_key', 'manifest'];

    protected function casts(): array
    {
        return ['status' => UploadStatus::class, 'encryption_key' => 'encrypted', 'manifest' => 'array', 'last_activity_at' => 'datetime', 'expires_at' => 'datetime', 'cleaned_at' => 'datetime', 'duration_seconds' => 'integer', 'validation_cursor' => 'integer', 'expected_bytes' => 'integer', 'received_bytes' => 'integer'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function lesson(): BelongsTo { return $this->belongsTo(Lesson::class); }
}
