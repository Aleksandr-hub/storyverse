<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('story_id')->constrained('stories')->cascadeOnDelete();
            $table->string('title', 500)->nullable();
            $table->text('content')->nullable();
            $table->unsignedInteger('chapter_number');
            $table->unsignedInteger('word_count')->default(0);
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_ai_generated')->default(false);
            $table->timestamps();

            $table->index(['story_id', 'chapter_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
