<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('enrollments', fn (Blueprint $table) => $table->index(['status', 'expires_at'], 'enr_status_exp_idx'));
        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['status', 'expires_at'], 'ord_status_exp_idx');
            $table->index(['status', 'paid_at'], 'ord_status_paid_idx');
        });
        Schema::table('payments', fn (Blueprint $table) => $table->index(['status', 'created_at'], 'pay_status_created_idx'));
        Schema::table('playback_sessions', fn (Blueprint $table) => $table->index(['created_at', 'hard_expires_at'], 'playback_created_hard_idx'));
        Schema::table('video_uploads', fn (Blueprint $table) => $table->index(['cleaned_at', 'last_activity_at', 'status'], 'video_upload_cleanup_idx'));
        Schema::table('system_logs', function (Blueprint $table): void {
            $table->index(['created_at'], 'syslog_created_idx');
            $table->index(['action', 'created_at'], 'syslog_action_created_idx');
            $table->index(['table_name', 'created_at'], 'syslog_table_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('system_logs', function (Blueprint $table): void {
            $table->dropIndex('syslog_table_created_idx');
            $table->dropIndex('syslog_action_created_idx');
            $table->dropIndex('syslog_created_idx');
        });
        Schema::table('video_uploads', fn (Blueprint $table) => $table->dropIndex('video_upload_cleanup_idx'));
        Schema::table('playback_sessions', fn (Blueprint $table) => $table->dropIndex('playback_created_hard_idx'));
        Schema::table('payments', fn (Blueprint $table) => $table->dropIndex('pay_status_created_idx'));
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('ord_status_exp_idx');
            $table->dropIndex('ord_status_paid_idx');
        });
        Schema::table('enrollments', fn (Blueprint $table) => $table->dropIndex('enr_status_exp_idx'));
    }
};
