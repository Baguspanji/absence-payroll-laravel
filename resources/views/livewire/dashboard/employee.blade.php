<?php

use Livewire\Volt\Component;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public function with(): array
    {
        // Get employee count per branch
        $employeePerBranch = Employee::select('branch_id', DB::raw('COUNT(*) as employee_count'))->with('branch:id,name')->groupBy('branch_id')->get();

        $branchNames = $employeePerBranch->pluck('branch.name')->toArray();
        $employeeCount = $employeePerBranch->pluck('employee_count')->toArray();

        // Get employee count per job level (position)
        $employeePerPosition = Employee::select('position', DB::raw('COUNT(*) as employee_count'))->groupBy('position')->get();

        $positionNames = $employeePerPosition->pluck('position')->toArray();
        $positionCount = $employeePerPosition->pluck('employee_count')->toArray();

        return [
            'branchNames' => $branchNames,
            'employeeCount' => $employeeCount,
            'positionNames' => $positionNames,
            'positionCount' => $positionCount,
        ];
    }
}; ?>

<div class="grid grid-cols-1 gap-6">
    <!-- Employee Per Branch Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold mb-4">Jumlah Pegawai Per Cabang</h3>

        <div x-data="{
            chartInstance: null,
            initChart() {
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js not loaded');
                    return;
                }
                const ctx = document.getElementById('employeePerBranchChart');
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
                        labels: @js($branchNames),
                        datasets: [{
                            label: 'Jumlah Pegawai',
                            data: @js($employeeCount),
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(249, 115, 22, 0.8)',
                                'rgba(168, 85, 247, 0.8)',
                            ],
                            borderColor: [
                                'rgba(59, 130, 246, 1)',
                                'rgba(34, 197, 94, 1)',
                                'rgba(249, 115, 22, 1)',
                                'rgba(168, 85, 247, 1)',
                            ],
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
                                display: false,
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + ' pegawai';
                                    }
                                }
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
        }" x-init="$nextTick(() => { setTimeout(() => initChart(), 100) })">
            <div style="height: 300px;">
                <canvas id="employeePerBranchChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Employee Per Job Level Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold mb-4">Jumlah Pegawai Per Tingkat Jabatan</h3>

        <div x-data="{
            chartInstance: null,
            initChart() {
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js not loaded');
                    return;
                }
                const ctx = document.getElementById('employeePerPositionChart');
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
                        labels: @js($positionNames),
                        datasets: [{
                            label: 'Jumlah Pegawai',
                            data: @js($positionCount),
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(34, 197, 94, 0.8)',
                                'rgba(249, 115, 22, 0.8)',
                                'rgba(168, 85, 247, 0.8)',
                            ],
                            borderColor: [
                                'rgba(59, 130, 246, 1)',
                                'rgba(34, 197, 94, 1)',
                                'rgba(249, 115, 22, 1)',
                                'rgba(168, 85, 247, 1)',
                            ],
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
                                display: false,
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y + ' pegawai';
                                    }
                                }
                            },
                            datalabels: {
                                anchor: 'end',
                                align: 'top',
                                color: '#666',
                                font: {
                                    weight: 'bold',
                                    size: 14
                                },
                                formatter: Math.round
                            }
                        }
                    }
                });
            }
        }" x-init="$nextTick(() => { setTimeout(() => initChart(), 100) })">
            <div style="height: 300px;">
                <canvas id="employeePerPositionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
    if (typeof Chart !== 'undefined') {
        Chart.register(ChartDataLabels);
    }
</script>
