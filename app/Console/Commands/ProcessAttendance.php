<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAttendance extends Command
{
    protected $signature = 'app:process-attendance';

    protected $description = 'Merekapitulasi data absensi mentah menjadi laporan harian yang akurat (Multi-Shift)';

    public function handle()
    {
        $this->info('Memulai proses rekapitulasi absensi...');
        Log::channel('process-attendance')->info('Memulai proses rekapitulasi absensi...');

        $rawAttendances = DB::table('attendances')
            ->where('is_processed', false)
                                            // Start Debug
            // ->where('employee_nip', '20250099') // Skip test entries
            // ->where('timestamp', 'like', '2025-12-27%') // Skip test entries
                                            // End Debug
            ->orderBy('timestamp', 'asc')
            ->get();

        if ($rawAttendances->isEmpty()) {
            $this->info('Tidak ada data absensi baru untuk diproses.');
            Log::channel('process-attendance')->info('Tidak ada data absensi baru untuk diproses.');

            return;
        }

        // Kelompokkan data berdasarkan NIP karyawan
        $attendancesByUser = $rawAttendances->groupBy('employee_nip');

        $summariesToInsert = [];
        foreach ($attendancesByUser as $nip => $attendances) {
            // Cari data karyawan
            $employee = DB::table('employees')->where('nip', $nip)->first();
            if (! $employee) {
                $this->warn("Karyawan dengan NIP {$nip} tidak ditemukan. Melewatkan...");
                Log::channel('process-attendance')->warning("Karyawan dengan NIP {$nip} tidak ditemukan. Melewatkan...");

                continue;
            }

            // Kelompokkan berdasarkan tanggal
            $attendancesByDate = $attendances->groupBy(function ($item) {
                return Carbon::parse($item->timestamp)->format('Y-m-d');
            });

            foreach ($attendancesByDate as $date => $dailyAttendances) {
                // Ambil semua jadwal shift karyawan untuk tanggal tersebut
                $schedules = DB::table('schedules')
                    ->join('shifts', 'schedules.shift_id', '=', 'shifts.id')
                    ->where('schedules.employee_id', $employee->id)
                    ->select('schedules.id as schedule_id', 'shifts.id as shift_id', 'shifts.name as shift_name', 'shifts.clock_in', 'shifts.clock_out')
                    ->orderBy('shifts.clock_in')
                    ->get();

                if ($schedules->isEmpty()) {
                    $this->warn("Jadwal shift untuk karyawan NIP {$nip} pada tanggal {$date} tidak ditemukan. Melewatkan...");
                    Log::channel('process-attendance')->warning("Jadwal shift untuk karyawan NIP {$nip} pada tanggal {$date} tidak ditemukan. Melewatkan...");

                    continue;
                }

                // Sort attendances
                $sortedAttendances = $dailyAttendances->sortBy('timestamp')->values();
                if ($sortedAttendances->isEmpty()) {
                    continue;
                }

                // ← NEW: Build shift information with proper boundaries
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

                // ← NEW: Assign attendances to shifts intelligently (prevent duplication)
                $attendanceAssignments = $this->assignAttendancesToShifts($sortedAttendances, $shiftsInfo, $date);

                // Proses setiap shift dengan attendance yang sudah ditetapkan
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

                    // ← NEW: Get assigned attendances for this shift
                    $shiftAttendances = $attendanceAssignments[$schedule->schedule_id] ?? collect();

                    if ($shiftAttendances->isEmpty()) {
                        Log::channel('process-attendance')->debug("No attendances assigned to shift {$schedule->shift_name} for NIP {$nip} on {$date}");

                        continue;
                    }

                    // Initialize variables
                    $clockInRecord = null;
                    $clockOutRecord = null;
                    $minDiffForClockIn = PHP_INT_MAX;
                    $minDiffForClockOut = PHP_INT_MAX;

                    // Cari clock in dan clock out yang paling mendekati shift
                    foreach ($shiftAttendances as $attendance) {
                        $attendanceTime = Carbon::parse($attendance->timestamp);

                        // Check for clock in (sebelum mid-shift)
                        if ($attendanceTime->lt($midShift)) {
                            $diffWithShiftStart = abs($attendanceTime->diffInSeconds($shiftStart));
                            if ($diffWithShiftStart < $minDiffForClockIn) {
                                $minDiffForClockIn = $diffWithShiftStart;
                                $clockInRecord = $attendance;
                            }
                        }
                        // Check for clock out (setelah mid-shift)
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

                    // Jika rowayat absensi hanya 1
                    if ($clockOutRecord && $shiftAttendances->count() == 1) {
                        $workHours = 0;
                        $lateMinutes = 0;
                        $overtimeHours = 0;
                    }

                    // Debug logging
                    // Log::channel('process-attendance')->debug("NIP: {$nip}");
                    // Log::channel('process-attendance')->debug("Date: {$date}");
                    // Log::channel('process-attendance')->debug("Shift: {$schedule->shift_name}");
                    // Log::channel('process-attendance')->debug("Shift Start: {$shiftStart->toDateTimeString()}");
                    // Log::channel('process-attendance')->debug("Shift End: {$shiftEnd->toDateTimeString()}");
                    // Log::channel('process-attendance')->debug("Attendances Count: {$shiftAttendances->count()}");
                    // Log::channel('process-attendance')->debug('Clock In: '.($clockInTime ? $clockInTime->toDateTimeString() : 'null'));
                    // Log::channel('process-attendance')->debug('Clock Out: '.($clockOutTime ? $clockOutTime->toDateTimeString() : 'null'));
                    // Log::channel('process-attendance')->debug("Work Hours: {$workHours}");
                    // Log::channel('process-attendance')->debug("Late Minutes: {$lateMinutes}");
                    // Log::channel('process-attendance')->debug("Overtime Hours: {$overtimeHours}");

                    // Insert summary record
                    // DB::table('attendance_summaries')->insert([
                    //     'employee_id' => $employee->id,
                    //     'date' => $date,
                    //     'branch_id' => $employee->branch_id,
                    //     'schedule_id' => $schedule->schedule_id,
                    //     'shift_id' => $schedule->shift_id,
                    //     'shift_name' => $schedule->shift_name,
                    //     'clock_in' => $clockInTime ? $clockInTime->toTimeString() : null,
                    //     'clock_out' => $clockOutTime ? $clockOutTime->toTimeString() : null,
                    //     'work_hours' => $workHours,
                    //     'late_minutes' => $lateMinutes,
                    //     'overtime_hours' => $overtimeHours,
                    //     'total_attendances' => $shiftAttendances->count(),
                    //     'created_at' => now(),
                    //     'updated_at' => now(),
                    // ]);

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

                    $this->info("Processed shift {$schedule->shift_name} for NIP {$nip} on {$date}");
                    Log::channel('process-attendance')->info("Processed shift {$schedule->shift_name} for NIP {$nip} on {$date}");
                }
            }
        }

        // Bulk insert summaries and mark as processed in a transaction
        DB::transaction(function () use ($summariesToInsert, $rawAttendances) {
            if (! empty($summariesToInsert)) {
                DB::table('attendance_summaries')->insert($summariesToInsert);
                Log::channel('process-attendance')->info('Inserted '.\count($summariesToInsert).' attendance summary records.');
            }

            // Mark as processed
            DB::table('attendances')
                ->whereIn('id', $rawAttendances->pluck('id'))
                ->update(['is_processed' => true]);
        });

        $this->info('Proses rekapitulasi absensi selesai.');
        Log::channel('process-attendance')->info('Proses rekapitulasi absensi selesai.');
    }

    /**
     * ← IMPROVED METHOD: Intelligently assign attendances to shifts
     * For multi-shift days, consolidate all attendances to ONE shift based on:
     * 1. Which shift has the EARLIEST first attendance
     * 2. This determines which shift the employee actually started with
     */
    private function assignAttendancesToShifts($attendances, $shiftsInfo, $date)
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
        // This represents which shift the employee actually worked
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
