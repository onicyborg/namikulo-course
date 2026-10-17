<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaybackSession extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'lesson_id', 'video_asset_id', 'token_hash', 'hls_path', 'encryption_key', 'is_preview', 'expires_at', 'hard_expires_at', 'last_heartbeat_at', 'revoked_at'];
    protected $hidden = ['token_hash', 'encryption_key'];

    protected function casts(): array
    {
        return ['encryption_key' => 'encrypted', 'is_preview' => 'boolean', 'expires_at' => 'datetime', 'hard_expires_at' => 'datetime', 'last_heartbeat_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function lesson(): BelongsTo { return $this->belongsTo(Lesson::class); }
    public function videoAsset(): BelongsTo { return $this->belongsTo(VideoAsset::class); }
}
