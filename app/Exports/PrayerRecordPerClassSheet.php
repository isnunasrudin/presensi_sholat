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

class PrayerRecordPerClassSheet implements FromArray, WithTitle, WithHeadings, WithStyles, WithColumnWidths
{
    protected $rombel;
    protected $year;
    protected $month;
    protected $prayerTypes;

    public function __construct(RombonganBelajar $rombel, int $year, int $month, array $prayerTypes)
    {
        $this->rombel = $rombel;
        $this->year = $year;
        $this->month = $month;
        $this->prayerTypes = $prayerTypes;
    }

    /**
     * Prepare the data for the sheet
     */
    public function array(): array
    {
        $data = [];

        // Get users in this class
        $users = User::where('rombongan_belajar_id', $this->rombel->id)
            ->where('role', 'user')
            ->orderBy('name')
            ->get();

        // Get days in the month
        $daysInMonth = Carbon::create($this->year, $this->month, 1)->daysInMonth;

        // Fixed structure: maximum 10 days to stay within Excel limits
        $maxDays = min($daysInMonth, 10);

        // Add header row with dates - 2 columns per day (dhuhur & dhuha)
        $dateRow = ['Nama Siswa', 'NISN'];
        for ($day = 1; $day <= 10; $day++) { // Always create 10 day columns
            if ($day <= $maxDays) {
                $dateRow[] = $day;
                $dateRow[] = ''; // Empty cell for visual separation
            } else {
                $dateRow[] = ''; // Empty for unused days
                $dateRow[] = ''; // Empty for unused days
            }
        }
        // Add summary columns (W, X, Y, Z)
        $dateRow[] = 'Total Sholat';
        $dateRow[] = 'Total Halangan';
        $dateRow[] = 'Tidak Sholat';
        $dateRow[] = 'Persentase';
        $data[] = $dateRow;

        // Add sub-header row with prayer types
        $subHeaderRow = ['', ''];
        for ($day = 1; $day <= 10; $day++) {
            if ($day <= $maxDays) {
                $subHeaderRow[] = 'Dhuhur';
                $subHeaderRow[] = 'Dhuha';
            } else {
                $subHeaderRow[] = ''; // Empty for unused days
                $subHeaderRow[] = ''; // Empty for unused days
            }
        }
        // Add summary column labels
        $subHeaderRow[] = 'Sholat';
        $subHeaderRow[] = 'Halangan';
        $subHeaderRow[] = 'Tidak Sholat';
        $subHeaderRow[] = '%';
        $data[] = $subHeaderRow;

        // Add legend row
        $legendRow = ['', ''];
        for ($day = 1; $day <= 10; $day++) {
            if ($day == 1) {
                $legendRow[] = 'S=Sholat';
                $legendRow[] = 'H=Halangan';
            } else {
                $legendRow[] = '';
                $legendRow[] = '';
            }
        }
        // Fill the rest of summary columns with legend
        $legendRow[] = 'T=Tidak Sholat';
        $legendRow[] = '';
        $legendRow[] = '';
        $legendRow[] = '';
        $data[] = $legendRow;

        // Add note about truncated data if needed
        if ($daysInMonth > $maxDays) {
            $noteRow = array_fill(0, 2, '');
            for ($day = 1; $day <= 10; $day++) {
                $noteRow[] = '';
                $noteRow[] = '';
            }
            $noteRow[] = 'Note: Only first ' . $maxDays . ' days shown';
            $noteRow[] = 'of ' . $daysInMonth . ' total days';
            $noteRow[] = '';
            $noteRow[] = '';
            $data[] = $noteRow;
        }

        // Add data for each student
        foreach ($users as $user) {
            $studentRow = [$user->name, $user->nisn];
            $totalSholat = 0;
            $totalHalangan = 0;
            $totalTidakSholat = 0;
            $totalPossible = $daysInMonth * 2; // 2 prayer types per day

            for ($day = 1; $day <= 10; $day++) { // Always process 10 days for consistent structure
                if ($day <= $maxDays) {
                    $date = Carbon::create($this->year, $this->month, $day);

                    // Get dhuhur status
                    $dhuhurRecord = PrayerRecord::where('user_id', $user->id)
                        ->where('prayer_type', 'dhuhur')
                        ->whereDate('date', $date)
                        ->first();

                    // Get dhuha status
                    $dhuhaRecord = PrayerRecord::where('user_id', $user->id)
                        ->where('prayer_type', 'dhuha')
                        ->whereDate('date', $date)
                        ->first();

                    // Process dhuhur status
                    $dhuhurStatus = $this->getStatusIcon($dhuhurRecord);
                    $studentRow[] = $dhuhurStatus;

                    if ($dhuhurRecord) {
                        if (in_array($dhuhurRecord->status, ['sholat', 'hadir'])) {
                            $totalSholat++;
                        } elseif ($dhuhurRecord->status === 'halangan') {
                            $totalHalangan++;
                        } else {
                            $totalTidakSholat++;
                        }
                    } else {
                        $totalTidakSholat++;
                    }

                    // Process dhuha status
                    $dhuhaStatus = $this->getStatusIcon($dhuhaRecord);
                    $studentRow[] = $dhuhaStatus;

                    if ($dhuhaRecord) {
                        if (in_array($dhuhaRecord->status, ['sholat', 'hadir'])) {
                            $totalSholat++;
                        } elseif ($dhuhaRecord->status === 'halangan') {
                            $totalHalangan++;
                        } else {
                            $totalTidakSholat++;
                        }
                    } else {
                        $totalTidakSholat++;
                    }
                } else {
                    // Add empty cells for days beyond $maxDays
                    $studentRow[] = '';
                    $studentRow[] = '';
                }
            }

            // Add summary columns
            $percentage = $totalPossible > 0 ? round(($totalSholat / $totalPossible) * 100, 1) : 0;
            $studentRow[] = $totalSholat;
            $studentRow[] = $totalHalangan;
            $studentRow[] = $totalTidakSholat;
            $studentRow[] = $percentage . '%';

            $data[] = $studentRow;
        }

        return $data;
    }

    /**
     * Convert prayer status to icon/text
     */
    private function getStatusIcon($record): string
    {
        if (!$record) {
            return 'T'; // Tidak Sholat
        }

        switch ($record->status) {
            case 'sholat':
            case 'hadir':
                return 'S'; // Sholat
            case 'halangan':
                return 'H'; // Halangan
            case 'sakit':
                return 'T'; // Sakit dianggap Tidak Sholat
            default:
                return 'T'; // Tidak Sholat
        }
    }

    /**
     * @return string
     */
    public function title(): string
    {
        $monthName = Carbon::create($this->year, $this->month, 1)->format('F Y');
        return $this->rombel->nama_rombel . ' - ' . $monthName;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        // Headings are handled in array() method
        return [];
    }

    /**
     * @return array
     */
    public function columnWidths(): array
    {
        $widths = [
            'A' => 25, // Nama Siswa
            'B' => 15, // NISN
        ];

        // Fixed structure with maximum 10 days (20 columns + 4 summary = 26 total columns)
        $maxDays = 10;
        $dateColumns = $maxDays * 2; // 2 columns per day

        // Date columns (C to V)
        $startCol = 67; // ASCII for 'C'
        for ($i = 0; $i < $dateColumns; $i++) {
            $colAscii = $startCol + $i;
            if ($colAscii <= 90) { // Up to 'Z'
                $column = chr($colAscii);
                $widths[$column] = 4;
            }
        }

        // Summary columns (W, X, Y, Z)
        $summaryStart = $startCol + $dateColumns;
        if ($summaryStart <= 90) $widths[chr($summaryStart)] = 12;     // Total Sholat (W)
        if ($summaryStart + 1 <= 90) $widths[chr($summaryStart + 1)] = 12; // Total Halangan (X)
        if ($summaryStart + 2 <= 90) $widths[chr($summaryStart + 2)] = 15; // Tidak Ada Keterangan (Y)
        if ($summaryStart + 3 <= 90) $widths[chr($summaryStart + 3)] = 10; // Persentase (Z)

        return $widths;
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Fixed structure: 10 days * 2 columns + 6 columns (2 basic + 4 summary) = 26 columns (A to Z)
        $maxDays = 10;
        $totalColumns = 26; // Fixed to A-Z range
        $lastColumn = 'Z';

        // Style for header row (dates) - Row 1
        $headerRange = 'A1:' . $lastColumn . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
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

        // Style for sub-header row (prayer types) - Row 2
        $subHeaderRange = 'A2:' . $lastColumn . '2';
        $sheet->getStyle($subHeaderRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '70AD47'],
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

        // Style for legend row - Row 3
        $legendRange = 'A3:' . $lastColumn . '3';
        $sheet->getStyle($legendRange)->applyFromArray([
            'font' => [
                'size' => 9,
                'italic' => true,
                'color' => ['rgb' => '666666'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F9F9F9'],
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

        // Center align prayer status columns (C to V)
        $dateColumns = $maxDays * 2; // 20 columns for dates
        $startCol = 67; // ASCII for 'C'
        for ($i = 0; $i < $dateColumns; $i++) {
            $colAscii = $startCol + $i;
            if ($colAscii <= 86) { // Up to 'V' (ASCII 86)
                $column = chr($colAscii);
                $sheet->getStyle($column . '4:' . $column . ($sheet->getHighestRow()))
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        // Style for summary columns (W, X, Y, Z)
        $summaryColumns = ['W', 'X', 'Y', 'Z'];
        $summaryRange = $summaryColumns[0] . '1:' . end($summaryColumns) . ($sheet->getHighestRow());
        $sheet->getStyle($summaryRange)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Add alternating colors for better readability (start from row 4)
        for ($row = 4; $row <= $sheet->getHighestRow(); $row++) {
            if ($row % 2 == 1) { // Odd rows starting from row 4
                $dataRange = 'A' . $row . ':' . $lastColumn . $row;
                $sheet->getStyle($dataRange)->applyFromArray([
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                ]);
            }
        }

        // Borders for all data
        $dataRange = 'A1:' . $lastColumn . $sheet->getHighestRow();
        $sheet->getStyle($dataRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Wrap text for names (start from row 4)
        $sheet->getStyle('A4:A' . $sheet->getHighestRow())->getAlignment()->setWrapText(true);

        // Set row height for better readability
        $sheet->getDefaultRowDimension()->setRowHeight(20);

        // Merge date cells for better visual (span 2 columns per day)
        for ($day = 1; $day <= $maxDays; $day++) {
            $startColAscii = 65 + ($day - 1) * 2 + 2; // C, E, G, ...
            $endColAscii = $startColAscii + 1; // D, F, H, ...

            if ($endColAscii <= 86) { // Up to 'V'
                $startCol = chr($startColAscii);
                $endCol = chr($endColAscii);
                $sheet->mergeCells($startCol . '1:' . $endCol . '1');
            }
        }

        // Merge summary header cells (span 2 rows each)
        $sheet->mergeCells('W1:W2'); // Total Sholat
        $sheet->mergeCells('X1:X2'); // Total Halangan
        $sheet->mergeCells('Y1:Y2'); // Tidak Ada Keterangan
        $sheet->mergeCells('Z1:Z2'); // Persentase
    }
}