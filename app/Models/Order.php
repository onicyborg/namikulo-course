<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUuids;

    protected $fillable = ['order_number', 'user_id', 'course_id', 'course_title', 'amount', 'status', 'expires_at', 'paid_at', 'cancelled_at', 'payment_email_sent_at', 'payment_email_last_attempt_at'];

    protected function casts(): array
    {
        return ['status' => OrderStatus::class, 'amount' => 'integer', 'expires_at' => 'datetime', 'paid_at' => 'datetime', 'cancelled_at' => 'datetime', 'payment_email_sent_at' => 'datetime', 'payment_email_last_attempt_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
    public function enrollment(): HasOne { return $this->hasOne(Enrollment::class); }
    public function invoice(): HasOne { return $this->hasOne(OrderInvoice::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
