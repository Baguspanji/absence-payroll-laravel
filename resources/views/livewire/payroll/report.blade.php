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

    public function mount()
    {
        $this->years = range(Carbon::now()->year, Carbon::now()->year - 5);
        $this->months = collect(range(1, 12))->mapWithKeys(fn($m) => [$m => Carbon::create()->month($m)->format('F')])->toArray();
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
}; ?>

<div class="p-6">
    <h2 class="text-2xl font-bold mb-6">Laporan Rekap Payroll</h2>

    <div class="bg-white p-4 rounded-lg shadow-md mb-6 flex items-end space-x-4">
        <div>
            <label for="month" class="block text-sm font-medium text-gray-700">Bulan</label>
            <select wire:model.live="selectedMonth" id="month"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @foreach ($months as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="year" class="block text-sm font-medium text-gray-700">Tahun</label>
            <select wire:model.live="selectedYear" id="year"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @foreach ($years as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Nama Karyawan</th>
                    <th class="px-6 py-3">NIP</th>
                    <th class="px-6 py-3">Gaji Bersih</th>
                    <th class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrolls as $payroll)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium">{{ $payroll->employee->name }}</td>
                        <td class="px-6 py-4">{{ $payroll->employee->nip }}</td>
                        <td class="px-6 py-4">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('payroll.slip', $payroll->id) }}" target="_blank"
                                class="font-medium text-indigo-600 hover:underline">Lihat Slip</a>
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
</div>
