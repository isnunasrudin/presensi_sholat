<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\UsersImportOptimized;
use App\Models\RombonganBelajar;
use App\Models\UserImport;
use App\Jobs\ImportUsersJob;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class UserImportController extends Controller
{
    /**
     * Import users from Excel/CSV file.
     */
    public function import(Request $request)
    {
        // Only admin can import users
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
                'success' => false,
            ], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:20480', // Max 20MB
            'use_queue' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal',
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            return $this->handleQueueImport($file, $request->user());

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi data gagal',
                'success' => false,
                'errors' => $e->failures(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memproses file',
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

  
    /**
     * Handle queue import (asynchronous)
     */
    private function handleQueueImport($file, $user)
    {
        // Store file temporarily
        $fileName = 'user_import_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $filePath = $file->storeAs('imports', $fileName, 'local');

        // Count total rows for progress tracking
        $totalRows = $this->countFileRows($file);

        // Create import record
        $userImport = UserImport::create([
            'admin_user_id' => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'total_rows' => $totalRows,
            'status' => 'pending',
        ]);

        // Dispatch job
        ImportUsersJob::dispatch($userImport, $filePath, $user->id);

        return response()->json([
            'message' => 'Import users dimulai (background)',
            'success' => true,
            'data' => [
                'import_id' => $userImport->id,
                'file_name' => $file->getClientOriginalName(),
                'total_rows' => $totalRows,
                'file_size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                'estimated_time' => $this->estimateTime($totalRows),
                'status' => 'pending',
                'message' => 'File sedang diproses di background. Anda dapat memantau progress menggunakan ID import: ' . $userImport->id,
            ],
        ]);
    }

    /**
     * Count file rows (approximate)
     */
    private function countFileRows($file)
    {
        $filePath = $file->getRealPath();
        $handle = fopen($filePath, 'r');
        $count = 0;

        while (!feof($handle)) {
            if (fgets($handle) !== false) {
                $count++;
            }
        }

        fclose($handle);
        return max(0, $count - 1); // Subtract header row
    }

    /**
     * Estimate processing time
     */
    private function estimateTime($totalRows)
    {
        // Rough estimation: 100 records per second
        $seconds = $totalRows / 100;

        if ($seconds < 60) {
            return round($seconds) . ' detik';
        }

        return round($seconds / 60, 1) . ' menit';
    }

  
  
    /**
     * Download Excel template for user import.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="template_import_users.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for proper Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");

            // Header - Required fields marked with *
            $columns = [
                'name*', // Required
                'role*', // Required (admin/user)
                'email', // Optional - will be generated if empty
                'nisn', // Optional
                'gender', // Optional (L/P)
                'tahun_angkatan*', // Required for rombel creation
                'nama_rombel*', // Required for rombel creation
                'tingkat*', // Required for rombel creation
            ];
            fputcsv($file, $columns);

            // Example rows
            fputcsv($file, [
                'Ahmad Rizki',
                'user',
                'ahmad.rizki@snesa.local', // Email optional, will be generated if empty
                '1234567890', // NISN bisa kosong atau diisi
                'L',
                '2024', // Required
                'XII-A', // Required - rombel akan dibuat otomatis jika belum ada
                'XII', // Required
            ]);

            fputcsv($file, [
                'Siti Nurhaliza',
                'user',
                '', // Email kosong, akan digenerate otomatis
                '', // NISN kosong, tidak error
                'P',
                '2024', // Required
                'XI-B', // Required - rombel akan dibuat otomatis jika belum ada
                'XI', // Required
            ]);

            fputcsv($file, [
                'Admin User',
                'admin',
                'admin@snesa.local',
                '', // NISN kosong, tidak error
                '', // Gender kosong
                '2024', // Required
                'X-1', // Required
                'X', // Required
            ]);

            // Add empty row for template
            fputcsv($file, ['', '', '', '', '', '', '', '']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get available rombel names for template reference
     */
    public function getAvailableRombel()
    {
        try {
            $rombels = RombonganBelajar::select('nama_rombel', 'tingkat', 'tahun_angkatan')
                ->orderBy('tahun_angkatan', 'desc')
                ->orderBy('tingkat', 'asc')
                ->orderBy('nama_rombel', 'asc')
                ->get()
                ->map(function($rombel) {
                    return "{$rombel->nama_rombel} ({$rombel->tingkat} - {$rombel->tahun_angkatan})";
                })
                ->toArray();

            return response()->json([
                'message' => 'Data rombel berhasil diambil',
                'success' => true,
                'data' => [
                    'available_rombel' => $rombels,
                    'total' => count($rombels),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data rombel',
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}