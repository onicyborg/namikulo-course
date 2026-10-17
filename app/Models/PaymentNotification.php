<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentNotification extends Model
{
    use HasUuids;

    public const SAFE_PAYLOAD_FIELDS = [
        'order_id', 'merchant_id', 'transaction_id', 'transaction_status', 'status_code',
        'gross_amount', 'payment_type', 'fraud_status', 'transaction_time', 'settlement_time', 'currency',
    ];

    public static function safePayload(array $payload): array
    {
        $safe = [];
        foreach (self::SAFE_PAYLOAD_FIELDS as $field) {
            if (isset($payload[$field]) && is_scalar($payload[$field])) {
                $safe[$field] = is_string($payload[$field]) ? mb_substr($payload[$field], 0, 255) : $payload[$field];
            }
        }
        return $safe;
    }

    protected $fillable = ['payment_id', 'midtrans_order_id', 'transaction_status', 'signature_valid', 'payload', 'processed_at', 'process_result'];

    protected function casts(): array
    {
        return ['signature_valid' => 'boolean', 'payload' => 'array', 'processed_at' => 'datetime'];
    }

    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
}
