<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lesson_discussion_posts', function (Blueprint $table): void {
            $table->string('body_format', 20)->default('plain');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_discussion_posts', function (Blueprint $table): void {
            $table->dropColumn('body_format');
        });
    }
};
