<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentNotification extends Model
{
    use HasUuids;

    protected $fillable = ['payment_id', 'midtrans_order_id', 'transaction_status', 'signature_valid', 'payload', 'processed_at', 'process_result'];

    protected function casts(): array
    {
        return ['signature_valid' => 'boolean', 'payload' => 'array', 'processed_at' => 'datetime'];
    }

    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
}
