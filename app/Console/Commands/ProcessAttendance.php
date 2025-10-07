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
                    ->where('date', $date)
                    ->select('shifts.clock_in', 'shifts.clock_out')
                    ->first();

                if (! $schedule) {
                    $this->warn("Jadwal shift untuk karyawan NIP {$nip} pada tanggal {$date} tidak ditemukan. Melewatkan...");
                    continue;
                }

                                                                                         // LOGIKA BARU: Cari jam masuk dan pulang berdasarkan status_scan
                $clockInRecord  = $dailyAttendances->where('status_scan', '0')->first(); // Scan masuk pertama
                $clockOutRecord = $dailyAttendances->where('status_scan', '1')->last();  // Scan pulang terakhir

                $lateMinutes   = 0;
                $overtimeHours = 0;

                // --- LOGIKA KALKULASI KETERLAMBATAN ---
                if ($clockInRecord) {
                    $clockInTime    = Carbon::parse($clockInRecord->timestamp);
                    $shiftStartTime = Carbon::parse($date . ' ' . $schedule->clock_in);

                    // Toleransi 15 menit
                    if ($clockInTime->isAfter($shiftStartTime->addMinutes(15))) {
                        $lateMinutes = $clockInTime->diffInMinutes($shiftStartTime->subMinutes(15));
                    }
                }

                // --- LOGIKA KALKULASI LEMBUR (VERSI SEDERHANA) ---

                // 1. Cek apakah ada izin lembur yang sudah disetujui untuk hari ini
                $isOvertimeApproved = DB::table('overtime_requests')
                    ->where('employee_id', $employee->id)
                    ->where('date', $date)
                    ->where('status', 'approved')
                    ->exists();

                // 2. Jika disetujui, baru jalankan kalkulasi
                if ($isOvertimeApproved && $clockOutRecord) {
                    $schedule = DB::table('schedules')
                        ->join('shifts', 'schedules.shift_id', '=', 'shifts.id')
                        ->where('employee_id', $employee->id)->where('date', $date)
                        ->select('shifts.jam_pulang')->first();

                    if ($schedule) {
                        $clockOutTime = Carbon::parse($clockOutRecord->timestamp);
                        $shiftEndTime = Carbon::parse($date . ' ' . $schedule->jam_pulang);

                        if ($clockOutTime->isAfter($shiftEndTime)) {
                            $overtimeHours = round($clockOutTime->diffInMinutes($shiftEndTime) / 60, 2);
                        }
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
