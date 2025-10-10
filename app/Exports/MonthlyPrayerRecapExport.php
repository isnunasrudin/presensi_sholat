<?php

namespace App\Exports;

use App\Models\RombonganBelajar;
use App\Models\User;
use App\Models\PrayerRecord;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Support\Collection;

class MonthlyPrayerRecapExport implements WithMultipleSheets
{
    use Exportable;

    protected $year;
    protected $month;
    protected $prayerTypes;

    public function __construct(int $year, int $month)
    {
        $this->year = $year;
        $this->month = $month;
        $this->prayerTypes = ['dhuhur', 'dhuha'];
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

        // Get all rombongan belajar (kelas)
        $rombonganBelajar = RombonganBelajar::with('users')->get();

        // Create sheet for each kelas
        foreach ($rombonganBelajar as $rombel) {
            if ($rombel->users->count() > 0) {
                $sheets[] = new PrayerRecordPerClassSheet(
                    $rombel,
                    $this->year,
                    $this->month,
                    $this->prayerTypes
                );
            }
        }

        // Add summary sheet if there are any classes
        if (count($sheets) > 0) {
            $sheets[] = new MonthlySummarySheet($this->year, $this->month, $this->prayerTypes);
        }

        return $sheets;
    }
}