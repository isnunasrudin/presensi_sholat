<?php

namespace App\Imports;

use App\Models\User;
use App\Models\RombonganBelajar;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Str;

class UsersImportOptimized implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errors = [];
    private $startTime;
    private $rombelCache = [];
    private $emailCache = [];

    public function __construct()
    {
        $this->startTime = microtime(true);
        // Pre-cache rombel data
        $this->preloadRombelCache();
    }

    /**
     * Pre-load rombel data to cache
     */
    private function preloadRombelCache()
    {
        $rombels = RombonganBelajar::select('id', 'nama_rombel', 'tingkat', 'tahun_angkatan')->get();

        foreach ($rombels as $rombel) {
            // Create composite key for exact match (nama_rombel + tingkat + tahun_angkatan)
            $key = strtolower(trim($rombel->nama_rombel)) . '|' .
                  strtolower(trim($rombel->tingkat ?? '')) . '|' .
                  trim($rombel->tahun_angkatan ?? '');
            $this->rombelCache[$key] = $rombel->id;
        }
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip if required fields are empty
        if (empty($row['name']) || empty($row['role']) ||
            empty($row['tahun_angkatan']) || empty($row['nama_rombel']) || empty($row['tingkat'])) {
            $this->skippedCount++;
            return null;
        }

        try {
            // Optimized rombel lookup using cache with auto-creation
            $rombonganBelajarId = null;
            if (!empty($row['nama_rombel']) && !empty($row['tahun_angkatan'])) {
                $rombelKey = strtolower(trim($row['nama_rombel'])) . '|' .
                             strtolower(trim($row['tingkat'] ?? '')) . '|' .
                             trim($row['tahun_angkatan']);

                // Check if rombel exists
                $rombonganBelajarId = $this->rombelCache[$rombelKey] ?? null;

                // If not found, create new rombel
                if (!$rombonganBelajarId) {
                    $rombonganBelajar = RombonganBelajar::create([
                        'nama_rombel' => trim($row['nama_rombel']),
                        'tingkat' => trim($row['tingkat'] ?? ''),
                        'tahun_angkatan' => trim($row['tahun_angkatan']),
                    ]);

                    $rombonganBelajarId = $rombonganBelajar->id;

                    // Add to cache
                    $this->rombelCache[$rombelKey] = $rombonganBelajarId;
                }
            }

            // Optimized email generation
            $email = $this->generateUniqueEmail($row['name'], $row['email'] ?? null);

            // Create user with batch insert ready data
            return new User([
                'name' => trim($row['name']),
                'email' => $email,
                'password' => Hash::make('password'),
                'role' => $row['role'],
                'nisn' => $row['nisn'] ? trim($row['nisn']) : null,
                'gender' => $row['gender'] ?? null,
                'rombongan_belajar_id' => $rombonganBelajarId,
                'tahun_angkatan' => $row['tahun_angkatan'] ?? null,
                'email_verified_at' => now(),
            ]);
        } catch (\Exception $e) {
            $this->skippedCount++;
            $this->errors[] = [
                'row' => $this->importedCount + $this->skippedCount,
                'error' => $e->getMessage(),
            ];
            return null;
        }
    }

    /**
     * Optimized email generation with caching
     */
    private function generateUniqueEmail($name, $providedEmail = null)
    {
        if ($providedEmail && filter_var($providedEmail, FILTER_VALIDATE_EMAIL)) {
            return $providedEmail;
        }

        $name = trim($name);
        $baseEmail = \Str::slug($name) . '@snesa.local';

        // Check cache first
        if (isset($this->emailCache[$baseEmail])) {
            return $this->emailCache[$baseEmail];
        }

        // Use database query only if not in cache
        $counter = 1;
        $email = $baseEmail;

        while (User::where('email', $email)->exists()) {
            $email = \Str::slug($name) . $counter . '@snesa.local';
            $counter++;
        }

        $this->emailCache[$baseEmail] = $email;
        return $email;
    }

    
    /**
     * Handle batch insert after chunk processing
     */
    public function chunk(array $rows): void
    {
        try {
            DB::transaction(function () use ($rows) {
                // Filter out null rows before insert
                $validRows = array_filter($rows, function($row) {
                    return $row !== null;
                });

                if (!empty($validRows)) {
                    // Use insertGetId for better performance
                    User::insert(array_map(function($user) {
                        return $user->getAttributes();
                    }, $validRows));

                    $this->importedCount += count($validRows);
                }
            });

            // Log progress every chunk
            $progress = round(($this->importedCount / 1000) * 100, 2);
            \Log::info("Import progress: {$this->importedCount} users imported");

        } catch (\Exception $e) {
            $this->skippedCount += count($rows);
            $this->errors[] = [
                'chunk_error' => $e->getMessage(),
                'chunk_size' => count($rows),
            ];
        }
    }

    /**
     * Batch size for better memory management
     *
     * @return int
     */
    public function batchSize(): int
    {
        return 200; // Smaller batches for better memory management
    }

    /**
     * Chunk size for processing
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 200; // Smaller chunks for better performance
    }

    /**
     * Get import results with performance metrics
     *
     * @return array
     */
    public function getResults()
    {
        $endTime = microtime(true);
        $executionTime = round($endTime - $this->startTime, 2);

        return [
            'imported_count' => $this->importedCount,
            'skipped_count' => $this->skippedCount,
            'errors' => $this->errors,
            'execution_time' => $executionTime,
            'memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
            'performance' => [
                'records_per_second' => round($this->importedCount / $executionTime, 2),
                'average_time_per_record' => round($executionTime / max($this->importedCount, 1), 4) . ' seconds'
            ]
        ];
    }

    /**
     * Clean up after import
     */
    public function __destruct()
    {
        // Clear caches
        $this->rombelCache = [];
        $this->emailCache = [];
    }
}