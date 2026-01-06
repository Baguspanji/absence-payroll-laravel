<?php

declare (strict_types = 1);

namespace App\Http\Controllers;

use App\Models\AttendanceSummary;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function ajaxDashboardData()
    {
        $filterType = request()->query('filterType', 'monthly');
        $year       = (int) request()->query('year', date('Y'));
        $month      = (int) request()->query('month', date('m'));

        $chartData         = [];
        $lateMinutesData   = [];
        $overtimeHoursData = [];
        $chartLabels       = [];

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

            $months      = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartLabels = $months;

            foreach (range(1, 12) as $monthNum) {
                $monthData           = $attendanceData->firstWhere('month', $monthNum);
                $chartData[]         = $monthData ? $monthData->total_attendances : 0;
                $lateMinutesData[]   = $monthData ? $monthData->count_late_employees : 0;
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
                $dayData             = $attendanceData->firstWhere('day', $day);
                $chartLabels[]       = $day;
                $chartData[]         = $dayData ? $dayData->total_attendances : 0;
                $lateMinutesData[]   = $dayData ? $dayData->count_late_employees : 0;
                $overtimeHoursData[] = $dayData ? $dayData->count_overtime_employees : 0;
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'chartLabels'       => $chartLabels,
                'chartData'         => $chartData,
                'lateMinutesData'   => $lateMinutesData,
                'overtimeHoursData' => $overtimeHoursData,
            ],
        ]);
    }

    public function exportPdf()
    {
        $employeeId = request()->query('employeeId');
        $startDate  = request()->query('startDate');
        $endDate    = request()->query('endDate');

        // Validate dates
        $startDate = Carbon::parse($startDate)->startOfDay();
        $endDate   = Carbon::parse($endDate)->endOfDay();

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
            'employee'            => $employee,
            'startDate'           => $startDate,
            'endDate'             => $endDate,
            'attendanceSummaries' => $attendanceSummaries,
            'totalWorkHours'      => $attendanceSummaries->sum('work_hours'),
            'totalLateMinutes'    => $attendanceSummaries->sum('late_minutes'),
            'totalOvertimeHours'  => $attendanceSummaries->sum('overtime_hours'),
            'totalAttendances'    => $attendanceSummaries->sum('total_attendances'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('attendance.summary-pdf', $data);

        $filename = "rekap-absensi-{$employee->nip}-{$startDate->format('Y-m-d')}-{$endDate->format('Y-m-d')}.pdf";

        return $pdf->download($filename);
    }
}
