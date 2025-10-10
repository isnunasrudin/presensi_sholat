<?php

namespace App\Imports;

use App\Models\User;
use App\Models\RombonganBelajar;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsersImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errors = [];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip if required fields are empty
        if (empty($row['name']) || empty($row['role'])) {
            $this->skippedCount++;
            $this->errors[] = [
                'row' => $this->importedCount + $this->skippedCount,
                'data' => $row,
                'errors' => ['name atau role kosong'],
            ];
            return null;
        }

        try {
            // Handle rombongan_belajar if provided
            $rombonganBelajarId = null;
            if (!empty($row['rombongan_belajar'])) {
                $rombel = RombonganBelajar::where('nama_rombel', $row['rombongan_belajar'])->first();
                if ($rombel) {
                    $rombonganBelajarId = $rombel->id;
                }
            }

            // Generate email if not provided
            $email = $row['email'] ?? null;
            if (!$email && !empty($row['name'])) {
                // Create email from name or use placeholder
                $name = $row['name'];
                $email = Str::slug($name) . '@snesa.local';

                // Make email unique
                $originalEmail = $email;
                $counter = 1;
                while (User::where('email', $email)->exists()) {
                    $email = Str::slug($name) . $counter . '@snesa.local';
                    $counter++;
                }
            }

            return new User([
                'name' => $row['name'],
                'email' => $email,
                'password' => Hash::make('password'), // Default password, can be changed later
                'role' => $row['role'],
                'nisn' => $row['nisn'] ?? null,
                'gender' => $row['gender'] ?? null,
                'rombongan_belajar_id' => $rombonganBelajarId,
                'tahun_angkatan' => $row['tahun_angkatan'] ?? null,
                'email_verified_at' => now(),
            ]);
        } catch (\Exception $e) {
            $this->skippedCount++;
            $this->errors[] = [
                'row' => $this->importedCount + $this->skippedCount,
                'data' => $row,
                'errors' => ['processing_error' => $e->getMessage()],
            ];
            return null;
        }
    }

    /**
     * Validation rules
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'role' => ['required', Rule::in(['admin', 'user'])],
            'email' => 'nullable|email|unique:users,email',
            'nisn' => 'nullable|max:20|unique:users,nisn',
            'gender' => 'nullable|in:L,P',
            'tahun_angkatan' => 'nullable|max:10',
            'rombongan_belajar' => 'nullable|max:255',
        ];
    }

    /**
     * Handle validation failures
     */
    public function onFailure(\Throwable $e)
    {
        $this->skippedCount++;
        $this->errors[] = [
            'row' => $this->importedCount + $this->skippedCount,
            'data' => [],
            'errors' => ['validation_error' => $e->getMessage()],
        ];
    }

    /**
     * Handle successful import
     */
    public function onSuccess()
    {
        $this->importedCount++;
    }

    /**
     * Get import results
     *
     * @return array
     */
    public function getResults()
    {
        return [
            'imported_count' => $this->importedCount,
            'skipped_count' => $this->skippedCount,
            'errors' => $this->errors,
        ];
    }

    /**
     * Custom validation messages
     *
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'name.required' => 'Nama wajib diisi',
            'role.required' => 'Role wajib diisi (admin/user)',
            'role.in' => 'Role harus berupa admin atau user',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email sudah terdaftar',
            'nisn.max' => 'NISN maksimal 20 karakter',
            'nisn.unique' => 'NISN sudah terdaftar',
            'gender.in' => 'Gender harus L atau P',
        ];
    }

    /**
     * Batch size
     *
     * @return int
     */
    public function batchSize(): int
    {
        return 1000;
    }

    /**
     * Chunk size
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}