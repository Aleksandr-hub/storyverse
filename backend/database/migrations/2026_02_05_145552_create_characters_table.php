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
        Schema::create('characters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('universe_id')->nullable()->constrained('universes')->nullOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->jsonb('traits')->nullable();
            $table->boolean('is_canonical')->default(false);
            $table->foreignUuid('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('universe_id');
            $table->index('is_canonical');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
