@php
    $currentYear = date('Y');
    $yearsRange = range($currentYear - 5, $currentYear);
@endphp

<div class="bg-white rounded-lg shadow p-6">
    <div class="grid grid-cols-2 gap-4 mb-6 items-center">
        <h3 class="text-xl font-bold">Ringkasan Absensi</h3>

        <!-- Filter Form -->
        <div class="flex flex-row items-center justify-end gap-4">
            <!-- Filter Type -->
            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="attendanceFilterType" value="monthly" class="w-4 h-4 cursor-pointer"
                        checked />
                    <span class="text-sm font-medium">Bulanan</span>
                </label>
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="attendanceFilterType" value="yearly" class="w-4 h-4 cursor-pointer" />
                    <span class="text-sm font-medium">Tahunan</span>
                </label>
            </div>

            <div class="relative">
                <select id="selectedYear" class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none pr-8">
                    @foreach ($yearsRange as $year)
                        <option value="{{ $year }}" @if ($year == $currentYear) selected @endif>
                            {{ $year }}</option>
                    @endforeach
                </select>
                <flux:icon name="chevrons-up-down" class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-600 pointer-events-none" />
            </div>

            <div class="relative">
                <select id="selectedMonth" class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none pr-8">
                    @foreach (['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as $index => $month)
                        <option value="{{ $index + 1 }}" @if ($index + 1 == date('m')) selected @endif>
                            {{ $month }}</option>
                    @endforeach
                </select>
                <flux:icon name="chevrons-up-down" class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-600 pointer-events-none" />
            </div>
        </div>
    </div>

    <div style="height: 250px;">
        <canvas id="attendanceChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js">
</script>

<script>
    // Register plugin if Chart is available
    if (typeof Chart !== 'undefined') {
        Chart.register(ChartDataLabels);
    }

    let chartInstance = null;
    const filterTypeRadios = document.querySelectorAll('input[name="attendanceFilterType"]');
    const yearSelect = document.getElementById('selectedYear');
    const monthSelect = document.getElementById('selectedMonth');
    const monthSelectContainer = monthSelect.closest('.flex')?.parentElement;

    // Initialize month visibility
    updateMonthVisibility();

    // Event listeners for filter changes
    filterTypeRadios.forEach(radio => {
        radio.addEventListener('change', () => {
            updateMonthVisibility();
            loadChartData();
        });
    });

    yearSelect.addEventListener('change', loadChartData);
    monthSelect.addEventListener('change', loadChartData);

    function updateMonthVisibility() {
        const filterType = document.querySelector('input[name="attendanceFilterType"]:checked').value;
        if (monthSelect.parentElement) {
            if (filterType === 'monthly') {
                monthSelect.parentElement.style.display = 'block';
            } else {
                monthSelect.parentElement.style.display = 'none';
            }
        }
    }

    function loadChartData() {
        const filterType = document.querySelector('input[name="attendanceFilterType"]:checked').value;
        const year = document.getElementById('selectedYear').value;
        const month = document.getElementById('selectedMonth').value;

        // Build query parameters
        const params = new URLSearchParams({
            filterType: filterType,
            year: year,
            month: month
        });

        // Fetch data from endpoint
        fetch(`{{ route('ajax.dashboard.data') }}?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    updateChart(data.data);
                }
            })
            .catch(error => {
                console.error('Error loading chart data:', error);
            });
    }

    function updateChart(data) {
        const canvas = document.getElementById('attendanceChart');
        if (!canvas) {
            console.error('Canvas element not found');
            return;
        }

        // Destroy existing chart instance
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        const ctx = canvas.getContext('2d');

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.chartLabels,
                datasets: [{
                    label: 'Total Kehadiran',
                    data: data.chartData,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }, {
                    label: 'Jumlah Pegawai Terlambat',
                    data: data.lateMinutesData,
                    backgroundColor: 'rgba(249, 115, 22, 0.5)',
                    borderColor: 'rgba(249, 115, 22, 1)',
                    borderWidth: 1
                }, {
                    label: 'Jumlah Pegawai Lembur',
                    data: data.overtimeHoursData,
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

    // Load initial chart data
    document.addEventListener('DOMContentLoaded', () => {
        loadChartData();
    });
</script>
