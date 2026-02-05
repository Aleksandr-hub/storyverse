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
        Schema::create('stories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 500);
            $table->string('slug', 500);
            $table->text('description')->nullable();
            $table->string('cover_url', 500)->nullable();
            $table->foreignUuid('universe_id')->nullable()->constrained('universes')->nullOnDelete();
            $table->foreignUuid('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 50)->default('draft');
            $table->string('mode', 50)->default('story');
            $table->boolean('is_public')->default(true);
            $table->string('rating', 10)->default('G');
            $table->unsignedInteger('word_count')->default(0);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('like_count')->default(0);
            $table->jsonb('settings')->nullable();
            $table->timestamps();
            $table->timestamp('published_at')->nullable();

            $table->index('slug');
            $table->index('status');
            $table->index('mode');
            $table->index('is_public');
            $table->index('author_id');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
