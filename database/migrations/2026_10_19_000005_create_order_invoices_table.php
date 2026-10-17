<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->unique('invoice_order_uq')->constrained('orders')->restrictOnDelete();
            $table->string('invoice_number', 50)->unique('invoice_number_uq');
            $table->string('order_number', 40);
            $table->string('seller_name');
            $table->text('seller_address')->nullable();
            $table->string('seller_email')->nullable();
            $table->string('seller_tax_id', 50)->nullable();
            $table->string('buyer_name');
            $table->string('buyer_email');
            $table->string('course_title');
            $table->string('payment_method', 50)->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('display_timezone', 64);
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_invoices');
    }
};
