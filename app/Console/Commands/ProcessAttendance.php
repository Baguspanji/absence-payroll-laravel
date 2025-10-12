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

            foreach ($attendancesByDate as $date => $dailyAttendances) {
                // Cari jadwal shift karyawan pada hari itu
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

                // Ambil semua record absensi hari ini dan sortir berdasarkan waktu
                $sortedAttendances = $dailyAttendances->sortBy('timestamp');

                // Ambil waktu jadwal sebagai Carbon objects
                $shiftStart = Carbon::parse($date . ' ' . $schedule->clock_in);
                $shiftEnd   = Carbon::parse($date . ' ' . $schedule->clock_out);

                // Waktu tengah shift untuk membantu penentuan clock_in/clock_out
                $midShift = $shiftStart->copy()->addSeconds($shiftEnd->diffInSeconds($shiftStart) / 2);

                // Inisialisasi
                $clockInRecord  = null;
                $clockOutRecord = null;

                // Cari clock in (scan paling dekat dengan jam masuk)
                $minDiffForClockIn = PHP_INT_MAX;
                foreach ($sortedAttendances as $attendance) {
                    $attendanceTime = Carbon::parse($attendance->timestamp);

                    // Hanya proses absensi sebelum tengah shift sebagai kandidat clock in
                    if ($attendanceTime->lt($midShift)) {
                        $diffWithShiftStart = abs($attendanceTime->diffInSeconds($shiftStart));
                        if ($diffWithShiftStart < $minDiffForClockIn) {
                            $minDiffForClockIn = $diffWithShiftStart;
                            $clockInRecord     = $attendance;
                        }
                    }
                }

                // Cari clock out (scan paling dekat dengan jam pulang)
                $minDiffForClockOut = PHP_INT_MAX;
                foreach ($sortedAttendances as $attendance) {
                    $attendanceTime = Carbon::parse($attendance->timestamp);

                    // Hanya proses absensi setelah tengah shift sebagai kandidat clock out
                    if ($attendanceTime->gt($midShift)) {
                        $diffWithShiftEnd = abs($attendanceTime->diffInSeconds($shiftEnd));
                        if ($diffWithShiftEnd < $minDiffForClockOut) {
                            $minDiffForClockOut = $diffWithShiftEnd;
                            $clockOutRecord     = $attendance;
                        }
                    }
                }

                // Jika tidak ada record di salah satu bagian shift (pagi/sore)
                // Coba gunakan logika alternatif - scan paling awal sebagai clock in dan paling akhir sebagai clock out
                if (! $clockInRecord && $sortedAttendances->count() > 0) {
                    $clockInRecord = $sortedAttendances->first();
                }

                if (! $clockOutRecord && $sortedAttendances->count() > 0) {
                    $clockOutRecord = $sortedAttendances->last();

                    // Jika masih sama dengan clock in, set menjadi null untuk menghindari duplikasi
                    if ($clockOutRecord && $clockInRecord && $clockOutRecord->id === $clockInRecord->id) {
                        $clockOutRecord = null;
                    }
                }

                $workHours = 0;
                $lateMinutes   = 0;
                $overtimeHours = 0;

                // --- LOGIKA KALKULASI JAM KERJA ---
                if ($clockInRecord && $clockOutRecord) {
                    $clockInTime = Carbon::parse($clockInRecord->timestamp);
                    $clockOutTime = Carbon::parse($clockOutRecord->timestamp);

                    // Only count if clock out is after clock in
                    if ($clockOutTime->gt($clockInTime)) {
                        $workHours = round(abs($clockOutTime->diffInMinutes($clockInTime)) / 60, 2);
                    } else {
                        // If clock out appears to be before clock in (e.g., next day clock out),
                        // assume 24-hour format and calculate accordingly
                        $adjustedClockOutTime = $clockOutTime->copy()->addDay();
                        $workHours = round(abs($adjustedClockOutTime->diffInMinutes($clockInTime)) / 60, 2);
                    }
                }

                // --- LOGIKA KALKULASI KETERLAMBATAN ---
                if ($clockInRecord) {
                    $clockInTime = Carbon::parse($clockInRecord->timestamp);

                    // Toleransi 15 menit
                    if ($clockInTime->isAfter($shiftStart->copy()->addMinutes(15))) {
                        $lateMinutes = round(abs($clockInTime->diffInMinutes($shiftStart)));
                    }
                }

                // --- LOGIKA KALKULASI LEMBUR ---
                $isOvertimeApproved = DB::table('overtime_requests')
                    ->where('employee_id', $employee->id)
                    ->where('date', $date)
                    ->where('status', 'approved')
                    ->exists();

                if ($isOvertimeApproved && $clockOutRecord) {
                    $clockOutTime = Carbon::parse($clockOutRecord->timestamp);

                    if ($clockOutTime->isAfter($shiftEnd)) {
                        $overtimeHours = round(abs($clockOutTime->diffInMinutes($shiftEnd)) / 60, 2);
                    }
                }

                // Simpan ke tabel rekapitulasi
                DB::table('attendance_summaries')->updateOrInsert(
                    [
                        'employee_id' => $employee->id,
                        'date'        => $date,
                    ],
                    [
                        'branch_id'      => $employee->branch_id,
                        'clock_in'       => $clockInRecord ? Carbon::parse($clockInRecord->timestamp)->toTimeString() : null,
                        'clock_out'      => $clockOutRecord ? Carbon::parse($clockOutRecord->timestamp)->toTimeString() : null,
                        'work_hours'     => min($workHours, 8), // Maksimal 8 jam kerja normal
                        'late_minutes'   => $lateMinutes,
                        'overtime_hours' => $overtimeHours,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]
                );
            }
        }

        // Tandai semua data yang sudah diproses
        DB::table('attendances')->whereIn('id', $rawAttendances->pluck('id'))->update(['is_processed' => true]);

        $this->info('Proses rekapitulasi absensi selesai.');
    }
}
