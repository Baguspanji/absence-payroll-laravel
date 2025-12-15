<?php

use Livewire\Volt\Component;
use App\Models\AttendanceSummary;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

new class extends Component {
    use WithPagination;

    public $employees;

    #[Url]
    public $employeeFilter = null;

    public function mount()
    {
        if (Auth::user()->role == 'leader') {
            $this->employees = \App\Models\Employee::where('branch_id', Auth::user()->employee->branch_id)
                ->select('id', 'name')
                ->get();
            return;
        }

        $this->employees = \App\Models\Employee::select('id', 'name')->get();
    }

    /**
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        if (Auth::user()->role == 'leader') {
            return [
                'requests' => AttendanceSummary::query()
                    ->with('employee')
                    ->whereHas('employee', function ($q) {
                        $q->where('branch_id', Auth::user()->employee->branch_id);
                    })
                    ->when($this->employeeFilter, function ($q) {
                        $q->where('employee_id', $this->employeeFilter);
                    })
                    ->latest()
                    ->paginate(10),
            ];
        }

        return [
            'requests' => AttendanceSummary::query()
                ->with('employee.branch')
                ->when($this->employeeFilter, function ($q) {
                    $q->where('employee_id', $this->employeeFilter);
                })
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Rekap Absensi</h2>
    </div>

    <!-- Filter Section -->
    <div class="mb-4">
        <form class="flex flex-wrap gap-4 items-end">
            <!-- Filter By Employee -->
            <div class="min-w-xs">
                <flux:select wire:model.live="employeeFilter" placeholder="Filter Pegawai...">
                    <flux:select.option value="">Pilih Pegawai</flux:select.option>
                    @foreach ($employees as $employee)
                        <flux:select.option value="{{ $employee->id }}" :selected="$employeeFilter == $employee->id">
                            {{ $employee->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </form>
    </div>

    <x-table :headers="['Pegawai', 'Tanggal', 'Waktu', 'Total Kerja(jam)', 'Telat(menit)', 'Lembur(jam)']" :rows="$requests" emptyMessage="Tidak ada data riwayat." fixedHeader="true"
        maxHeight="540px">
        @foreach ($requests as $request)
            <x-table.row>
                <x-table.cell class="font-medium text-gray-900 whitespace-nowrap">
                    <div class="flex items-center gap-4">
                        @if ($request->employee?->image_url)
                            <img src="{{ $request->employee?->image_url }}" alt="Foto Karyawan"
                                class="w-10 h-10 rounded object-cover" />
                        @else
                            <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center text-gray-500">
                                <flux:icon name="user" class="w-6 h-6" />
                            </div>
                        @endif
                        <div class="flex flex-col items-start">
                            <span class="font-mono text-green-600">{{ $request->employee?->nip }}</span>
                            <span>{{ $request->employee?->name }}</span>
                            @can('admin')
                                <span class="text-sm text-gray-500">{{ $request->employee?->branch?->name }}</span>
                            @endcan
                        </div>
                    </div>
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    {{ $request->date }}
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    {{ $request->clock_in }} - {{ $request->clock_out }}
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    {{ $request->work_hours }}
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    {{ $request->late_minutes }}
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    {{ $request->overtime_hours }}
                </x-table.cell>
            </x-table.row>
        @endforeach
    </x-table>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>
