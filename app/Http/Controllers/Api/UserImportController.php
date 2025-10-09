<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RombonganBelajar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserImportController extends Controller
{
    /**
     * Import users from CSV file.
     */
    public function import(Request $request)
    {
        // Only admin can import users
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
            'rombongan_belajar_id' => 'nullable|exists:rombongan_belajar,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $rombonganBelajarId = $request->input('rombongan_belajar_id');

            // Open and read CSV file
            $handle = fopen($file->getRealPath(), 'r');
            
            // Skip header row
            $header = fgetcsv($handle);
            
            // Validate header - support both old and new format
            $requiredHeaders = ['name', 'email', 'phone', 'password', 'role', 'nisn'];
            $optionalHeaders = ['gender', 'nama_rombel', 'tingkat', 'tahun_angkatan'];
            $headerLower = array_map('strtolower', array_map('trim', $header));
            
            $missingHeaders = array_diff($requiredHeaders, $headerLower);
            if (!empty($missingHeaders)) {
                fclose($handle);
                return response()->json([
                    'message' => 'Invalid CSV format. Missing required columns: ' . implode(', ', $missingHeaders),
                    'expected_headers' => $requiredHeaders,
                    'optional_headers' => $optionalHeaders,
                ], 422);
            }
            
            // Check if rombel columns exist
            $hasRombelColumns = in_array('nama_rombel', $headerLower) && 
                               in_array('tingkat', $headerLower) && 
                               in_array('tahun_angkatan', $headerLower);

            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];
            $rowNumber = 1; // Start from 1 (excluding header)

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                
                // Map CSV columns to array keys
                $userData = array_combine($headerLower, array_map('trim', $row));
                
                // Validate row data
                $rowValidator = Validator::make($userData, [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'phone' => 'nullable|string|max:20',
                    'password' => 'required|string|min:6',
                    'role' => 'required|in:admin,user',
                    'nisn' => 'nullable|string|max:20',
                    'gender' => 'nullable|in:male,female',
                ]);

                if ($rowValidator->fails()) {
                    $skippedCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'data' => $userData,
                        'errors' => $rowValidator->errors()->toArray(),
                    ];
                    continue;
                }

                // Determine rombongan_belajar_id
                $finalRombonganId = $rombonganBelajarId;
                
                // If CSV has rombel columns and no manual rombongan_belajar_id is set
                if ($hasRombelColumns && !$rombonganBelajarId) {
                    $namaRombel = $userData['nama_rombel'] ?? null;
                    $tingkat = $userData['tingkat'] ?? null;
                    $tahunAngkatan = $userData['tahun_angkatan'] ?? null;
                    
                    // Only process if all rombel data is provided
                    if ($namaRombel && $tingkat && $tahunAngkatan) {
                        // Find or create rombongan belajar
                        $rombongan = RombonganBelajar::firstOrCreate(
                            [
                                'nama_rombel' => $namaRombel,
                                'tingkat' => $tingkat,
                                'tahun_angkatan' => $tahunAngkatan,
                            ]
                        );
                        $finalRombonganId = $rombongan->id;
                    }
                }

                // Create user
                try {
                    User::create([
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'phone' => $userData['phone'] ?: null,
                        'password' => Hash::make($userData['password']),
                        'role' => $userData['role'],
                        'nisn' => $userData['nisn'] ?: null,
                        'gender' => $userData['gender'] ?? null,
                        'rombongan_belajar_id' => $finalRombonganId,
                    ]);
                    $importedCount++;
                } catch (\Exception $e) {
                    $skippedCount++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'data' => $userData,
                        'errors' => ['database' => [$e->getMessage()]],
                    ];
                }
            }

            fclose($handle);

            return response()->json([
                'message' => "Import completed. {$importedCount} users imported, {$skippedCount} skipped.",
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download CSV template.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="user_import_template.csv"',
        ];

        $columns = ['name', 'email', 'phone', 'password', 'role', 'nisn', 'gender', 'nama_rombel', 'tingkat', 'tahun_angkatan'];
        
        $callback = function() use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            
            // Add example rows
            fputcsv($file, [
                'John Doe',
                'john.doe@example.com',
                '081234567890',
                'password123',
                'user',
                '1234567890',
                'male',
                'X-1',
                'X',
                '2024'
            ]);
            
            fputcsv($file, [
                'Jane Smith',
                'jane.smith@example.com',
                '081234567891',
                'password123',
                'user',
                '1234567891',
                'female',
                'XI-2',
                'XI',
                '2023'
            ]);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
