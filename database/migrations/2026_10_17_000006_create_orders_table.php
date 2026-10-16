<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('order_number', 40)->unique();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('course_id')->constrained()->restrictOnDelete();
            $table->string('course_title');
            $table->unsignedBigInteger('amount');
            $table->string('status', 20)->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
