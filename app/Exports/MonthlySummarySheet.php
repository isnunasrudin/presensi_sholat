<?php

namespace App\Exports;

use App\Models\RombonganBelajar;
use App\Models\User;
use App\Models\PrayerRecord;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class MonthlySummarySheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $year;
    protected $month;
    protected $prayerTypes;

    public function __construct(int $year, int $month, array $prayerTypes)
    {
        $this->year = $year;
        $this->month = $month;
        $this->prayerTypes = $prayerTypes;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        $data = [];
        $daysInMonth = Carbon::create($this->year, $this->month, 1)->daysInMonth;

        // Get all rombongan belajar
        $rombonganBelajar = RombonganBelajar::with('users')->get();

        // Overall statistics
        $totalStudents = 0;
        $totalSholatAll = 0;
        $totalHalanganAll = 0;
        $totalTidakSholatAll = 0;

        foreach ($rombonganBelajar as $rombel) {
            $users = User::where('rombongan_belajar_id', $rombel->id)
                ->where('role', 'user')
                ->get();

            if ($users->count() === 0) continue;

            $classTotalStudents = $users->count();
            $classTotalSholat = 0;
            $classTotalHalangan = 0;
            $classTotalTidakSholat = 0;
            $classTotalPossible = $daysInMonth * 2; // 2 prayer types per day

            foreach ($users as $user) {
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = Carbon::create($this->year, $this->month, $day);

                    foreach ($this->prayerTypes as $prayerType) {
                        $record = PrayerRecord::where('user_id', $user->id)
                            ->where('prayer_type', $prayerType)
                            ->whereDate('date', $date)
                            ->first();

                        if ($record) {
                            if (in_array($record->status, ['sholat', 'hadir'])) {
                                $classTotalSholat++;
                            } elseif ($record->status === 'halangan') {
                                $classTotalHalangan++;
                            } else {
                                $classTotalTidakSholat++;
                            }
                        } else {
                            $classTotalTidakSholat++;
                        }
                    }
                }
            }

            $percentage = $classTotalPossible > 0 ? round(($classTotalSholat / $classTotalPossible) * 100, 1) : 0;

            $data[] = [
                $rombel->nama_rombel,
                $classTotalStudents,
                $classTotalSholat,
                $classTotalHalangan,
                $classTotalTidakSholat,
                $classTotalPossible,
                $percentage . '%'
            ];

            $totalStudents += $classTotalStudents;
            $totalSholatAll += $classTotalSholat;
            $totalHalanganAll += $classTotalHalangan;
            $totalTidakSholatAll += $classTotalTidakSholat;
        }

        // Add total row
        $totalPossibleAll = $totalStudents * $daysInMonth * 2;
        $totalPercentage = $totalPossibleAll > 0 ? round(($totalSholatAll / $totalPossibleAll) * 100, 1) : 0;
        $data[] = [
            'TOTAL SEMUA KELAS',
            $totalStudents,
            $totalSholatAll,
            $totalHalanganAll,
            $totalTidakSholatAll,
            $totalPossibleAll,
            $totalPercentage . '%'
        ];

        // Add prayer type breakdown
        $data[] = []; // Empty row
        $data[] = ['REKAP BERDASARKAN JENIS SHOLAT DHUHUR & DHUHA'];
        $data[] = ['Jenis Sholat', 'Total Sholat', 'Total Halangan', 'Tidak Sholat', 'Total Possible', 'Persentase'];

        foreach ($this->prayerTypes as $prayerType) {
            $prayerSholat = 0;
            $prayerHalangan = 0;
            $prayerTidakSholat = 0;
            $prayerPossible = 0;

            foreach ($rombonganBelajar as $rombel) {
                $users = User::where('rombongan_belajar_id', $rombel->id)
                    ->where('role', 'user')
                    ->get();

                foreach ($users as $user) {
                    for ($day = 1; $day <= $daysInMonth; $day++) {
                        $date = Carbon::create($this->year, $this->month, $day);
                        $record = PrayerRecord::where('user_id', $user->id)
                            ->where('prayer_type', $prayerType)
                            ->whereDate('date', $date)
                            ->first();

                        if ($record) {
                            if (in_array($record->status, ['sholat', 'hadir'])) {
                                $prayerSholat++;
                            } elseif ($record->status === 'halangan') {
                                $prayerHalangan++;
                            } else {
                                $prayerTidakSholat++;
                            }
                        } else {
                            $prayerTidakSholat++;
                        }
                        $prayerPossible++;
                    }
                }
            }

            $prayerPercentage = $prayerPossible > 0 ? round(($prayerSholat / $prayerPossible) * 100, 1) : 0;

            $prayerTypeNames = [
                'dhuhur' => 'Sholat Dhuhur',
                'dhuha' => 'Sholat Dhuha'
            ];

            $data[] = [
                $prayerTypeNames[$prayerType] ?? $prayerType,
                $prayerSholat,
                $prayerHalangan,
                $prayerTidakSholat,
                $prayerPossible,
                $prayerPercentage . '%'
            ];
        }

        return $data;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        $monthName = Carbon::create($this->year, $this->month, 1)->format('F Y');
        return 'RINGKASAN - ' . $monthName;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Nama Kelas',
            'Jumlah Siswa',
            'Total Sholat',
            'Total Halangan',
            'Tidak Sholat',
            'Total Possible',
            'Persentase Kehadiran'
        ];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25, // Nama Kelas
            'B' => 15, // Jumlah Siswa
            'C' => 15, // Total Sholat
            'D' => 15, // Total Halangan
            'E' => 18, // Tidak Ada Keterangan
            'F' => 15, // Total Possible
            'G' => 20, // Persentase
        ];
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();

        // Style for main header
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Find the prayer type breakdown section
        $prayerBreakdownRow = null;
        for ($row = 1; $row <= $highestRow; $row++) {
            if ($sheet->getCell('A' . $row)->getValue() === 'REKAP BERDASARKAN JENIS SHOLAT DHUHUR & DHUHA') {
                $prayerBreakdownRow = $row;
                break;
            }
        }

        if ($prayerBreakdownRow) {
            // Style for prayer type breakdown header
            $sheet->getStyle('A' . ($prayerBreakdownRow + 1) . ':G' . ($prayerBreakdownRow + 1))->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '70AD47'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            // Style for prayer type breakdown title
            $sheet->getStyle('A' . $prayerBreakdownRow)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'size' => 14,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2F2F2'],
                ],
            ]);
        }

        // Style for total row (find it)
        for ($row = 1; $row <= $highestRow; $row++) {
            if ($sheet->getCell('A' . $row)->getValue() === 'TOTAL SEMUA KELAS') {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'C00000'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                break;
            }
        }

        // Borders for all data
        $sheet->getStyle('A1:G' . $highestRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Center align numeric columns
        $sheet->getStyle('B:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set row height
        $sheet->getDefaultRowDimension()->setRowHeight(20);
    }
}