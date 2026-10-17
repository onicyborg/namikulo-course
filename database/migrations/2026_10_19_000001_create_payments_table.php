<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 20)->default('midtrans');
            $table->string('midtrans_order_id', 50)->unique();
            $table->string('transaction_id')->nullable()->index();
            $table->string('payment_type', 30);
            $table->string('channel', 30)->nullable();
            $table->string('status', 20)->index();
            $table->string('fraud_status', 20)->nullable();
            $table->unsignedBigInteger('gross_amount');
            $table->string('va_number')->nullable();
            $table->text('qr_url')->nullable();
            $table->text('deeplink_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status'], 'pay_order_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
