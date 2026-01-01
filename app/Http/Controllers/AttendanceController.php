<?php

declare (strict_types = 1);

namespace App\Http\Controllers;

use App\Models\AttendanceSummary;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
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
