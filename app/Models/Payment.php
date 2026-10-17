<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = ['order_id', 'gateway', 'midtrans_order_id', 'transaction_id', 'payment_type', 'channel', 'status', 'fraud_status', 'gross_amount', 'va_number', 'qr_url', 'deeplink_url', 'expires_at', 'paid_at', 'raw_response', 'instruction_email_sent_at', 'instruction_email_last_attempt_at'];

    protected function casts(): array
    {
        return ['status' => PaymentStatus::class, 'gross_amount' => 'integer', 'expires_at' => 'datetime', 'paid_at' => 'datetime', 'raw_response' => 'array', 'instruction_email_sent_at' => 'datetime', 'instruction_email_last_attempt_at' => 'datetime'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
