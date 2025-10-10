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
            // Check if the table has a unique constraint before dropping it
            $indexes = \DB::select("PRAGMA index_list('prayer_records')");
            $uniqueIndexExists = false;
            $indexName = null;

            foreach ($indexes as $index) {
                if ($index->unique == 1) {
                    $uniqueIndexExists = true;
                    $indexName = $index->name;
                    break;
                }
            }

            if ($uniqueIndexExists && $indexName) {
                // Drop the unique constraint using the index name
                $table->dropUnique($indexName);
            }

            // For SQLite, we need to recreate the table for enum changes
            // This is a safer approach for SQLite databases
            if (\DB::connection()->getDriverName() === 'sqlite') {
                // Get all existing data
                $data = \DB::table('prayer_records')->get();

                // Drop and recreate the table
                Schema::dropIfExists('prayer_records');

                // Recreate the table with new enum values
                Schema::create('prayer_records', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->constrained()->onDelete('cascade');
                    $table->enum('prayer_type', ['subuh', 'dhuhur', 'ashar', 'maghrib', 'isya', 'dhuha']);
                    $table->date('date');
                    $table->enum('status', ['sholat', 'halangan', 'sakit', 'kosong'])->default('kosong');
                    $table->text('keterangan')->nullable();
                    $table->timestamps();

                    // Add unique constraint
                    $table->unique(['user_id', 'prayer_type', 'date']);
                });

                // Restore the data if any
                if ($data->isNotEmpty()) {
                    foreach ($data as $record) {
                        \DB::table('prayer_records')->insert([
                            'user_id' => $record->user_id,
                            'prayer_type' => $record->prayer_type,
                            'date' => $record->date,
                            'status' => $record->status ?? 'kosong',
                            'keterangan' => $record->keterangan ?? null,
                            'created_at' => $record->created_at ?? now(),
                            'updated_at' => $record->updated_at ?? now(),
                        ]);
                    }
                }
            } else {
                // For MySQL/PostgreSQL, use the standard approach
                $table->enum('prayer_type', ['subuh', 'dhuhur', 'ashar', 'maghrib', 'isya', 'dhuha'])->change();

                // Re-add the unique constraint
                $table->unique(['user_id', 'prayer_type', 'date']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prayer_records', function (Blueprint $table) {
            // For SQLite, we need to recreate the table again
            if (\DB::connection()->getDriverName() === 'sqlite') {
                // Get all existing data
                $data = \DB::table('prayer_records')->get();

                // Drop and recreate the table with original enum values
                Schema::dropIfExists('prayer_records');

                // Recreate the table with original enum values
                Schema::create('prayer_records', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->constrained()->onDelete('cascade');
                    $table->enum('prayer_type', ['dhuhur', 'dhuha']);
                    $table->date('date');
                    $table->enum('status', ['sholat', 'halangan', 'sakit', 'kosong'])->default('kosong');
                    $table->text('keterangan')->nullable();
                    $table->timestamps();

                    // Add unique constraint
                    $table->unique(['user_id', 'prayer_type', 'date']);
                });

                // Restore only dhuhur and dhuha records
                if ($data->isNotEmpty()) {
                    foreach ($data as $record) {
                        if (in_array($record->prayer_type, ['dhuhur', 'dhuha'])) {
                            \DB::table('prayer_records')->insert([
                                'user_id' => $record->user_id,
                                'prayer_type' => $record->prayer_type,
                                'date' => $record->date,
                                'status' => $record->status ?? 'kosong',
                                'keterangan' => $record->keterangan ?? null,
                                'created_at' => $record->created_at ?? now(),
                                'updated_at' => $record->updated_at ?? now(),
                            ]);
                        }
                    }
                }
            } else {
                // For MySQL/PostgreSQL, use the standard approach
                // Drop the unique constraint
                $table->dropUnique(['user_id', 'prayer_type', 'date']);

                // Revert to original enum values
                $table->enum('prayer_type', ['dhuhur', 'dhuha'])->change();

                // Re-add the unique constraint
                $table->unique(['user_id', 'prayer_type', 'date']);
            }
        });
    }
};
