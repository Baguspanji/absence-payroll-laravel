<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessAttendance extends Command
{
    protected $signature = 'app:process-attendance';

    protected $description = 'Merekapitulasi data absensi mentah menjadi laporan harian yang akurat (Multi-Shift)';

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
            // Cari data karyawan
            $employee = DB::table('employees')->where('nip', $nip)->first();
            if (! $employee) {
                $this->warn("Karyawan dengan NIP {$nip} tidak ditemukan. Melewatkan...");

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
                    // ->where('schedules.date', $date)
                    ->select('schedules.id as schedule_id', 'shifts.id as shift_id', 'shifts.name as shift_name', 'shifts.clock_in', 'shifts.clock_out')
                    ->orderBy('shifts.clock_in')
                    ->get();

                if ($schedules->isEmpty()) {
                    $this->warn("Jadwal shift untuk karyawan NIP {$nip} pada tanggal {$date} tidak ditemukan. Melewatkan...");

                    continue;
                }

                // Sort attendances
                $sortedAttendances = $dailyAttendances->sortBy('timestamp')->values();
                if ($sortedAttendances->isEmpty()) {
                    continue;
                }

                // Proses setiap shift
                foreach ($schedules as $schedule) {
                    // Check if summary already exists
                    $existingSummary = DB::table('attendance_summaries')
                        ->where('employee_id', $employee->id)
                        ->where('date', $date)
                        ->where('schedule_id', $schedule->schedule_id)
                        ->first();

                    if ($existingSummary) {
                        continue;
                    }

                    // Parse shift times
                    $shiftStart = Carbon::parse($date.' '.$schedule->clock_in);
                    $shiftEnd = Carbon::parse($date.' '.$schedule->clock_out);

                    // Handle overnight shift
                    if ($shiftEnd->lt($shiftStart)) {
                        $shiftEnd->addDay();
                    }

                    $midShift = $shiftStart->copy()->addSeconds($shiftEnd->diffInSeconds($shiftStart) / 2);

                    // Toleransi waktu untuk mencocokkan absensi dengan shift (misal: 3 jam sebelum/sesudah shift)
                    $shiftWindow = 3 * 60 * 60; // 3 jam dalam detik
                    $windowStart = $shiftStart->copy()->subSeconds($shiftWindow);
                    $windowEnd = $shiftEnd->copy()->addSeconds($shiftWindow);

                    // Filter attendances yang masuk dalam window shift ini
                    $shiftAttendances = $sortedAttendances->filter(function ($attendance) use ($windowStart, $windowEnd) {
                        $attendanceTime = Carbon::parse($attendance->timestamp);

                        return $attendanceTime->between($windowStart, $windowEnd);
                    });

                    if ($shiftAttendances->isEmpty()) {
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
                            $lateMinutes = $clockInTime->diffInMinutes($shiftStart);
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

                        $workHours = round($workHours, 2);
                        $workHours = min($workHours, 8); // Cap at 8 hours per shift
                    }

                    // Check overtime
                    if ($clockOutRecord) {
                        $clockOutTime = Carbon::parse($clockOutRecord->timestamp);

                        if ($clockOutTime->isAfter($shiftEnd)) {
                            $isOvertimeApproved = DB::table('overtime_requests')
                                ->where('employee_id', $employee->id)
                                ->where('date', $date)
                                ->where('schedule_id', $schedule->schedule_id)
                                ->where('status', 'approved')
                                ->exists();

                            if ($isOvertimeApproved) {
                                $overtimeHours = round($clockOutTime->diffInMinutes($shiftEnd) / 60, 2);
                            }
                        }
                    }

                    // Insert summary record
                    DB::table('attendance_summaries')->insert([
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
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->info("Processed shift {$schedule->shift_name} for NIP {$nip} on {$date}");
                }
            }
        }

        // Mark as processed
        DB::table('attendances')->whereIn('id', $rawAttendances->pluck('id'))->update(['is_processed' => true]);

        $this->info('Proses rekapitulasi absensi selesai.');
    }
}
