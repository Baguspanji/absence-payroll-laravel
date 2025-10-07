<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessAttendance extends Command
{
    // Nama command kita yang akan dipanggil di terminal
    protected $signature = 'app:process-attendance';

    // Deskripsi command
    protected $description = 'Merekapitulasi data absensi mentah menjadi laporan harian';

    public function handle()
    {
        $this->info('Memulai proses rekapitulasi absensi...');

        // Ambil semua data absensi mentah yang belum diproses
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
            // Kelompokkan lagi berdasarkan tanggal
            $attendancesByDate = $attendances->groupBy(function ($item) {
                return Carbon::parse($item->timestamp)->format('Y-m-d');
            });

            foreach ($attendancesByDate as $date => $dailyAttendances) {
                // Asumsi: scan pertama adalah jam masuk, scan terakhir adalah jam pulang
                $clockIn  = $dailyAttendances->first();
                $clockOut = $dailyAttendances->count() > 1 ? $dailyAttendances->last() : null;

                // Ambil informasi device dari data absensi
                $deviceSN   = $clockIn->device_sn ?? 'UNKNOWN';
                $deviceInfo = $clockIn->device_info ?? '';

                DB::table('attendance_summaries')->updateOrInsert(
                    [
                        'employee_nip' => $nip,
                        'date'         => $date,
                    ],
                    [
                        'clock_in'    => Carbon::parse($clockIn->timestamp)->toTimeString(),
                        'clock_out'   => $clockOut ? Carbon::parse($clockOut->timestamp)->toTimeString() : null,
                        'device_sn'   => $deviceSN,
                        'device_info' => $deviceInfo,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]
                );
            }
        }

        // Tandai semua data yang sudah diproses
        DB::table('attendances')->whereIn('id', $rawAttendances->pluck('id'))->update(['is_processed' => true]);

        $this->info('Proses rekapitulasi absensi selesai.');
    }
}
