<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RombonganBelajar;
use Illuminate\Http\Request;

class RombonganBelajarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Only admin can list rombongan belajar
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $rombonganBelajar = RombonganBelajar::withCount('users')->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($rombonganBelajar);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Only admin can create rombongan belajar
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $request->validate([
            'nama_rombel' => 'required|string|max:255',
            'tingkat' => 'required|string|max:255',
            'tahun_angkatan' => 'required|string|max:255',
        ]);

        $rombonganBelajar = RombonganBelajar::create($request->all());

        return response()->json([
            'message' => 'Rombongan Belajar created successfully',
            'data' => $rombonganBelajar,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        // Only admin can view rombongan belajar
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $rombonganBelajar = RombonganBelajar::with(['users' => function($query) {
            $query->select('id', 'name', 'email', 'role', 'rombongan_belajar_id');
        }])->findOrFail($id);

        return response()->json($rombonganBelajar);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Only admin can update rombongan belajar
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $rombonganBelajar = RombonganBelajar::findOrFail($id);

        $request->validate([
            'nama_rombel' => 'sometimes|required|string|max:255',
            'tingkat' => 'sometimes|required|string|max:255',
            'tahun_angkatan' => 'sometimes|required|string|max:255',
        ]);

        $rombonganBelajar->update($request->all());

        return response()->json([
            'message' => 'Rombongan Belajar updated successfully',
            'data' => $rombonganBelajar,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        // Only admin can delete rombongan belajar
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $rombonganBelajar = RombonganBelajar::findOrFail($id);

        // Check if there are users in this rombongan belajar
        if ($rombonganBelajar->users()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete rombongan belajar with existing users',
            ], 400);
        }

        $rombonganBelajar->delete();

        return response()->json([
            'message' => 'Rombongan Belajar deleted successfully',
        ]);
    }

    /**
     * Get all available tahun angkatan from rombongan belajar.
     */
    public function getTahunAngkatan(Request $request)
    {
        // Only admin can access this
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $tahunAngkatan = RombonganBelajar::select('tahun_angkatan')
            ->distinct()
            ->orderBy('tahun_angkatan', 'desc')
            ->pluck('tahun_angkatan');

        return response()->json($tahunAngkatan);
    }

    /**
     * Promote all rombongan belajar from a specific tahun angkatan to the next tingkat.
     */
    public function promote(Request $request, string $tahunAngkatan)
    {
        // Only admin can promote rombongan belajar
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Define tingkat progression mapping
        $tingkatMap = [
            // SMA
            'X' => 'XI',
            'XI' => 'XII',
            '10' => '11',
            '11' => '12',
            // SMP - Angka
            '7' => '8',
            '8' => '9',
            // SMP - Romawi
            'VII' => 'VIII',
            'VIII' => 'IX',
            // SD - Angka
            '1' => '2',
            '2' => '3',
            '3' => '4',
            '4' => '5',
            '5' => '6',
            '6' => '7',
            // SD - Romawi
            'I' => 'II',
            'II' => 'III',
            'III' => 'IV',
            'IV' => 'V',
            'V' => 'VI',
            'VI' => 'VII',
        ];

        // Get all rombongan belajar with the specified tahun angkatan
        $rombonganBelajarList = RombonganBelajar::where('tahun_angkatan', $tahunAngkatan)->get();

        if ($rombonganBelajarList->isEmpty()) {
            return response()->json([
                'message' => 'No rombongan belajar found with tahun angkatan ' . $tahunAngkatan,
            ], 404);
        }

        // Update tingkat for all rombongan belajar
        $updatedCount = 0;
        $promotionDetails = [];
        
        foreach ($rombonganBelajarList as $rombonganBelajar) {
            $currentTingkat = $rombonganBelajar->tingkat;
            
            // Check if tingkat can be promoted
            if (isset($tingkatMap[$currentTingkat])) {
                $nextTingkat = $tingkatMap[$currentTingkat];
                $rombonganBelajar->tingkat = $nextTingkat;
                $rombonganBelajar->save();
                
                $promotionDetails[] = [
                    'nama_rombel' => $rombonganBelajar->nama_rombel,
                    'from' => $currentTingkat,
                    'to' => $nextTingkat,
                ];
                
                $updatedCount++;
            }
        }

        return response()->json([
            'message' => "Successfully promoted {$updatedCount} rombongan belajar from tahun angkatan {$tahunAngkatan}",
            'promoted_count' => $updatedCount,
            'tahun_angkatan' => $tahunAngkatan,
            'details' => $promotionDetails,
        ]);
    }

    /**
     * Graduate all rombongan belajar of a specific tingkat.
     * This will set all students' rombongan_belajar_id to null and delete the rombongan belajar.
     */
    public function graduate(Request $request, string $tingkat)
    {
        // Only admin can graduate rombongan belajar
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Get all rombongan belajar with the specified tingkat
        $rombonganBelajarList = RombonganBelajar::where('tingkat', $tingkat)
            ->withCount('users')
            ->get();

        if ($rombonganBelajarList->isEmpty()) {
            return response()->json([
                'message' => 'No rombongan belajar found with tingkat ' . $tingkat,
            ], 404);
        }

        $graduatedStudentsCount = 0;
        $deletedRombonganCount = 0;

        // Process each rombongan belajar
        foreach ($rombonganBelajarList as $rombonganBelajar) {
            // Set all students' rombongan_belajar_id to null (they are graduated)
            $studentsCount = $rombonganBelajar->users()->count();
            $rombonganBelajar->users()->update(['rombongan_belajar_id' => null]);
            $graduatedStudentsCount += $studentsCount;

            // Delete the rombongan belajar
            $rombonganBelajar->delete();
            $deletedRombonganCount++;
        }

        return response()->json([
            'message' => "Successfully graduated {$graduatedStudentsCount} students from {$deletedRombonganCount} rombongan belajar of tingkat {$tingkat}",
            'graduated_students_count' => $graduatedStudentsCount,
            'deleted_rombongan_count' => $deletedRombonganCount,
            'tingkat' => $tingkat,
        ]);
    }
}
