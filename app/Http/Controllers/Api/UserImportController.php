<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\UsersImportOptimized;
use App\Models\RombonganBelajar;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Response;

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
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240', // Max 10MB
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

            // Import the file
            $import = new UsersImportOptimized();
            Excel::import($import, $file);

            // Get import results from the import class
            $results = $import->getResults();

            return response()->json([
                'message' => 'Import users berhasil',
                'success' => true,
                'data' => [
                    'imported_count' => $results['imported_count'] ?? 0,
                    'skipped_count' => $results['skipped_count'] ?? 0,
                    'errors' => $results['errors'] ?? [],
                ],
            ]);

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