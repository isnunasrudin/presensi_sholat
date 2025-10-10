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
        Schema::table('rombongan_belajar', function (Blueprint $table) {
            $table->string('tingkat')->after('nama_rombel')->nullable();

            // Update unique constraint to include tingkat
            $table->dropUnique(['nama_rombel', 'tahun_angkatan']);
            $table->unique(['nama_rombel', 'tingkat', 'tahun_angkatan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rombongan_belajar', function (Blueprint $table) {
            $table->dropColumn('tingkat');

            // Restore original unique constraint
            $table->dropUnique(['nama_rombel', 'tingkat', 'tahun_angkatan']);
            $table->unique(['nama_rombel', 'tahun_angkatan']);
        });
    }
};