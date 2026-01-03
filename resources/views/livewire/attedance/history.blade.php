<?php

use Livewire\Volt\Component;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Url;

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

    // Form properties
    public $isEdit = false;
    public $attendanceId = null;

    #[Rule('required', message: 'Karyawan harus dipilih.')]
    public $employeeId = '';

    #[Rule('required', message: 'Tanggal dan waktu harus diisi.')]
    public $timestamp = '';

    public $deviceSn = '';

    public function mount()
    {
        $this->branches = Branch::select('id', 'name')->get();

        if (Auth::user()->role == 'leader') {
            $this->employees = Employee::where('branch_id', Auth::user()->employee->branch_id)
                ->select('id', 'name')
                ->get();
            return;
        }

        $this->employees = Employee::select('id', 'name', 'nip')->orderBy('name')->get();
    }

    public function create()
    {
        $this->resetForm();
        $this->modal('attendance-form')->show();
    }

    public function edit(Attendance $attendance)
    {
        $this->attendanceId = $attendance->id;
        $this->isEdit = true;

        $this->employeeId = $attendance->employee_id;
        $this->timestamp = $attendance->timestamp;
        $this->deviceSn = $attendance->device_sn;

        $this->modal('attendance-form')->show();
    }

    public function submit()
    {
        $this->validate();

        if (!$this->isEdit) {
            Attendance::create([
                'employee_nip' => collect($this->employees)->firstWhere('id', $this->employeeId)->nip,
                'timestamp' => $this->timestamp,
                'device_sn' => $this->deviceSn,
                'status_scan' => '0',
                'is_processed' => false,
            ]);

            $this->dispatch('alert-shown', message: 'Data absensi berhasil ditambahkan!', type: 'success');
        } else {
            $attendance = Attendance::find($this->attendanceId);
            $attendance->employee_id = $this->employeeId;
            $attendance->timestamp = $this->timestamp;
            $attendance->device_sn = $this->deviceSn;
            $attendance->save();

            $this->dispatch('alert-shown', message: 'Data absensi berhasil diperbarui!', type: 'success');
        }

        $this->modal('attendance-form')->close();
        $this->dispatch('refreshTable');
    }

    public function resetForm()
    {
        $this->attendanceId = null;
        $this->isEdit = false;

        $this->employeeId = '';
        $this->timestamp = '';
        $this->deviceSn = '';
    }

    #[On('refreshTable')]
    public function refreshTable()
    {
        // Table will refresh automatically via Livewire
    }

    /**
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        if (Auth::user()->role == 'leader') {
            return [
                'requests' => Attendance::query()
                    ->with('employee')
                    ->whereHas('employee', function ($q) {
                        $q->where('branch_id', Auth::user()->employee->branch_id);
                    })
                    ->when($this->employeeFilter, function ($q) {
                        $q->where('employee_id', $this->employeeFilter);
                    })
                    ->when($this->start_date, function ($q) {
                        $q->whereDate('timestamp', '>=', $this->start_date);
                    })
                    ->when($this->end_date, function ($q) {
                        $q->whereDate('timestamp', '<=', $this->end_date);
                    })
                    ->latest()
                    ->paginate(10),
            ];
        }

        return [
            'requests' => Attendance::query()
                ->with('employee.branch')
                ->when($this->employeeFilter, function ($q) {
                    $q->whereHas('employee', function ($q2) {
                        $q2->where('id', $this->employeeFilter);
                    });
                })
                ->when($this->branchFilter, function ($q) {
                    $q->whereHas('employee', function ($q2) {
                        $q2->where('branch_id', $this->branchFilter);
                    });
                })
                ->when($this->start_date, function ($q) {
                    $q->whereDate('timestamp', '>=', $this->start_date);
                })
                ->when($this->end_date, function ($q) {
                    $q->whereDate('timestamp', '<=', $this->end_date);
                })
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Riwayat Absensi</h2>
        <button class="text-sm px-2 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer"
            wire:click="create">
            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" />
            Tambah Absensi
        </button>
    </div>

    <!-- Filter Section -->
    <div class="mb-4">
        <form class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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
                {{-- <flux:select wire:model.live="employeeFilter" placeholder="Filter Pegawai...">
                    <flux:select.option value="">Pilih Pegawai</flux:select.option>
                    @foreach ($employees as $employee)
                        <flux:select.option value="{{ $employee->id }}" :selected="$employeeFilter == $employee->id">
                            {{ $employee->name }}
                        </flux:select.option>
                    @endforeach
                </flux:select> --}}
                <x-select-searchable :items="$employees" modelName="employeeFilter" :modelValue="$employeeFilter"
                    displayProperty="name" valueProperty="id" placeholder="Pilih Pegawai"
                    searchPlaceholder="Cari Pegawai..." />
            </div>
            <!-- Filter By Start Date -->
            <div>
                <flux:input type="date" wire:model.live="start_date" placeholder="Tanggal Mulai..." />
            </div>
            <!-- Filter By End Date -->
            <div>
                <flux:input type="date" wire:model.live="end_date" placeholder="Tanggal Akhir..." />
            </div>
        </form>
    </div>

    @php
        $tableHeaders = [];
        $tableHeaders[] = 'Karyawan';
        if (Auth::user()->can('admin')) {
            $tableHeaders[] = 'Cabang';
        }
        $tableHeaders[] = 'Waktu';
        $tableHeaders[] = 'Device SN';
        $tableHeaders[] = 'Aksi';
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
                            <span class="font-mono text-green-600">{{ $request->employee_nip }}</span>
                            <span>{{ $request->employee?->name }}</span>
                        </div>
                    </div>
                </x-table.cell>
                @can('admin')
                    <x-table.cell class="whitespace-nowrap">
                        <div class="flex flex-col items-start">
                            <span class="font-semibold">
                                {{ $request->employee?->branch?->name }}
                            </span>
                        </div>
                    </x-table.cell>
                @endcan
                <x-table.cell class="px-6 py-4 font-medium text-gray-900">
                    {{ $request->timestamp }}
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    {{ $request->device_sn }}
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    @if ($request->is_processed == false)
                        <x-button-tooltip tooltip="Edit data" icon="pencil-square"
                            wire:click="edit({{ $request->id }})"
                            class="text-sm text-yellow-600 px-2 py-1 rounded hover:bg-yellow-100 cursor-pointer"
                            iconClass="w-4 h-4 inline-block -mt-1">
                        </x-button-tooltip>
                    @endif
                </x-table.cell>
            </x-table.row>
        @endforeach
    </x-table>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>

    <!-- Attendance Form Modal -->
    @include('livewire.attedance.history-form')
</div>
