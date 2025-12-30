<?php

use Livewire\Volt\Component;
use App\Models\AttendanceSummary;
use App\Models\Branch;
use App\Models\Employee;
use App\Jobs\ProcessEmployeeAttendanceJob;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

    public $employees;
    public $branches;

    #[Url]
    public $employeeFilter = null;
    #[Url]
    public $branchFilter = null;
    #[Url]
    public $start_date = null;
    #[Url]
    public $end_date = null;

    // Reprocess form properties
    #[Rule('required', message: 'Karyawan harus dipilih.')]
    public $reprocessEmployeeId = '';

    #[Rule('required', message: 'Tanggal mulai harus diisi.')]
    public $reprocessStartDate = '';

    #[Rule('required', message: 'Tanggal akhir harus diisi.')]
    public $reprocessEndDate = '';

    public function mount()
    {
        $this->branches = Branch::select('id', 'name')->get();

        if (Auth::user()->role == 'leader') {
            $this->employees = Employee::where('branch_id', Auth::user()->employee->branch_id)
                ->select('id', 'name')
                ->get();
            return;
        }

        $this->employees = Employee::select('id', 'name')->orderBy('name')->get();
    }

    public function openReprocessForm()
    {
        $this->resetReprocessForm();
        $this->modal('reprocess-attendance-modal')->show();
    }

    public function resetReprocessForm()
    {
        $this->reprocessEmployeeId = '';
        $this->reprocessStartDate = '';
        $this->reprocessEndDate = '';
    }

    public function reprocessAttendance()
    {
        $this->validate(
            [
                'reprocessEmployeeId' => 'required',
                'reprocessStartDate' => 'required|date',
                'reprocessEndDate' => 'required|date|after_or_equal:reprocessStartDate',
            ],
            [
                'reprocessEmployeeId.required' => 'Karyawan harus dipilih.',
                'reprocessStartDate.required' => 'Tanggal mulai harus diisi.',
                'reprocessEndDate.required' => 'Tanggal akhir harus diisi.',
                'reprocessEndDate.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
            ],
        );

        $employee = Employee::find($this->reprocessEmployeeId);
        if (!$employee) {
            $this->dispatch('alert-shown', message: 'Karyawan tidak ditemukan!', type: 'error');
            return;
        }

        // Delete existing summaries for the date range
        AttendanceSummary::where('employee_id', $this->reprocessEmployeeId)
            ->whereBetween('date', [$this->reprocessStartDate, $this->reprocessEndDate])
            ->delete();

        // Mark attendances as not processed so they will be reprocessed
        $updatedCount = DB::table('attendances')
            ->where('employee_nip', $employee->nip)
            ->whereBetween(DB::raw('DATE(timestamp)'), [$this->reprocessStartDate, $this->reprocessEndDate])
            ->update(['is_processed' => false]);

        // Dispatch job to process attendance
        ProcessEmployeeAttendanceJob::dispatch(
            $employee->nip,
            $this->reprocessStartDate,
            $this->reprocessEndDate
        );

        $this->dispatch('alert-shown',
            message: "Absensi telah dikirim ke antrian untuk diproses! ({$updatedCount} rekam absensi dihapus)",
            type: 'success'
        );

        $this->modal('reprocess-attendance-modal')->close();
        $this->resetReprocessForm();
        $this->resetPage();
    }

    /**
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        if (Auth::user()->role == 'leader') {
            return [
                'requests' => AttendanceSummary::query()
                    ->with('employee.branch')
                    ->whereHas('employee', function ($q) {
                        $q->where('branch_id', Auth::user()->employee->branch_id);
                    })
                    ->when($this->employeeFilter, function ($q) {
                        $q->where('employee_id', $this->employeeFilter);
                    })
                    ->when($this->start_date, function ($q) {
                        $q->whereDate('date', '>=', $this->start_date);
                    })
                    ->when($this->end_date, function ($q) {
                        $q->whereDate('date', '<=', $this->end_date);
                    })
                    ->orderBy('date', 'desc')
                    ->paginate(10),
            ];
        }

        return [
            'requests' => AttendanceSummary::query()
                ->with('employee.branch')
                ->when($this->employeeFilter, function ($q) {
                    $q->where('employee_id', $this->employeeFilter);
                })
                ->when($this->branchFilter, function ($q) {
                    $q->where('branch_id', $this->branchFilter);
                })
                ->when($this->start_date, function ($q) {
                    $q->whereDate('date', '>=', $this->start_date);
                })
                ->when($this->end_date, function ($q) {
                    $q->whereDate('date', '<=', $this->end_date);
                })
                ->orderBy('date', 'desc')
                ->paginate(10),
        ];
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Rekap Absensi</h2>
        <button class="text-sm px-2 py-1.5 bg-orange-600 text-white rounded hover:bg-orange-700 cursor-pointer"
            wire:click="openReprocessForm">
            <flux:icon name="arrow-path" class="w-4 h-4 inline-block -mt-1" />
            Proses Ulang Rekap Absensi
        </button>
    </div>

    <!-- Filter Section -->
    <div class="mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Filter By Branch -->
            @if (Auth::user()->role !== 'leader')
                <div>
                    <flux:select wire:model.live="branchFilter" placeholder="Filter Cabang...">
                        <flux:select.option value="">Pilih Cabang</flux:select.option>
                        @foreach ($branches as $branch)
                            <flux:select.option value="{{ $branch->id }}" :selected="$branchFilter == $branch->id">
                                {{ $branch->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif
            <!-- Filter By Employee -->
            <div>
                <flux:select wire:model.live="employeeFilter" placeholder="Filter Pegawai...">
                    <flux:select.option value="">Pilih Pegawai</flux:select.option>
                    @foreach ($employees as $employee)
                        <flux:select.option value="{{ $employee->id }}" :selected="$employeeFilter == $employee->id">
                            {{ $employee->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <!-- Filter By Start Date -->
            <div>
                <flux:input type="date" wire:model.live="start_date" placeholder="Tanggal Mulai..." />
            </div>
            <!-- Filter By End Date -->
            <div>
                <flux:input type="date" wire:model.live="end_date" placeholder="Tanggal Akhir..." />
            </div>
        </div>
    </div>

    @php
        $tableHeaders = [];
        $tableHeaders[] = 'Pegawai';
        if (Auth::user()->role !== 'leader') {
            $tableHeaders[] = 'Cabang';
        }
        $tableHeaders[] = 'Tanggal';
        $tableHeaders[] = 'Total Jam Kerja';
        $tableHeaders[] = 'Terlambat (menit)';
        $tableHeaders[] = 'Lembur (jam)';
    @endphp

    <x-table :headers="$tableHeaders" :rows="$requests" emptyMessage="Tidak ada data riwayat." fixedHeader="true"
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
                @can('admin')
                    <x-table.cell class="whitespace-nowrap">
                        <div class="flex flex-col items-start">
                            <span class="font-semibold">
                                {{ $request->employee?->branch?->name }}
                            </span>
                            <span>{{ $request->shift_name }}</span>
                        </div>
                    </x-table.cell>
                @endcan
                <x-table.cell class="whitespace-nowrap">
                    <div class="flex flex-col">
                        <span class="font-semibold">{{ $request->date }}</span>
                        <span class="text-sm text-gray-500">{{ $request->clock_in }} -
                            {{ $request->clock_out }}</span>
                    </div>
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    {{ $request->work_hours }}
                    <span class="font-semibold">({{ $request->total_attendances }}x)</span>
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

    <!-- Reprocess Attendance Modal -->
    <flux:modal name="reprocess-attendance-modal" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Proses Ulang Absensi</flux:heading>
            </div>

            <flux:select label="Karyawan" placeholder="Pilih Karyawan" wire:model="reprocessEmployeeId">
                <flux:select.option value="">Pilih Karyawan</flux:select.option>
                @foreach ($employees as $employee)
                    <flux:select.option value="{{ $employee->id }}">
                        {{ $employee->nip }} - {{ $employee->name }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input type="date" label="Tanggal Mulai" placeholder="Pilih Tanggal Mulai"
                wire:model="reprocessStartDate" />

            <flux:input type="date" label="Tanggal Akhir" placeholder="Pilih Tanggal Akhir"
                wire:model="reprocessEndDate" />

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" wire:click="resetReprocessForm" variant="ghost">Batal</flux:button>
                <flux:button type="button" wire:click="reprocessAttendance" variant="primary">Proses Ulang
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
