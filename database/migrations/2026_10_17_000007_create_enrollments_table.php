<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('course_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('order_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->string('source', 20);
            $table->string('status', 20)->index();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->index();
            $table->foreignUuid('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignUuid('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'course_id', 'status'], 'enr_user_course_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
