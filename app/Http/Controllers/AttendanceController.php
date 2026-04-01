<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AttendanceSummary;
use App\Models\Branch;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AttendanceController extends Controller
{
    public function ajaxDashboardData()
    {
        $filterType = request()->query('filterType', 'monthly');
        $year = (int) request()->query('year', date('Y'));
        $month = (int) request()->query('month', date('m'));

        $chartData = [];
        $lateMinutesData = [];
        $overtimeHoursData = [];
        $chartLabels = [];

        if ($filterType === 'yearly') {
            // Get attendance summaries by month for selected year
            $attendanceData = AttendanceSummary::select(
                DB::raw('MONTH(date) as month'),
                DB::raw('COUNT(*) as total_attendances'),
                DB::raw('COUNT(DISTINCT CASE WHEN late_minutes > 0 THEN employee_id END) as count_late_employees'),
                DB::raw('COUNT(DISTINCT CASE WHEN overtime_hours > 0 THEN employee_id END) as count_overtime_employees')
            )
                ->whereYear('date', $year)
                ->groupBy(DB::raw('MONTH(date)'))
                ->orderBy('month')
                ->get();

            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartLabels = $months;

            foreach (range(1, 12) as $monthNum) {
                $monthData = $attendanceData->firstWhere('month', $monthNum);
                $chartData[] = $monthData ? $monthData->total_attendances : 0;
                $lateMinutesData[] = $monthData ? $monthData->count_late_employees : 0;
                $overtimeHoursData[] = $monthData ? $monthData->count_overtime_employees : 0;
            }
        } else {
            // Get attendance summaries by day for selected month
            $attendanceData = AttendanceSummary::select(
                DB::raw('DAY(date) as day'),
                DB::raw('COUNT(*) as total_attendances'),
                DB::raw('COUNT(DISTINCT CASE WHEN late_minutes > 0 THEN employee_id END) as count_late_employees'),
                DB::raw('COUNT(DISTINCT CASE WHEN overtime_hours > 0 THEN employee_id END) as count_overtime_employees')
            )
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->groupBy(DB::raw('DAY(date)'))
                ->orderBy('day')
                ->get();

            $daysInMonth = (int) date('t', mktime(0, 0, 0, $month, 1, $year));

            foreach (range(1, $daysInMonth) as $day) {
                $dayData = $attendanceData->firstWhere('day', $day);
                $chartLabels[] = $day;
                $chartData[] = $dayData ? $dayData->total_attendances : 0;
                $lateMinutesData[] = $dayData ? $dayData->count_late_employees : 0;
                $overtimeHoursData[] = $dayData ? $dayData->count_overtime_employees : 0;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'chartLabels' => $chartLabels,
                'chartData' => $chartData,
                'lateMinutesData' => $lateMinutesData,
                'overtimeHoursData' => $overtimeHoursData,
            ],
        ]);
    }

    public function exportPdf()
    {
        $employeeId = request()->query('employeeId');
        $startDate = request()->query('startDate');
        $endDate = request()->query('endDate');

        // Validate dates
        // $startDate = Carbon::parse($startDate)->startOfDay();
        // $endDate   = Carbon::parse($endDate)->endOfDay();

        // Get employee
        $employee = Employee::findOrFail($employeeId);

        // Check authorization - user can only export their own data or admin can export anyone's
        if (Auth::user()->role !== 'admin' && Auth::user()->employee?->id !== $employee->id) {
            abort(403, 'Unauthorized');
        }

        // Get attendance summaries for the employee and date range
        $attendanceSummaries = AttendanceSummary::where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        // Prepare PDF data
        $data = [
            'employee' => $employee,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'attendanceSummaries' => $attendanceSummaries,
            'totalWorkHours' => $attendanceSummaries->sum('work_hours'),
            'totalLateMinutes' => $attendanceSummaries->sum('late_minutes'),
            'totalOvertimeHours' => $attendanceSummaries->sum('overtime_hours'),
            'totalAttendances' => $attendanceSummaries->sum('total_attendances'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('attendance.summary-pdf', $data);

        $filename = "rekap-absensi-{$employee->nip}-{$startDate}-{$endDate}.pdf";

        return $pdf->stream($filename);
        // return $pdf->download($filename);
    }

    public function exportExcelBranch()
    {
        $branchId = request()->query('branchId');
        $startDate = request()->query('startDate');
        $endDate = request()->query('endDate');

        // Check authorization - only admin
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized');
        }

        // Get branch
        $branch = Branch::findOrFail($branchId);

        // Get all employees in branch
        $employees = Employee::where('branch_id', $branchId)
            ->orderBy('name')
            ->get();

        // Get attendance summaries grouped by employee
        $attendanceSummaries = AttendanceSummary::whereHas('employee', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('employee_id')
            ->orderBy('date')
            ->get()
            ->groupBy('employee_id');

        // Create Excel workbook
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0); // Remove default sheet

        // Create sheet for each employee
        foreach ($employees as $employee) {
            $summaries = $attendanceSummaries->get($employee->id, collect());

            $sheet = new Worksheet($spreadsheet, "EP-{$employee->nip}");
            $spreadsheet->addSheet($sheet);

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(12);
            $sheet->getColumnDimension('C')->setWidth(12);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(12);
            $sheet->getColumnDimension('F')->setWidth(12);
            $sheet->getColumnDimension('G')->setWidth(12);
            $sheet->getColumnDimension('H')->setWidth(15);
            $sheet->getColumnDimension('I')->setWidth(15);
            $sheet->getColumnDimension('J')->setWidth(15);

            // Header
            $row = 1;
            $sheet->setCellValue('A'.$row, 'REKAP ABSENSI KARYAWAN');
            $sheet->mergeCells("A{$row}:J{$row}");
            $sheet->getStyle("A{$row}:J{$row}")->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle("A{$row}:J{$row}")->getAlignment()->setHorizontal('center');

            // Employee Info
            $row = 3;
            $sheet->setCellValue('A'.$row, 'NIP');
            $sheet->setCellValue('B'.$row, $employee->nip);
            $row++;
            $sheet->setCellValue('A'.$row, 'Nama');
            $sheet->setCellValue('B'.$row, $employee->name);
            $row++;
            $sheet->setCellValue('A'.$row, 'Cabang');
            $sheet->setCellValue('B'.$row, $employee->branch->name ?? '-');
            $row++;
            $sheet->setCellValue('A'.$row, 'Posisi');
            $sheet->setCellValue('B'.$row, $employee->position ?? '-');
            $row++;
            $sheet->setCellValue('A'.$row, 'Periode');
            $sheet->setCellValue('B'.$row, "{$startDate} - {$endDate}");

            // Table header
            $row = 9;
            $headers = ['No', 'Tanggal', 'Shift', 'Jam Masuk', 'Jam Pulang', 'Total Jam', 'Terlambat (min)', 'Lembur (jam)', 'Frekuensi', 'Keterangan'];
            foreach ($headers as $col => $header) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1).$row, $header);
            }

            // Style header
            $sheet->getStyle("A{$row}:J{$row}")
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FF2C3E50');
            $sheet->getStyle("A{$row}:J{$row}")->getFont()->setColor(new Color('FFFFFFFF'))->setBold(true);
            $sheet->getStyle("A{$row}:J{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Data rows
            $row = 10;
            $no = 1;
            foreach ($summaries as $summary) {
                $sheet->setCellValue('A'.$row, $no);
                $sheet->setCellValue('B'.$row, $summary->date);
                $sheet->setCellValue('C'.$row, $summary->shift_name ?? '-');
                $sheet->setCellValue('D'.$row, $summary->clock_in ?? '-');
                $sheet->setCellValue('E'.$row, $summary->clock_out ?? '-');
                $sheet->setCellValue('F'.$row, $summary->work_hours ?? 0);
                $sheet->setCellValue('G'.$row, $summary->late_minutes ?? 0);
                $sheet->setCellValue('H'.$row, $summary->overtime_hours ?? 0);
                $sheet->setCellValue('I'.$row, $summary->total_attendances ?? 0);
                $sheet->setCellValue('J'.$row, '-');

                $no++;
                $row++;
            }

            // Summary section
            $row += 1;
            $sheet->setCellValue('A'.$row, 'RINGKASAN');
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true)->setSize(11);

            $row++;
            $sheet->setCellValue('A'.$row, 'Total Hari Kerja');
            $sheet->setCellValue('B'.$row, $summaries->count());

            $row++;
            $sheet->setCellValue('A'.$row, 'Total Jam Kerja');
            $sheet->setCellValue('B'.$row, round($summaries->sum('work_hours'), 2));

            $row++;
            $sheet->setCellValue('A'.$row, 'Total Keterlambatan (menit)');
            $sheet->setCellValue('B'.$row, $summaries->sum('late_minutes'));

            $row++;
            $sheet->setCellValue('A'.$row, 'Total Lembur (jam)');
            $sheet->setCellValue('B'.$row, round($summaries->sum('overtime_hours'), 2));

            $row++;
            $sheet->setCellValue('A'.$row, 'Total Frekuensi');
            $sheet->setCellValue('B'.$row, $summaries->sum('total_attendances'));
        }

        // Generate filename
        $filename = "rekap-absensi-{$branch->name}-{$startDate}-{$endDate}.xlsx";
        $filename = str_replace(' ', '-', $filename);

        // Output Excel
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
