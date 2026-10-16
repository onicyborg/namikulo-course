<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable();
            $table->string('action', 40)->index();
            $table->string('table_name', 80)->nullable();
            $table->string('record_id', 36)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id', 'syslog_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['table_name', 'record_id'], 'syslog_table_record_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
