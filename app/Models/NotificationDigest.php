<?php

namespace App\Models;

use App\Enums\NotificationDigestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationDigest extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'digest_date', 'cutoff_at', 'status', 'item_count', 'attempted_at', 'sent_at', 'error_class'];

    protected function casts(): array
    {
        return ['digest_date' => 'date:Y-m-d', 'cutoff_at' => 'datetime', 'status' => NotificationDigestStatus::class,
            'attempted_at' => 'datetime', 'sent_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
