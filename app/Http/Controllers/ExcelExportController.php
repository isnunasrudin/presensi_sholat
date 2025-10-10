<?php

namespace App\Http\Controllers;

use App\Exports\MonthlyPrayerRecapExport;
use App\Models\RombonganBelajar;
use App\Models\User;
use App\Models\PrayerRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelExportController extends Controller
{
    /**
     * Display the export form
     */
    public function index()
    {
        $rombonganBelajar = RombonganBelajar::all();

        return view('exports.index', compact('rombonganBelajar'));
    }

    /**
     * Export monthly prayer recap to Excel
     */
    public function exportMonthlyRecap(Request $request): BinaryFileResponse
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $month = $request->input('month');
        $year = $request->input('year');

        // Validate if there's any data for the selected month
        $hasData = $this->hasPrayerDataForMonth($year, $month);

        if (!$hasData) {
            return redirect()->back()
                ->with('error', 'Tidak ada data sholat untuk bulan ' . Carbon::create($year, $month, 1)->format('F Y'));
        }

        $monthName = Carbon::create($year, $month, 1)->format('F-Y');
        $fileName = "Rekap-Sholat-{$monthName}.xlsx";

        return Excel::download(
            new MonthlyPrayerRecapExport($year, $month),
            $fileName
        );
    }

    /**
     * Export specific class data to Excel
     */
    public function exportClassData(Request $request, $rombelId)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $rombel = RombonganBelajar::findOrFail($rombelId);
        $month = $request->input('month');
        $year = $request->input('year');

        // Validate if there's any data for the selected class and month
        $hasData = $this->hasPrayerDataForClassAndMonth($rombelId, $year, $month);

        if (!$hasData) {
            return redirect()->back()
                ->with('error', 'Tidak ada data sholat untuk kelas ' . $rombel->nama_rombel . ' pada bulan ' . Carbon::create($year, $month, 1)->format('F Y'));
        }

        $monthName = Carbon::create($year, $month, 1)->format('F-Y');
        $fileName = "Rekap-{$rombel->nama_rombel}-{$monthName}.xlsx";

        // Create single sheet export for specific class
        $export = new MonthlyPrayerRecapExport($year, $month);

        return Excel::download($export, $fileName);
    }

    /**
     * Check if there's any prayer data for the given month
     */
    private function hasPrayerDataForMonth(int $year, int $month): bool
    {
        return PrayerRecord::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->exists();
    }

    /**
     * Check if there's any prayer data for the given class and month
     */
    private function hasPrayerDataForClassAndMonth(int $rombelId, int $year, int $month): bool
    {
        return PrayerRecord::whereHas('user', function ($query) use ($rombelId) {
                $query->where('rombongan_belajar_id', $rombelId);
            })
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->exists();
    }

    /**
     * API endpoint to get available months with data
     */
    public function getAvailableMonths(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $months = PrayerRecord::whereYear('date', $year)
            ->selectRaw('cast(strftime("%m", date) as integer) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->month,
                    'label' => Carbon::create(null, $item->month, 1)->format('F'),
                    'count' => $item->count
                ];
            });

        return response()->json($months);
    }

    /**
     * API endpoint to get statistics for selected month
     */
    public function getMonthStatistics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        $month = $request->input('month');
        $year = $request->input('year');

        // Get statistics
        $totalStudents = User::where('role', 'user')->count();
        $totalRecords = PrayerRecord::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->count();

        $totalPresent = PrayerRecord::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('status', 'hadir')
            ->count();

        $classesWithData = RombonganBelajar::whereHas('users.prayerRecords', function ($query) use ($year, $month) {
            $query->whereYear('date', $year)->whereMonth('date', $month);
        })->count();

        return response()->json([
            'total_students' => $totalStudents,
            'total_records' => $totalRecords,
            'total_present' => $totalPresent,
            'attendance_rate' => $totalRecords > 0 ? round(($totalPresent / $totalRecords) * 100, 1) : 0,
            'classes_with_data' => $classesWithData,
            'month_name' => Carbon::create($year, $month, 1)->format('F Y')
        ]);
    }
}