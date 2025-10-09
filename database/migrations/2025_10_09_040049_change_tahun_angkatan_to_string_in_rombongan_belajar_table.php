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
            $table->string('tahun_angkatan')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rombongan_belajar', function (Blueprint $table) {
            $table->integer('tahun_angkatan')->change();
        });
    }
};
