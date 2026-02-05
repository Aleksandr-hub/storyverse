<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convert MPAA ratings to Ukrainian age ratings:
     * G -> 0+, PG -> 6+, PG-13 -> 12+, R -> 18+
     */
    public function up(): void
    {
        // Update existing ratings
        DB::table('stories')->where('rating', 'G')->update(['rating' => '0+']);
        DB::table('stories')->where('rating', 'PG')->update(['rating' => '6+']);
        DB::table('stories')->where('rating', 'PG-13')->update(['rating' => '12+']);
        DB::table('stories')->where('rating', 'R')->update(['rating' => '18+']);

        // Change default value
        Schema::table('stories', function (Blueprint $table) {
            $table->string('rating', 10)->default('0+')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert ratings
        DB::table('stories')->where('rating', '0+')->update(['rating' => 'G']);
        DB::table('stories')->where('rating', '6+')->update(['rating' => 'PG']);
        DB::table('stories')->where('rating', '12+')->update(['rating' => 'PG-13']);
        DB::table('stories')->where('rating', '16+')->update(['rating' => 'PG-13']);
        DB::table('stories')->where('rating', '18+')->update(['rating' => 'R']);

        // Change default value back
        Schema::table('stories', function (Blueprint $table) {
            $table->string('rating', 10)->default('G')->change();
        });
    }
};
