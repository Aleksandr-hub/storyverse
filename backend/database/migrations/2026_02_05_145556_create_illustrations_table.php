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
        Schema::create('illustrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('story_id')->nullable()->constrained('stories')->cascadeOnDelete();
            $table->foreignUuid('chapter_id')->nullable()->constrained('chapters')->cascadeOnDelete();
            $table->string('image_url', 500);
            $table->text('prompt')->nullable();
            $table->unsignedInteger('position')->nullable();
            $table->timestamps();

            $table->index('story_id');
            $table->index('chapter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('illustrations');
    }
};
