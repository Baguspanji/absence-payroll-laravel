<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceProcessingService
{
    /**
     * Process attendance records for a specific employee and date range
     */
    public function processAttendanceForEmployeeAndDateRange($employeeNip, $startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate)->format('Y-m-d');
        $endDate = Carbon::parse($endDate)->format('Y-m-d');

        // Get raw attendances for the employee and date range
        $rawAttendances = DB::table('attendances')
            ->where('employee_nip', $employeeNip)
            ->where('is_processed', false)
            ->whereBetween(DB::raw('DATE(timestamp)'), [$startDate, $endDate])
            ->orderBy('timestamp', 'asc')
            ->get();

        if ($rawAttendances->isEmpty()) {
            Log::channel('process-attendance')->info("No unprocessed attendances found for NIP {$employeeNip} between {$startDate} and {$endDate}");

            return [
                'success' => true,
                'message' => 'No unprocessed attendances found',
                'inserted' => 0,
                'processed_ids' => [],
            ];
        }

        // Find employee
        $employee = DB::table('employees')->where('nip', $employeeNip)->first();
        if (! $employee) {
            Log::channel('process-attendance')->warning("Employee with NIP {$employeeNip} not found");

            return [
                'success' => false,
                'message' => "Employee with NIP {$employeeNip} not found",
                'inserted' => 0,
                'processed_ids' => [],
            ];
        }

        // Group by date
        $attendancesByDate = $rawAttendances->groupBy(function ($item) {
            return Carbon::parse($item->timestamp)->format('Y-m-d');
        });

        $summariesToInsert = [];
        $processedIds = [];

        foreach ($attendancesByDate as $date => $dailyAttendances) {
            // Get employee schedules for the date
            $schedules = DB::table('schedules')
                ->join('shifts', 'schedules.shift_id', '=', 'shifts.id')
                ->where('schedules.employee_id', $employee->id)
                ->select('schedules.id as schedule_id', 'shifts.id as shift_id', 'shifts.name as shift_name', 'shifts.clock_in', 'shifts.clock_out')
                ->orderBy('shifts.clock_in')
                ->get();

            if ($schedules->isEmpty()) {
                Log::channel('process-attendance')->warning("No schedules found for NIP {$employeeNip} on {$date}");

                continue;
            }

            // Sort attendances
            $sortedAttendances = $dailyAttendances->sortBy('timestamp')->values();
            if ($sortedAttendances->isEmpty()) {
                continue;
            }

            // Build shift information with boundaries
            $shiftsInfo = [];
            foreach ($schedules as $schedule) {
                $shiftStart = Carbon::parse($date.' '.$schedule->clock_in);
                $shiftEnd = Carbon::parse($date.' '.$schedule->clock_out);

                // Handle overnight shift
                if ($shiftEnd->lt($shiftStart)) {
                    $shiftEnd->addDay();
                }

                $shiftsInfo[] = [
                    'schedule' => $schedule,
                    'start' => $shiftStart,
                    'end' => $shiftEnd,
                    'midpoint' => $shiftStart->copy()->addSeconds($shiftEnd->diffInSeconds($shiftStart) / 2),
                ];
            }

            // Assign attendances to shifts
            $attendanceAssignments = $this->assignAttendancesToShifts($sortedAttendances, $shiftsInfo, $date);

            // Process each shift
            foreach ($shiftsInfo as $shiftInfo) {
                $schedule = $shiftInfo['schedule'];
                $shiftStart = $shiftInfo['start'];
                $shiftEnd = $shiftInfo['end'];
                $midShift = $shiftInfo['midpoint'];

                // Check if summary already exists
                $existingSummary = DB::table('attendance_summaries')
                    ->where('employee_id', $employee->id)
                    ->where('date', $date)
                    ->where('schedule_id', $schedule->schedule_id)
                    ->first();

                if ($existingSummary) {
                    continue;
                }

                // Get assigned attendances for this shift
                $shiftAttendances = $attendanceAssignments[$schedule->schedule_id] ?? collect();

                if ($shiftAttendances->isEmpty()) {
                    Log::channel('process-attendance')->debug("No attendances assigned to shift {$schedule->shift_name} for NIP {$employeeNip} on {$date}");

                    continue;
                }

                // Find clock in and clock out
                $clockInRecord = null;
                $clockOutRecord = null;
                $minDiffForClockIn = PHP_INT_MAX;
                $minDiffForClockOut = PHP_INT_MAX;

                foreach ($shiftAttendances as $attendance) {
                    $attendanceTime = Carbon::parse($attendance->timestamp);

                    // Check for clock in (before mid-shift)
                    if ($attendanceTime->lt($midShift)) {
                        $diffWithShiftStart = abs($attendanceTime->diffInSeconds($shiftStart));
                        if ($diffWithShiftStart < $minDiffForClockIn) {
                            $minDiffForClockIn = $diffWithShiftStart;
                            $clockInRecord = $attendance;
                        }
                    }
                    // Check for clock out (after mid-shift)
                    else {
                        $diffWithShiftEnd = abs($attendanceTime->diffInSeconds($shiftEnd));
                        if ($diffWithShiftEnd < $minDiffForClockOut) {
                            $minDiffForClockOut = $diffWithShiftEnd;
                            $clockOutRecord = $attendance;
                        }
                    }
                }

                // Fallback logic
                if (! $clockInRecord && $shiftAttendances->count() > 0) {
                    $clockInRecord = $shiftAttendances->first();
                }

                if (! $clockOutRecord && $shiftAttendances->count() > 0) {
                    $clockOutRecord = $shiftAttendances->last();

                    // Avoid duplicate
                    if ($clockOutRecord && $clockInRecord && $clockOutRecord->id === $clockInRecord->id) {
                        $clockOutRecord = null;
                    }
                }

                // Calculate metrics
                $workHours = 0;
                $lateMinutes = 0;
                $overtimeHours = 0;
                $clockInTime = null;
                $clockOutTime = null;

                if ($clockInRecord) {
                    $clockInTime = Carbon::parse($clockInRecord->timestamp);

                    // Calculate late minutes
                    if ($clockInTime->isAfter($shiftStart->copy()->addMinutes(15))) {
                        $lateMinutes = max(0, $clockInTime->diffInMinutes($shiftStart));
                    }
                }

                if ($clockInRecord && $clockOutRecord) {
                    $clockOutTime = Carbon::parse($clockOutRecord->timestamp);

                    // Calculate work hours
                    if ($clockOutTime->gt($clockInTime)) {
                        $workHours = $clockOutTime->diffInMinutes($clockInTime) / 60;
                    } else {
                        $workHours = $clockOutTime->copy()->addDay()->diffInMinutes($clockInTime) / 60;
                    }

                    $workHours = abs(round($workHours, 2));
                    $workHours = min($workHours, 8); // Cap at 8 hours per shift
                }

                // Check overtime
                if ($clockOutRecord) {
                    $clockOutTime = Carbon::parse($clockOutRecord->timestamp);

                    if ($clockOutTime->isAfter($shiftEnd)) {
                        $isOvertimeApproved = DB::table('overtime_requests')
                            ->where('employee_id', $employee->id)
                            ->where('date', $date)
                            ->where('status_approval', 'approved')
                            ->exists();

                        if ($isOvertimeApproved) {
                            $overtimeHours = round($clockOutTime->diffInMinutes($shiftEnd) / 60, 2);
                        }
                    }
                }

                // If attendance record only has 1 entry
                if ($clockOutRecord && $shiftAttendances->count() == 1) {
                    $workHours = 0;
                    $lateMinutes = 0;
                    $overtimeHours = 0;
                }

                $summariesToInsert[] = [
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'branch_id' => $employee->branch_id,
                    'schedule_id' => $schedule->schedule_id,
                    'shift_id' => $schedule->shift_id,
                    'shift_name' => $schedule->shift_name,
                    'clock_in' => $clockInTime ? $clockInTime->toTimeString() : null,
                    'clock_out' => $clockOutTime ? $clockOutTime->toTimeString() : null,
                    'work_hours' => $workHours,
                    'late_minutes' => $lateMinutes,
                    'overtime_hours' => $overtimeHours,
                    'total_attendances' => $shiftAttendances->count(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                Log::channel('process-attendance')->info("Processed shift {$schedule->shift_name} for NIP {$employeeNip} on {$date}");
            }
        }

        // Bulk insert summaries and mark as processed in transaction
        $insertedCount = 0;
        DB::transaction(function () use ($summariesToInsert, $rawAttendances, &$insertedCount, &$processedIds) {
            if (! empty($summariesToInsert)) {
                DB::table('attendance_summaries')->insert($summariesToInsert);
                $insertedCount = count($summariesToInsert);
                Log::channel('process-attendance')->info("Inserted {$insertedCount} attendance summary records");
            }

            // Mark as processed
            $processedIds = $rawAttendances->pluck('id')->toArray();
            DB::table('attendances')
                ->whereIn('id', $processedIds)
                ->update(['is_processed' => true]);
        });

        return [
            'success' => true,
            'message' => "Successfully processed {$insertedCount} attendance summaries",
            'inserted' => $insertedCount,
            'processed_ids' => $processedIds,
        ];
    }

    /**
     * Intelligently assign attendances to shifts for multi-shift days
     */
    private function assignAttendancesToShifts(Collection $attendances, array $shiftsInfo, string $date): array
    {
        $assignments = [];

        // Initialize empty assignments for each shift
        foreach ($shiftsInfo as $shiftInfo) {
            $assignments[$shiftInfo['schedule']->schedule_id] = collect();
        }

        // If only one shift, assign all attendances to it
        if (count($shiftsInfo) === 1) {
            $assignments[$shiftsInfo[0]['schedule']->schedule_id] = $attendances;

            return $assignments;
        }

        // Multiple shifts: assign based on the EARLIEST attendance
        $firstAttendance = $attendances->sortBy('timestamp')->first();
        $firstAttendanceTime = Carbon::parse($firstAttendance->timestamp);

        $selectedShift = null;
        $closestDistance = PHP_INT_MAX;

        // Find which shift this earliest attendance belongs to
        foreach ($shiftsInfo as $shiftInfo) {
            $scheduleId = $shiftInfo['schedule']->schedule_id;
            $shiftStart = $shiftInfo['start'];
            $shiftEnd = $shiftInfo['end'];

            // Check if attendance falls within shift boundaries (with 1-hour tolerance before start)
            $windowStart = $shiftStart->copy()->subHours(1);
            $windowEnd = $shiftEnd->copy()->addMinutes(15);

            if ($firstAttendanceTime->between($windowStart, $windowEnd)) {
                // Calculate distance to shift start
                $distance = abs($firstAttendanceTime->diffInSeconds($shiftStart));

                if ($distance < $closestDistance) {
                    $closestDistance = $distance;
                    $selectedShift = $scheduleId;
                }
            }
        }

        // Assign all attendances to the selected shift
        if ($selectedShift !== null) {
            $assignments[$selectedShift] = $attendances;
        } else {
            // Fallback: assign to first shift if no match found
            $assignments[$shiftsInfo[0]['schedule']->schedule_id] = $attendances;
        }

        return $assignments;
    }
}
