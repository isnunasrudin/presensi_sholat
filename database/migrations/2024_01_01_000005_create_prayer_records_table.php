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
            $table->enum('prayer_type', ['subuh', 'dhuhur', 'ashar', 'maghrib', 'isya', 'dhuha']);
            $table->date('date');
            $table->enum('status', ['sholat', 'halangan', 'sakit', 'kosong'])->default('kosong');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'prayer_type', 'date']);
            $table->index(['user_id', 'date']);
            $table->index(['prayer_type', 'date']);
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