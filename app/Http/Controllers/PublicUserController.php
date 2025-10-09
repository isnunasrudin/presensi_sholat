<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PrayerRecord;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PublicUserController extends Controller
{
    /**
     * Display the public user profile with prayer records.
     */
    public function show(Request $request, User $user)
    {
        // Get the period for filtering (default to current month)
        $period = $request->get('period', 'month');
        $date = $request->get('date', now()->format('Y-m-d'));

        // Calculate date range based on period
        switch ($period) {
            case 'week':
                $startDate = Carbon::parse($date)->startOfWeek();
                $endDate = Carbon::parse($date)->endOfWeek();
                break;
            case 'month':
                $startDate = Carbon::parse($date)->startOfMonth();
                $endDate = Carbon::parse($date)->endOfMonth();
                break;
            case 'year':
                $startDate = Carbon::parse($date)->startOfYear();
                $endDate = Carbon::parse($date)->endOfYear();
                break;
            default:
                $startDate = Carbon::parse($date)->startOfMonth();
                $endDate = Carbon::parse($date)->endOfMonth();
        }

        // Get prayer records for the specified period
        $prayerRecords = PrayerRecord::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy('prayer_type');

        // Calculate statistics
        $totalPrayers = 0;
        $presentPrayers = 0;
        $statistics = [];

        $prayerTypes = ['dhuhur', 'dhuha', 'ashar', 'maghrib', 'isya', 'subuh'];

        foreach ($prayerTypes as $type) {
            $typeRecords = $prayerRecords->get($type, collect());
            $total = $typeRecords->count();
            $present = $typeRecords->where('status', 'sholat')->count();

            $statistics[$type] = [
                'total' => $total,
                'present' => $present,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                'records' => $typeRecords->take(7) // Last 7 records
            ];

            $totalPrayers += $total;
            $presentPrayers += $present;
        }

        $overallPercentage = $totalPrayers > 0 ? round(($presentPrayers / $totalPrayers) * 100, 1) : 0;

        // Recent activities (last 30 days)
        $recentActivities = PrayerRecord::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return view('public.user-profile', compact(
            'user',
            'statistics',
            'overallPercentage',
            'recentActivities',
            'period',
            'date',
            'startDate',
            'endDate'
        ));
    }
}