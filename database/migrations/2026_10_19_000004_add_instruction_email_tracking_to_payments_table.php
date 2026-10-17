<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->timestamp('instruction_email_sent_at')->nullable();
            $table->timestamp('instruction_email_last_attempt_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['instruction_email_sent_at', 'instruction_email_last_attempt_at']);
        });
    }
};
