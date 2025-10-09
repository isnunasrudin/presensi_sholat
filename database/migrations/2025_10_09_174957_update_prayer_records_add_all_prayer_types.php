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
        Schema::table('prayer_records', function (Blueprint $table) {
            // Drop the unique constraint first
            $table->dropUnique(['user_id', 'prayer_type', 'date']);

            // Modify the enum to include all prayer types
            $table->enum('prayer_type', ['subuh', 'dhuhur', 'ashar', 'maghrib', 'isya', 'dhuha'])->change();

            // Re-add the unique constraint
            $table->unique(['user_id', 'prayer_type', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prayer_records', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique(['user_id', 'prayer_type', 'date']);

            // Revert to original enum values
            $table->enum('prayer_type', ['dhuhur', 'dhuha'])->change();

            // Re-add the unique constraint
            $table->unique(['user_id', 'prayer_type', 'date']);
        });
    }
};
