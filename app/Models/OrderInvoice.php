<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInvoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'invoice_number', 'order_number', 'seller_name', 'seller_address',
        'seller_email', 'seller_tax_id', 'buyer_name', 'buyer_email', 'course_title',
        'payment_method', 'amount', 'display_timezone', 'issued_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'issued_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
