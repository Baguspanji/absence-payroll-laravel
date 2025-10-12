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

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">#</th>
                    <th scope="col" class="px-6 py-3">Nama Pegawai</th>
                    @can('admin')
                        <th scope="col" class="px-6 py-3">Cabang</th>
                    @endcan
                    <th scope="col" class="px-6 py-3">Tanggal</th>
                    <th scope="col" class="px-6 py-3">Waktu</th>
                    <th scope="col" class="px-6 py-3">Total Kerja(jam)</th>
                    <th scope="col" class="px-6 py-3">Telat(menit)</th>
                    <th scope="col" class="px-6 py-3">Lembur(jam)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="font-mono px-6 py-4">{{ $request->employee?->nip }}</td>
                        <td class="font-mono px-6 py-4">{{ $request->employee?->name }}</td>
                        @can('admin')
                            <td class="font-mono px-6 py-4">{{ $request->employee?->branch?->name }}</td>
                        @endcan
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->date }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->clock_in }} -
                            {{ $request->clock_out }}
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->work_hours }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->late_minutes }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->overtime_hours }}</td>
                    </tr>
                @empty
                    <tr class="bg-white border-b">
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            Tidak ada data riwayat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>
