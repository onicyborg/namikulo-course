<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\OperationState;
use App\Models\Order;
use App\Services\Checkout\PaymentService;

class ExpirePendingOrders
{
    public const TASK = 'expire-pending-orders';

    public function __construct(private readonly PaymentService $payments, private readonly ActivityLogger $logger) {}

    public function run(int $batchSize, int $maxSeconds): array
    {
        $state = OperationState::query()->firstOrCreate(['task' => self::TASK], ['cursor' => null]);
        if ($state->next_attempt_at?->isFuture()) return ['status' => 'partial', 'processed' => 0, 'remaining' => true];
        $state->update(['last_started_at' => now()]);
        $started = microtime(true);
        $processed = 0;
        $afterId = $state->cursor['after_id'] ?? null;

        try {
            while ($processed < $batchSize && microtime(true) - $started < $maxSeconds - 1) {
                $query = Order::query()->where('status', OrderStatus::Pending->value)
                    ->where('expires_at', '<=', now())->orderBy('id');
                if ($afterId) $query->where('id', '>', $afterId);
                $order = $query->first();
                if (! $order) {
                    if ($afterId !== null) $state->update(['cursor' => null]);
                    break;
                }

                $payment = $order->payments()->latest()->first();
                if ($payment && in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Unknown, PaymentStatus::Initiating], true)) {
                    $this->payments->sync($payment);
                    $order->refresh();
                }
                if ($order->status === OrderStatus::Pending
                    && (! $payment || ! in_array($payment->fresh()->status, [PaymentStatus::Pending, PaymentStatus::Unknown, PaymentStatus::Initiating], true))) {
                    $order->update(['status' => OrderStatus::Expired]);
                    $this->logger->log('updated', null, 'orders', $order->id, ['status' => 'pending'], ['status' => 'expired']);
                }
                $afterId = $order->id;
                $state->update(['cursor' => ['after_id' => $afterId], 'last_error' => null, 'next_attempt_at' => null]);
                $processed++;
            }
            $remaining = Order::query()->where('status', OrderStatus::Pending->value)
                ->where('expires_at', '<=', now())->exists();
            if (! $remaining) $state->update(['cursor' => null, 'last_succeeded_at' => now(), 'last_error' => null, 'next_attempt_at' => null]);
            $this->logger->log('operation', null, 'operation_states', $state->id, null,
                ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining], 'Batch kedaluwarsa pesanan');
            return ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining];
        } catch (\Throwable $exception) {
            $state->update(['last_error' => $exception::class, 'next_attempt_at' => now()->addMinutes(5)]);
            $this->logger->log('operation_failed', null, 'operation_states', $state->id, null,
                ['error' => $exception::class, 'processed' => $processed], 'Batch kedaluwarsa pesanan gagal');
            throw $exception;
        }
    }
}
