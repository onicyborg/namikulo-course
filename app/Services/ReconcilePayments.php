<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\OperationState;
use App\Models\Payment;
use App\Services\Checkout\PaymentService;

class ReconcilePayments
{
    public const TASK = 'reconcile-payments';

    public function __construct(private readonly PaymentService $payments, private readonly ActivityLogger $logger) {}

    public function run(int $batchSize, int $maxSeconds): array
    {
        $state = OperationState::query()->firstOrCreate(['task' => self::TASK], ['cursor' => null]);
        if ($state->next_attempt_at?->isFuture()) return ['status' => 'partial', 'processed' => 0, 'remaining' => true];
        $state->update(['last_started_at' => now()]);
        $started = microtime(true);
        $processed = 0;
        $afterId = $state->cursor['after_id'] ?? null;
        $statuses = [PaymentStatus::Initiating->value, PaymentStatus::Unknown->value, PaymentStatus::Pending->value];

        try {
            while ($processed < $batchSize && microtime(true) - $started < $maxSeconds - 1) {
                $query = Payment::query()->whereIn('status', $statuses)
                    ->where('created_at', '<=', now()->subMinutes(10))->orderBy('id');
                if ($afterId) $query->where('id', '>', $afterId);
                $payment = $query->first();
                if (! $payment) {
                    if ($afterId !== null) $state->update(['cursor' => null]);
                    break;
                }
                $this->payments->sync($payment);
                $afterId = $payment->id;
                $state->update(['cursor' => ['after_id' => $afterId], 'last_error' => null, 'next_attempt_at' => null]);
                $processed++;
            }
            $remaining = Payment::query()->whereIn('status', $statuses)
                ->where('created_at', '<=', now()->subMinutes(10))->exists();
            if (! $remaining) $state->update(['cursor' => null, 'last_succeeded_at' => now(), 'last_error' => null, 'next_attempt_at' => null]);
            $this->logger->log('operation', null, 'operation_states', $state->id, null,
                ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining], 'Batch sinkron pembayaran');
            return ['status' => $remaining ? 'partial' : 'completed', 'processed' => $processed, 'remaining' => $remaining];
        } catch (\Throwable $exception) {
            $state->update(['last_error' => $exception::class, 'next_attempt_at' => now()->addMinutes(5)]);
            $this->logger->log('operation_failed', null, 'operation_states', $state->id, null,
                ['error' => $exception::class, 'processed' => $processed], 'Batch sinkron pembayaran gagal');
            throw $exception;
        }
    }
}
