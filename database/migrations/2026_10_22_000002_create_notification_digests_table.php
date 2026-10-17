<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_digests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->date('digest_date');
            $table->timestamp('cutoff_at');
            $table->string('status', 20);
            $table->unsignedInteger('item_count')->default(0);
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('error_class', 120)->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'digest_date'], 'nd_user_date_uq');
            $table->index(['digest_date', 'status'], 'nd_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_digests');
    }
};
