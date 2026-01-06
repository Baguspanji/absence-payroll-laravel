<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\AttendanceSummary;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $attendanceFilterType = 'monthly';
    public int $selectedYear;
    public int $selectedMonth;

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');
        $this->selectedMonth = (int) date('m');
    }

    #[On('updated')]
    public function refreshChart()
    {
        $this->dispatch('chartUpdated');
    }

    public function updated($property)
    {
        if (in_array($property, ['attendanceFilterType', 'selectedYear', 'selectedMonth'])) {
            $this->dispatch('chartUpdated');
        }
    }

    public function with(): array
    {
        $chartData = [];
        $lateMinutesData = [];
        $overtimeHoursData = [];
        $chartLabels = [];

        if ($this->attendanceFilterType === 'yearly') {
            // Get attendance summaries by month for selected year
            $attendanceData = AttendanceSummary::select(DB::raw('MONTH(date) as month'), DB::raw('COUNT(*) as total_attendances'), DB::raw('COUNT(DISTINCT CASE WHEN late_minutes > 0 THEN employee_id END) as count_late_employees'), DB::raw('COUNT(DISTINCT CASE WHEN overtime_hours > 0 THEN employee_id END) as count_overtime_employees'))->whereYear('date', $this->selectedYear)->groupBy(DB::raw('MONTH(date)'))->orderBy('month')->get();

            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $chartLabels = $months;

            foreach (range(1, 12) as $monthNum) {
                $monthData = $attendanceData->firstWhere('month', $monthNum);
                $chartData[] = $monthData ? $monthData->total_attendances : 0;
                $lateMinutesData[] = $monthData ? $monthData->count_late_employees : 0;
                $overtimeHoursData[] = $monthData ? $monthData->count_overtime_employees : 0;
            }
        } else {
            // Get attendance summaries by day for selected month
            $attendanceData = AttendanceSummary::select(DB::raw('DAY(date) as day'), DB::raw('COUNT(*) as total_attendances'), DB::raw('COUNT(DISTINCT CASE WHEN late_minutes > 0 THEN employee_id END) as count_late_employees'), DB::raw('COUNT(DISTINCT CASE WHEN overtime_hours > 0 THEN employee_id END) as count_overtime_employees'))->whereYear('date', $this->selectedYear)->whereMonth('date', $this->selectedMonth)->groupBy(DB::raw('DAY(date)'))->orderBy('day')->get();

            $daysInMonth = (int) date('t', mktime(0, 0, 0, $this->selectedMonth, 1, $this->selectedYear));

            foreach (range(1, $daysInMonth) as $day) {
                $dayData = $attendanceData->firstWhere('day', $day);
                $chartLabels[] = $day;
                $chartData[] = $dayData ? $dayData->total_attendances : 0;
                $lateMinutesData[] = $dayData ? $dayData->count_late_employees : 0;
                $overtimeHoursData[] = $dayData ? $dayData->count_overtime_employees : 0;
            }
        }

        return [
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'lateMinutesData' => $lateMinutesData,
            'overtimeHoursData' => $overtimeHoursData,
            'years' => range(date('Y') - 5, date('Y')),
        ];
    }
}; ?>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-xl font-bold">Ringkasan Absensi</h3>

        <!-- Filter Form -->
        <div class="flex gap-4 items-center">
            <!-- Filter Type -->
            <div class="flex gap-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" wire:model.live="attendanceFilterType" value="monthly"
                        class="w-4 h-4 cursor-pointer" />
                    <span class="text-sm font-medium">Bulanan</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" wire:model.live="attendanceFilterType" value="yearly"
                        class="w-4 h-4 cursor-pointer" />
                    <span class="text-sm font-medium">Tahunan</span>
                </label>
            </div>

            <!-- Year Picker -->
            <select wire:model.live="selectedYear" class="px-3 py-1 border border-gray-300 rounded-lg text-sm">
                @foreach ($years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>

            <!-- Month Picker (only show for monthly view) -->
            @if ($attendanceFilterType === 'monthly')
                <select wire:model.live="selectedMonth" class="px-3 py-1 border border-gray-300 rounded-lg text-sm">
                    @foreach (['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $index => $month)
                        <option value="{{ $index + 1 }}">{{ $month }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    <div x-data="{
        chartInstance: null,
        init() {
            this.$watch('$wire.attendanceFilterType', () => this.initChart());
            this.$watch('$wire.selectedYear', () => this.initChart());
            this.$watch('$wire.selectedMonth', () => this.initChart());
            this.$nextTick(() => { setTimeout(() => this.initChart(), 100) });
        },
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
            if (this.chartInstance) {
                this.chartInstance.destroy();
            }
            this.chartInstance = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @js($chartLabels),
                    datasets: [{
                        label: 'Total Kehadiran',
                        data: @js($chartData),
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Jumlah Pegawai Terlambat',
                        data: @js($lateMinutesData),
                        backgroundColor: 'rgba(249, 115, 22, 0.5)',
                        borderColor: 'rgba(249, 115, 22, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Jumlah Pegawai Lembur',
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
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: '#666',
                            font: {
                                weight: 'bold',
                                size: 11
                            },
                            formatter: Math.round
                        }
                    }
                }
            });
        }
    }">
        <div style="height: 250px;">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js">
</script>
<script>
    if (typeof Chart !== 'undefined') {
        Chart.register(ChartDataLabels);
    }
</script>
