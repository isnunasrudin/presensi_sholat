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
        Schema::create('prayer_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('prayer_type', ['dhuhur', 'dhuha']);
            $table->date('date');
            $table->enum('status', ['sholat', 'tidak_sholat', 'halangan']);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Unique constraint: one record per user per prayer type per day
            $table->unique(['user_id', 'prayer_type', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prayer_records');
    }
};
