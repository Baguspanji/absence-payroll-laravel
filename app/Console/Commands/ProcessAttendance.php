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
                // LOGIKA BARU: Cari jam masuk dan pulang berdasarkan status_scan
                $clockInRecord  = $dailyAttendances->where('status_scan', '0')->first(); // Scan masuk pertama
                $clockOutRecord = $dailyAttendances->where('status_scan', '1')->last();  // Scan pulang terakhir

                // Simpan ke tabel rekapitulasi
                DB::table('attendance_summaries')->updateOrInsert(
                    [
                        'employee_id' => $employee->id,
                        'date'        => $date,
                    ],
                    [
                        'branch_id'  => $employee->branch_id,
                        'clock_in'   => $clockInRecord ? Carbon::parse($clockInRecord->timestamp)->toTimeString() : null,
                        'clock_out'  => $clockOutRecord ? Carbon::parse($clockOutRecord->timestamp)->toTimeString() : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // Tandai semua data yang sudah diproses
        DB::table('attendances')->whereIn('id', $rawAttendances->pluck('id'))->update(['is_processed' => true]);

        $this->info('Proses rekapitulasi absensi selesai.');
    }
}
