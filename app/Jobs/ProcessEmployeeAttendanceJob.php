<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\AttendanceProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEmployeeAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $employeeNip,
        public string $startDate,
        public string $endDate,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('process-attendance')->info(
            "Starting ProcessEmployeeAttendanceJob for NIP {$this->employeeNip} from {$this->startDate} to {$this->endDate}",
        );

        $service = new AttendanceProcessingService();
        $result = $service->processAttendanceForEmployeeAndDateRange(
            $this->employeeNip,
            $this->startDate,
            $this->endDate,
        );

        if ($result['success']) {
            Log::channel('process-attendance')->info(
                "ProcessEmployeeAttendanceJob completed successfully: {$result['message']}",
            );
        } else {
            Log::channel('process-attendance')->error("ProcessEmployeeAttendanceJob failed: {$result['message']}");
            throw new \Exception("Failed to process attendance: {$result['message']}");
        }
    }
}
