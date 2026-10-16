<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('operation_states', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('task', 80)->unique();
            $table->json('cursor')->nullable();
            $table->timestamp('last_started_at')->nullable();
            $table->timestamp('last_succeeded_at')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->string('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_states');
    }
};
