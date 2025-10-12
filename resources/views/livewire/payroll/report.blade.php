<?php

use Livewire\Volt\Component;
use App\Models\Payroll;
use Carbon\Carbon;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $selectedMonth;
    public $selectedYear;
    public $years = [];
    public $months = [];
    public $showDetailModal = false;
    public $payrollDetails = null;
    public $workSummary = null;
    public $earningComponents = [];
    public $deductionComponents = [];

    public function mount()
    {
        $this->years = range(Carbon::now()->year, Carbon::now()->year - 5);
        $this->months = collect(range(1, 12))->mapWithKeys(fn($m) => [$m => Carbon::create()->month($m)->translatedFormat('F')])->toArray();
        $this->selectedYear = Carbon::now()->year;
        $this->selectedMonth = Carbon::now()->month;
    }

    public function with(): array
    {
        $payrolls = Payroll::whereYear('period_start', $this->selectedYear)->whereMonth('period_start', $this->selectedMonth)->with('employee')->paginate(15);

        return [
            'payrolls' => $payrolls,
        ];
    }

    public function payrollDetail($payrollId)
    {
        $payroll = Payroll::with(['employee', 'details'])->findOrFail($payrollId);
        $this->payrollDetails = $payroll;

        // Get work summary for this employee in the payroll period
        $workSummary = \DB::table('attendance_summaries')
            ->where('employee_id', $payroll->employee_id)
            ->whereBetween('date', [$payroll->period_start, $payroll->period_end])
            ->select(\DB::raw('SUM(work_hours) as total_work_hours'), \DB::raw('COUNT(*) as total_workdays'), \DB::raw('COUNT(CASE WHEN late_minutes > 0 THEN 1 END) as total_late_days'), \DB::raw('SUM(late_minutes) as total_late_minutes'), \DB::raw('SUM(overtime_hours) as total_overtime_hours'))
            ->first();

        $this->workSummary = $workSummary;

        // Organize payroll components
        $this->earningComponents = $payroll->details->where('type', 'earning')->toArray();
        $this->deductionComponents = $payroll->details->where('type', 'deduction')->toArray();

        $this->showDetailModal = true;
    }

    public function closeModal()
    {
        $this->showDetailModal = false;
    }
}; ?>

<div class="px-6 py-4">
    <h2 class="text-2xl font-bold mb-6">Laporan Rekap Penggajian</h2>

    <div class="bg-white p-4 rounded-lg shadow-md mb-6 flex items-end space-x-4">
        <flux:select label="Bulan" wire:model="selectedMonth" placeholder="Pilih Bulan...">
            @foreach ($months as $num => $name)
                <flux:select.option value="{{ $num }}">{{ $name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select label="Tahun" wire:model="selectedYear" placeholder="Pilih Tahun...">
            @foreach ($years as $year)
                <flux:select.option value="{{ $year }}">{{ $year }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">#</th>
                    <th class="px-6 py-3">Nama Karyawan</th>
                    <th class="px-6 py-3">Gaji Bersih</th>
                    <th class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrolls as $payroll)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">{{ $payroll->employee?->nip }}</td>
                        <td class="px-6 py-4 font-medium">{{ $payroll->employee?->name }}</td>
                        <td class="px-6 py-4">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <div class="space-x-1">
                                <a href="{{ route('payroll.slip', $payroll->id) }}" target="_blank"
                                    class="text-xs font-medium px-2 py-1.5 bg-indigo-600 text-white rounded-md cursor-pointer">
                                    Lihat Slip
                                </a>
                                <button type="button"
                                    class="text-xs font-medium px-2 py-1.5 bg-orange-600 text-white rounded-md cursor-pointer"
                                    wire:click="payrollDetail({{ $payroll->id }})">
                                    Lihat Detail
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-4">Tidak ada data payroll untuk periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $payrolls->links() }}</div>

    <!-- Payroll Detail Modal -->
    @if ($showDetailModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-[#99a1afb3] transform transition-opacity">
            <div class="relative p-6 bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                <button type="button" wire:click="closeModal"
                    class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <h3 class="text-lg font-bold mb-4">Detail Penggajian</h3>

                <div class="border-b pb-4 mb-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600">NIP</p>
                            <p>{{ $payrollDetails->employee->nip }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Nama Karyawan</p>
                            <p>{{ $payrollDetails->employee->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Periode</p>
                            <p>{{ \Carbon\Carbon::parse($payrollDetails->period_start)->translatedFormat('d M Y') }} -
                                {{ \Carbon\Carbon::parse($payrollDetails->period_end)->translatedFormat('d M Y') }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-b pb-4 mb-4">
                    <h4 class="font-bold mb-2">Ringkasan Kehadiran</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Hari Kerja</p>
                            <p>{{ $workSummary->total_workdays ?? 0 }} hari</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Jam Kerja</p>
                            <p>{{ $workSummary->total_work_hours ?? 0 }} jam</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Hari Terlambat</p>
                            <p>{{ $workSummary->total_late_days ?? 0 }} hari</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Menit Terlambat</p>
                            <p>{{ $workSummary->total_late_minutes ?? 0 }} menit</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Jam Lembur</p>
                            <p>{{ $workSummary->total_overtime_hours ?? 0 }} jam</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-bold mb-2 text-green-600">Pendapatan</h4>
                        <div class="space-y-2">
                            @foreach ($earningComponents as $component)
                                <div class="flex justify-between">
                                    <span>{{ $component['description'] }}</span>
                                    <span>Rp {{ number_format($component['amount'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-t mt-2 pt-2 font-bold flex justify-between">
                            <span>Total Pendapatan</span>
                            <span>Rp
                                {{ number_format(array_sum(array_column($earningComponents, 'amount')), 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold mb-2 text-red-600">Potongan</h4>
                        <div class="space-y-2">
                            @foreach ($deductionComponents as $component)
                                <div class="flex justify-between">
                                    <span>{{ $component['description'] }}</span>
                                    <span>Rp {{ number_format($component['amount'], 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-t mt-2 pt-2 font-bold flex justify-between">
                            <span>Total Potongan</span>
                            <span>Rp
                                {{ number_format(array_sum(array_column($deductionComponents, 'amount')), 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <div class="border-t pt-4 flex justify-between items-center">
                    <span class="font-bold text-lg">Total Gaji Bersih</span>
                    <span class="font-bold text-lg">Rp
                        {{ number_format($payrollDetails->net_salary, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
