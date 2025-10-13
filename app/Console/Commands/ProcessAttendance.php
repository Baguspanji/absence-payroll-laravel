<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessAttendance extends Command
{
    protected $signature   = 'app:process-attendance';
    protected $description = 'Merekapitulasi data absensi mentah menjadi laporan harian yang akurat';

    public function handle()
    {
        $this->info('Memulai proses rekapitulasi absensi...');

        $rawAttendances = DB::table('attendances')
            ->where('is_processed', false)
            ->orderBy('timestamp', 'asc')
            ->get();

        if ($rawAttendances->isEmpty()) {
            $this->info('Tidak ada data absensi baru untuk diproses.');
            return;
        }

        // Kelompokkan data berdasarkan NIP karyawan
        $attendancesByUser = $rawAttendances->groupBy('employee_nip');

        foreach ($attendancesByUser as $nip => $attendances) {
            // Cari data karyawan dan cabang berdasarkan NIP
            $employee = DB::table('employees')->where('nip', $nip)->first();
            if (! $employee) {
                $this->warn("Karyawan dengan NIP {$nip} tidak ditemukan. Melewatkan...");
                continue;
            }

            // Kelompokkan lagi berdasarkan tanggal
            $attendancesByDate = $attendances->groupBy(function ($item) {
                return Carbon::parse($item->timestamp)->format('Y-m-d');
            });

            // Cari jadwal shift karyawan
            $schedule = DB::table('schedules')
                ->join('shifts', 'schedules.shift_id', '=', 'shifts.id')
                ->where('employee_id', $employee->id)
            // ->where('date', $date)
                ->select('shifts.clock_in', 'shifts.clock_out')
                ->first();

            if (! $schedule) {
                $this->warn("Jadwal shift untuk karyawan NIP {$nip} tidak ditemukan. Melewatkan...");
                continue;
            }

            foreach ($attendancesByDate as $date => $dailyAttendances) {
                // Check if a summary already exists for this employee and date
                $existingSummary = DB::table('attendance_summaries')
                    ->where('employee_id', $employee->id)
                    ->where('date', $date)
                    ->first();

                if ($existingSummary) {
                    continue; // Skip processing if already processed
                }

                // Sort attendances efficiently using Collection method once
                $sortedAttendances = $dailyAttendances->sortBy('timestamp')->values();
                if ($sortedAttendances->isEmpty()) {
                    continue;
                }

                // Parse shift times once
                $shiftStart = Carbon::parse($date . ' ' . $schedule->clock_in);
                $shiftEnd   = Carbon::parse($date . ' ' . $schedule->clock_out);
                $midShift   = $shiftStart->copy()->addSeconds($shiftEnd->diffInSeconds($shiftStart) / 2);

                // Initialize variables
                $clockInRecord      = null;
                $clockOutRecord     = null;
                $minDiffForClockIn  = PHP_INT_MAX;
                $minDiffForClockOut = PHP_INT_MAX;

                // Single loop through attendances to find clock in/out
                foreach ($sortedAttendances as $attendance) {
                    $attendanceTime = Carbon::parse($attendance->timestamp);

                    // Check for clock in (before midshift)
                    if ($attendanceTime->lt($midShift)) {
                        $diffWithShiftStart = abs($attendanceTime->diffInSeconds($shiftStart));
                        if ($diffWithShiftStart < $minDiffForClockIn) {
                            $minDiffForClockIn = $diffWithShiftStart;
                            $clockInRecord     = $attendance;
                        }
                    }
                    // Check for clock out (after midshift)
                    else {
                        $diffWithShiftEnd = abs($attendanceTime->diffInSeconds($shiftEnd));
                        if ($diffWithShiftEnd < $minDiffForClockOut) {
                            $minDiffForClockOut = $diffWithShiftEnd;
                            $clockOutRecord     = $attendance;
                        }
                    }
                }

                // Fallback logic - first scan as clock in, last as clock out if needed
                if (! $clockInRecord && $sortedAttendances->count() > 0) {
                    $clockInRecord = $sortedAttendances->first();
                }

                if (! $clockOutRecord && $sortedAttendances->count() > 0) {
                    $clockOutRecord = $sortedAttendances->last();

                    // Avoid duplicate records
                    if ($clockOutRecord && $clockInRecord && $clockOutRecord->id === $clockInRecord->id) {
                        $clockOutRecord = null;
                    }
                }

                // Calculate metrics only if we have valid records
                $workHours     = 0;
                $lateMinutes   = 0;
                $overtimeHours = 0;
                $clockInTime   = null;
                $clockOutTime  = null;

                if ($clockInRecord) {
                    $clockInTime = Carbon::parse($clockInRecord->timestamp);

                    // Calculate late minutes
                    if ($clockInTime->isAfter($shiftStart->copy()->addMinutes(15))) {
                        $lateMinutes = $clockInTime->diffInMinutes($shiftStart);
                    }
                }

                if ($clockInRecord && $clockOutRecord) {
                    $clockOutTime = Carbon::parse($clockOutRecord->timestamp);

                    // Calculate work hours
                    if ($clockOutTime->gt($clockInTime)) {
                        $workHours = $clockOutTime->diffInMinutes($clockInTime) / 60;
                    } else {
                        // Handle overnight shifts
                        $workHours = $clockOutTime->copy()->addDay()->diffInMinutes($clockInTime) / 60;
                    }

                    $workHours = round($workHours, 2);
                    $workHours = min($workHours, 8); // Cap at 8 hours
                }

                // Only check overtime if we have a clock out and there's an approved request
                if ($clockOutRecord) {
                    $clockOutTime = Carbon::parse($clockOutRecord->timestamp);

                    if ($clockOutTime->isAfter($shiftEnd)) {
                        // Use cached query for overtime approval
                        $isOvertimeApproved = DB::table('overtime_requests')
                            ->where('employee_id', $employee->id)
                            ->where('date', $date)
                            ->where('status', 'approved')
                            ->exists();

                        if ($isOvertimeApproved) {
                            $overtimeHours = round($clockOutTime->diffInMinutes($shiftEnd) / 60, 2);
                        }
                    }
                }

                // Insert record in a single operation
                DB::table('attendance_summaries')->insert([
                    'employee_id'    => $employee->id,
                    'date'           => $date,
                    'branch_id'      => $employee->branch_id,
                    'clock_in'       => $clockInTime ? $clockInTime->toTimeString() : null,
                    'clock_out'      => $clockOutTime ? $clockOutTime->toTimeString() : null,
                    'work_hours'     => $workHours,
                    'late_minutes'   => $lateMinutes,
                    'overtime_hours' => $overtimeHours,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        // Tandai semua data yang sudah diproses
        DB::table('attendances')->whereIn('id', $rawAttendances->pluck('id'))->update(['is_processed' => true]);

        $this->info('Proses rekapitulasi absensi selesai.');
    }
}
