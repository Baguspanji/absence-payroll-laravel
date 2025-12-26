<?php

use Livewire\Volt\Component;
use App\Models\Payroll;
use Carbon\Carbon;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $selectedMonth;
    public $selectedYear;
    public $years = [];
    public $months = [];
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
        $query = Payroll::whereYear('period_start', $this->selectedYear)->whereMonth('period_start', $this->selectedMonth)->with('employee.branch');

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('employee', function ($eq) {
                    $eq->where('name', 'like', '%' . $this->search . '%')->orWhere('nip', 'like', '%' . $this->search . '%');
                });
            });
        }

        return [
            'payrolls' => $query->paginate(15),
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

        $this->modal('detail-modal')->show();
    }

    public function closeModal()
    {
        $this->modal('detail-modal')->close();
    }
}; ?>

<div class="px-6 py-4">
    <h2 class="text-2xl font-bold mb-6">Laporan Rekap Penggajian</h2>

    <div class="mb-6 flex flex-col md:flex-row items-end gap-4">
        <div class="w-full md:max-w-[10rem]">
            <flux:select wire:model="selectedMonth" placeholder="Pilih Bulan...">
            @foreach ($months as $num => $name)
                <flux:select.option value="{{ $num }}">{{ $name }}</flux:select.option>
            @endforeach
            </flux:select>
        </div>
        <div class="w-full md:max-w-[10rem]">
            <flux:select wire:model="selectedYear" placeholder="Pilih Tahun...">
            @foreach ($years as $year)
                <flux:select.option value="{{ $year }}">{{ $year }}</flux:select.option>
            @endforeach
            </flux:select>
        </div>
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama karyawan atau NIP..."
                icon="magnifying-glass" />
        </div>
    </div>

    <x-table :headers="['Karyawan', 'Cabang', 'Gaji Bersih', 'Aksi']" :rows="$payrolls" emptyMessage="Tidak ada data payroll untuk periode ini."
        fixedHeader="true" maxHeight="540px">
        @foreach ($payrolls as $payroll)
            <x-table.row>
                <x-table.cell class="font-medium text-gray-900 whitespace-nowrap">
                    <div class="flex items-center gap-4">
                        <div class="flex flex-col items-start">
                            <span class="font-mono text-green-600">{{ $payroll->employee?->nip }}</span>
                            <span>{{ $payroll->employee?->name }}</span>
                        </div>
                    </div>
                </x-table.cell>
                <x-table.cell>
                    {{ $payroll->employee?->branch?->name }}
                </x-table.cell>
                <x-table.cell class="font-semibold text-green-600">
                    Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap w-[15%]">
                    <x-button-tooltip tooltip="Lihat slip gaji" icon="document-text"
                        class="text-sm text-indigo-600 px-2 py-1 rounded hover:bg-indigo-100 cursor-pointer"
                        iconClass="w-4 h-4 inline-block -mt-1">
                        <a href="{{ route('payroll.slip', $payroll->id) }}" target="_blank"
                            class="block w-full h-full"></a>
                    </x-button-tooltip>
                    <x-button-tooltip tooltip="Lihat detail" icon="eye"
                        wire:click="payrollDetail({{ $payroll->id }})"
                        class="text-sm text-orange-600 px-2 py-1 rounded hover:bg-orange-100 cursor-pointer"
                        iconClass="w-4 h-4 inline-block -mt-1">
                    </x-button-tooltip>
                </x-table.cell>
            </x-table.row>
        @endforeach
    </x-table>

    <div class="mt-4">{{ $payrolls->links() }}</div>

    <!-- Modal Detail -->
    <flux:modal name="detail-modal" class="max-w-4xl md:w-[60rem]" closeable>
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Detail Penggajian</flux:heading>
            </div>

            @if ($payrollDetails)
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
            @endif
        </div>
    </flux:modal>
</div>
