<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('editor_images', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('course_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignUuid('lesson_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('scope', 20);
            $table->string('path', 255);
            $table->string('mime_type', 30);
            $table->unsignedInteger('size_bytes');
            $table->timestamps();
            $table->index(['user_id', 'created_at'], 'editor_images_user_created_idx');
            $table->index(['course_id', 'created_at'], 'editor_images_course_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_images');
    }
};
