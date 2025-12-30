<?php

namespace App\Console\Commands;

use App\Services\AttendanceProcessingService;
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

        // Get all unprocessed attendances grouped by employee NIP
        $unprocessedAttendances = DB::table('attendances')
            ->where('is_processed', false)
            ->orderBy('timestamp', 'asc')
            ->get();

        if ($unprocessedAttendances->isEmpty()) {
            $this->info('Tidak ada data absensi baru untuk diproses.');
            Log::channel('process-attendance')->info('Tidak ada data absensi baru untuk diproses.');

            return;
        }

        // Group by employee NIP
        $attendancesByNip = $unprocessedAttendances->groupBy('employee_nip');

        $service = new AttendanceProcessingService;
        $totalInserted = 0;

        foreach ($attendancesByNip as $nip => $attendances) {
            // Get the date range for this NIP's attendances
            $dates = $attendances->map(function ($a) {
                return Carbon::parse($a->timestamp)->format('Y-m-d');
            })->unique();

            $startDate = $dates->min();
            $endDate = $dates->max();

            $this->info("Processing NIP {$nip} from {$startDate} to {$endDate}...");

            $result = $service->processAttendanceForEmployeeAndDateRange($nip, $startDate, $endDate);

            if ($result['success']) {
                $totalInserted += $result['inserted'];
                $this->info("✓ {$result['message']}");
            } else {
                $this->warn("✗ {$result['message']}");
            }
        }

        $this->info("Proses rekapitulasi absensi selesai. Total inserted: {$totalInserted}");
        Log::channel('process-attendance')->info("Proses rekapitulasi absensi selesai. Total inserted: {$totalInserted}");
    }
}
