<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrayerRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrayerRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PrayerRecord::with('user');

        // Filter by user if not admin
        if (!$request->user()->isAdmin()) {
            $query->where('user_id', $request->user()->id);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        // Filter by prayer type
        if ($request->has('prayer_type')) {
            $query->where('prayer_type', $request->prayer_type);
        }

        // Filter by user_id (admin only)
        if ($request->has('user_id') && $request->user()->isAdmin()) {
            $query->where('user_id', $request->user_id);
        }

        $records = $query->orderBy('date', 'desc')->paginate(15);

        return response()->json($records);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Only admin can create records
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Only admin can create records.',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'prayer_type' => 'required|in:dhuhur,dhuha',
            'date' => 'required|date',
            'status' => 'required|in:sholat,tidak_sholat,halangan',
            'notes' => 'nullable|string',
        ]);

        $record = PrayerRecord::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'prayer_type' => $request->prayer_type,
                'date' => $request->date,
            ],
            [
                'status' => $request->status,
                'notes' => $request->notes,
            ]
        );

        return response()->json([
            'message' => 'Prayer record saved successfully',
            'data' => $record->load('user'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $record = PrayerRecord::with('user')->findOrFail($id);

        // Check if user has access to this record
        if (!$request->user()->isAdmin() && $record->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        return response()->json($record);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Only admin can update records
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Only admin can update records.',
            ], 403);
        }

        $record = PrayerRecord::findOrFail($id);

        $request->validate([
            'status' => 'required|in:sholat,tidak_sholat,halangan',
            'notes' => 'nullable|string',
        ]);

        $record->update([
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Prayer record updated successfully',
            'data' => $record->load('user'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $record = PrayerRecord::findOrFail($id);

        // Only admin can delete records
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        $record->delete();

        return response()->json([
            'message' => 'Prayer record deleted successfully',
        ]);
    }

    /**
     * Get statistics for prayer records
     */
    public function statistics(Request $request)
    {
        $userId = $request->user()->isAdmin() && $request->has('user_id')
            ? $request->user_id
            : $request->user()->id;

        $query = PrayerRecord::where('user_id', $userId);

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }

        $total = $query->count();
        $sholat = (clone $query)->where('status', 'sholat')->count();
        $tidakSholat = (clone $query)->where('status', 'tidak_sholat')->count();
        $halangan = (clone $query)->where('status', 'halangan')->count();

        return response()->json([
            'total' => $total,
            'sholat' => $sholat,
            'tidak_sholat' => $tidakSholat,
            'halangan' => $halangan,
            'percentage_sholat' => $total > 0 ? round(($sholat / $total) * 100, 2) : 0,
        ]);
    }

    /**
     * Get recap per user (Admin only)
     */
    public function userRecap(Request $request)
    {
        // Only admin can view user recap
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Only admin can view user recap.',
            ], 403);
        }

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'prayer_type' => 'nullable|in:dhuhur,dhuha',
        ]);

        $query = PrayerRecord::with('user')
            ->selectRaw('user_id,
                COUNT(*) as total_records,
                SUM(CASE WHEN status = "sholat" THEN 1 ELSE 0 END) as sholat_count,
                SUM(CASE WHEN status = "tidak_sholat" THEN 1 ELSE 0 END) as tidak_sholat_count,
                SUM(CASE WHEN status = "halangan" THEN 1 ELSE 0 END) as halangan_count')
            ->groupBy('user_id');

        if ($request->has('start_date')) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('date', '<=', $request->end_date);
        }
        if ($request->has('prayer_type')) {
            $query->where('prayer_type', $request->prayer_type);
        }

        $recap = $query->get()->map(function ($item) {
            return [
                'user_id' => $item->user_id,
                'user_name' => $item->user->name,
                'user_email' => $item->user->email,
                'total_records' => $item->total_records,
                'sholat_count' => $item->sholat_count,
                'tidak_sholat_count' => $item->tidak_sholat_count,
                'halangan_count' => $item->halangan_count,
                'percentage_sholat' => $item->total_records > 0
                    ? round(($item->sholat_count / $item->total_records) * 100, 2)
                    : 0,
            ];
        });

        return response()->json($recap);
    }
}
