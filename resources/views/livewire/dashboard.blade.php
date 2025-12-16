<?php

use Livewire\Volt\Component;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\AttendanceSummary;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public function with(): array
    {
        $totalBranches = Branch::count();
        $totalEmployees = Employee::count();
        $totalShifts = Shift::count();

        // Get attendance summaries by month for the current year
        $attendanceByMonth = AttendanceSummary::select(
            DB::raw('MONTH(date) as month'),
            DB::raw('COUNT(*) as total_attendances'),
            DB::raw('SUM(work_hours) as total_work_hours'),
            DB::raw('SUM(late_minutes) as total_late_minutes'),
            DB::raw('SUM(overtime_hours) as total_overtime_hours')
        )
        ->whereYear('date', date('Y'))
        ->groupBy(DB::raw('MONTH(date)'))
        ->orderBy('month')
        ->get();

        // Format data for chart
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartData = [];
        $workHoursData = [];
        $lateMinutesData = [];
        $overtimeHoursData = [];

        foreach (range(1, 12) as $monthNum) {
            $monthData = $attendanceByMonth->firstWhere('month', $monthNum);
            $chartData[] = $monthData ? $monthData->total_attendances : 0;
            $workHoursData[] = $monthData ? round($monthData->total_work_hours, 2) : 0;
            $lateMinutesData[] = $monthData ? round($monthData->total_late_minutes, 2) : 0;
            $overtimeHoursData[] = $monthData ? round($monthData->total_overtime_hours, 2) : 0;
        }

        return [
            'totalBranches' => $totalBranches,
            'totalEmployees' => $totalEmployees,
            'totalShifts' => $totalShifts,
            'months' => $months,
            'chartData' => $chartData,
            'workHoursData' => $workHoursData,
            'lateMinutesData' => $lateMinutesData,
            'overtimeHoursData' => $overtimeHoursData,
        ];
    }
}; ?>

<div class="px-6 py-4">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Cabang -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Cabang</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $totalBranches }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Pegawai -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Pegawai</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $totalEmployees }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Shift -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Shift</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $totalShifts }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold mb-4">Ringkasan Absensi Bulanan ({{ date('Y') }})</h3>

        <div x-data="{
            chartInstance: null,
            initChart() {
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js not loaded');
                    return;
                }
                const ctx = document.getElementById('attendanceChart');
                if (!ctx) {
                    console.error('Canvas element not found');
                    return;
                }
                this.chartInstance = new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: @js($months),
                        datasets: [{
                            label: 'Total Kehadiran',
                            data: @js($chartData),
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Total Jam Kerja',
                            data: @js($workHoursData),
                            backgroundColor: 'rgba(34, 197, 94, 0.5)',
                            borderColor: 'rgba(34, 197, 94, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Total Keterlambatan (menit)',
                            data: @js($lateMinutesData),
                            backgroundColor: 'rgba(239, 68, 68, 0.5)',
                            borderColor: 'rgba(239, 68, 68, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Total Lembur (jam)',
                            data: @js($overtimeHoursData),
                            backgroundColor: 'rgba(168, 85, 247, 0.5)',
                            borderColor: 'rgba(168, 85, 247, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                            }
                        }
                    }
                });
            }
        }" x-init="$nextTick(() => { setTimeout(() => initChart(), 100) })">
            <div style="height: 400px;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
