<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_uploads', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('lesson_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->index();
            $table->text('encryption_key');
            $table->string('iv_hex', 32);
            $table->unsignedInteger('duration_seconds');
            $table->json('manifest')->nullable();
            $table->unsignedBigInteger('expected_bytes')->nullable();
            $table->unsignedBigInteger('received_bytes')->default(0);
            $table->unsignedInteger('validation_cursor')->default(0);
            $table->string('storage_path');
            $table->string('error_message')->nullable();
            $table->timestamp('last_activity_at');
            $table->timestamp('expires_at');
            $table->timestamp('cleaned_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'expires_at'], 'video_uploads_user_status_exp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_uploads');
    }
};
