<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('playback_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('lesson_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('video_asset_id')->constrained('video_assets')->restrictOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('hls_path');
            $table->text('encryption_key');
            $table->boolean('is_preview')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('hard_expires_at');
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'revoked_at', 'expires_at'], 'playback_user_active_idx');
            $table->index(['hls_path', 'expires_at'], 'playback_path_exp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playback_sessions');
    }
};
