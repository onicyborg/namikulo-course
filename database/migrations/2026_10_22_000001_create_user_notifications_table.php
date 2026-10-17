<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->string('title', 160);
            $table->string('summary', 300);
            $table->string('dedupe_key', 191);
            $table->foreignUuid('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignUuid('lesson_id')->nullable()->constrained('lessons')->nullOnDelete();
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignUuid('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'dedupe_key'], 'un_user_dedupe_uq');
            $table->index(['user_id', 'read_at', 'created_at'], 'un_user_read_created_idx');
            $table->index(['user_id', 'created_at'], 'un_user_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
