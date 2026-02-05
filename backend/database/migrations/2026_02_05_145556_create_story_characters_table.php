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
        Schema::create('story_characters', function (Blueprint $table) {
            $table->foreignUuid('story_id')->constrained('stories')->cascadeOnDelete();
            $table->foreignUuid('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('role', 50)->nullable();

            $table->primary(['story_id', 'character_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_characters');
    }
};
