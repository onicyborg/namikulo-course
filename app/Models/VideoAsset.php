<?php

namespace App\Models;

use App\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoAsset extends Model
{
    use HasUuids;

    protected $fillable = ['lesson_id', 'status', 'original_path', 'hls_path', 'encryption_key', 'renditions', 'duration_seconds', 'size_bytes', 'error_message', 'processed_at'];
    protected $hidden = ['encryption_key'];

    protected function casts(): array
    {
        return ['status' => VideoStatus::class, 'renditions' => 'array', 'processed_at' => 'datetime', 'encryption_key' => 'encrypted', 'duration_seconds' => 'integer', 'size_bytes' => 'integer'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
