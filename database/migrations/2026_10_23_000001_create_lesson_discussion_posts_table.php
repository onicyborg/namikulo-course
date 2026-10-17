<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lesson_discussion_posts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_id')->constrained('lessons')->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('parent_id')->nullable();
            $table->foreignUuid('reply_to_id')->nullable();
            $table->text('body');
            $table->string('status', 20)->default('visible');
            $table->softDeletes();
            $table->timestamps();
            $table->index(['lesson_id', 'parent_id', 'created_at'], 'ldp_lesson_parent_created_idx');
            $table->index(['parent_id', 'created_at'], 'ldp_parent_created_idx');
        });

        Schema::table('lesson_discussion_posts', function (Blueprint $table): void {
            $table->foreign('parent_id', 'ldp_parent_fk')
                ->references('id')->on('lesson_discussion_posts')->restrictOnDelete();
            $table->foreign('reply_to_id', 'ldp_reply_to_fk')
                ->references('id')->on('lesson_discussion_posts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_discussion_posts');
    }
};
