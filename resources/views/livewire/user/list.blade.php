<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Employee;
use App\Models\EmployeeSaving;
use App\Models\Branch;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\PayrollComponent;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filterBranch = '';

    public $isEdit = false;
    public $userId = null;
    #[Rule('required', message: 'Nama harus diisi.')]
    public $nip = '';
    #[Rule('required', message: 'Nama harus diisi.')]
    public $name = '';
    #[Rule('required', message: 'Email harus diisi.')]
    public $email = '';
    #[Rule('required', message: 'Akses harus dipilih.')]
    public $role = '';

    #[Rule('nullable', 'image', 'max:2048', message: 'Foto harus berupa gambar dengan ukuran maksimal 2MB.')]
    public $photo = null;

    public $position = '';
    #[Rule('required', message: 'Cabang harus dipilih.')]
    public $branchId = '';

    public $branches = [];
    #[Rule('required', message: 'Cabang harus dipilih.')]
    public $shiftIds = [];

    public $shifts = [];

    public $schedules = [];

    public $withdrawalAmount = 0;
    public $withdrawalDescription = '';

    public $payrollComponents = [];

    public $optionPayrollComponents = [];

    public ?EmployeeSaving $employeeSaving = null;

    public function mount()
    {
        $this->branches = Branch::get()
            ->map(function ($branch) {
                return [
                    'value' => $branch->id,
                    'label' => $branch->name,
                    'description' => $branch->address,
                ];
            })
            ->toArray();

        $this->shifts = Shift::get()
            ->map(function ($shift) {
                return [
                    'value' => $shift->id,
                    'label' => $shift->name . '(' . $shift->clock_in . ' - ' . $shift->clock_out . ')',
                ];
            })
            ->toArray();

        $this->optionPayrollComponents = PayrollComponent::get()
            ->map(function ($component) {
                return [
                    'value' => $component->id,
                    'label' => $component->name . ' (' . ($component->type == 'earning' ? 'Pendapatan' : 'Potongan') . ')',
                    'description' => $component->description,
                ];
            })
            ->toArray();
    }

    /**
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        $query = User::query()
            ->with('employee.branch');

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhereHas('employee', function ($eq) {
                        $eq->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('nip', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Apply branch filter
        if ($this->filterBranch) {
            $query->whereHas('employee', function ($eq) {
                $eq->where('branch_id', $this->filterBranch);
            });
        }

        return [
            'requests' => $query->latest()->paginate(15),
        ];
    }

    // Add these properties
    public $selectedComponent = null;
    public $payrollAmount = 0;

    // Add these methods
    public function addPayrollComponent()
    {
        $this->validate([
            'selectedComponent' => 'required',
            'payrollAmount' => 'required|numeric|min:0',
        ]);

        $component = PayrollComponent::find($this->selectedComponent);

        if (!$component) {
            return;
        }

        // Check if employee exists and user exists
        if (!$this->userId) {
            return;
        }

        $user = User::find($this->userId);
        $employee = $user->employee;

        // Attach the component to employee
        $employee->payrollComponents()->attach($component->id, [
            'amount' => $this->payrollAmount,
        ]);

        // Refresh the payroll components
        $this->payrollComponents = $employee->payrollComponents()->get();

        // Reset form
        $this->selectedComponent = null;
        $this->payrollAmount = 0;

        // Close modal
        $this->dispatch('closeModal', 'add-payroll-component');
        $this->dispatch('alert-shown', message: 'Komponen gaji berhasil ditambahkan!', type: 'success');
        $this->modal('add-payroll-component')->close();
    }

    public function removePayrollComponent($index)
    {
        if (!isset($this->payrollComponents[$index])) {
            return;
        }

        $component = $this->payrollComponents[$index];

        // Check if employee exists and user exists
        if (!$this->userId) {
            return;
        }

        $user = User::find($this->userId);
        $employee = $user->employee;

        // Detach the component from employee
        $employee->payrollComponents()->detach($component->id);

        // Refresh the payroll components
        $this->payrollComponents = $employee->payrollComponents()->get();

        $this->dispatch('alert-shown', message: 'Komponen gaji berhasil dihapus!', type: 'success');
    }

    public function create()
    {
        $this->resetForm();

        $employee = new Employee();
        $this->nip = $employee->generateNip();

        $this->modal('form-data')->show();
    }

    public function detail(User $user)
    {
        $user->load(['employee.schedules', 'employee.payrollComponents']);

        $this->userId = $user->id;

        $this->nip = $user->employee?->nip;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;

        // mount employee image
        $this->photo = $user->employee?->image_url;

        $this->position = $user->employee?->position;
        $this->branchId = $user->employee?->branch_id;
        $this->schedules = $user->employee?->schedules;

        $this->payrollComponents = $user->employee?->payrollComponents;

        $this->modal('detail-data')->show();
    }

    public function edit(User $user)
    {
        $this->userId = $user->id;
        $this->isEdit = true;

        $this->nip = $user->employee?->nip;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;

        // mount employee image
        // $this->photo = $user->employee?->image_url;

        $this->position = $user->employee?->position;
        $this->branchId = $user->employee?->branch_id;
        $this->shiftIds = $user->employee?->schedules?->pluck('shift_id')->toArray();

        $this->modal('form-data')->show();
    }

    public function tabungan(User $user)
    {
        $this->employeeSaving = EmployeeSaving::with('transactions')->where('employee_id', $user->employee?->id)->first();

        $this->modal('employee-saving-data')->show();
    }

    public function openWidrawalModal()
    {
        $this->withdrawalAmount = 0;
        $this->withdrawalDescription = '';

        $this->modal('employee-saving-widrawal-modal')->show();
    }

    public function submitWidrawal()
    {
        $this->validate([
            'withdrawalAmount' => 'required|numeric|min:1|max:' . ($this->employeeSaving ? $this->employeeSaving->balance : 0),
            'withdrawalDescription' => 'nullable|string|max:255',
        ]);

        // Process the withdrawal
        $savingService = app()->make(\App\Services\EmployeeSavingService::class);
        $savingService->withdraw($this->employeeSaving->employee, $this->withdrawalAmount, $this->withdrawalDescription ?: 'Penarikan Tabungan ' . now()->translatedFormat('F Y'), null);

        $this->dispatch('alert-shown', message: 'Penarikan tabungan berhasil dilakukan!', type: 'success');
        $this->modal('employee-saving-widrawal-modal')->close();
    }

    public function updateStatus(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();

        $this->dispatch('alert-shown', message: 'Status berhasil diperbarui!', type: 'success');
    }

    public function submit()
    {
        $this->validate();

        $imageUrl = null;
        if ($this->photo) {
            $imagePath = $this->photo->store('employee_photos', 'public');

            // Generate unique image URL
            $imageUrl = '/storage/employee_photos/' . uniqid() . '_' . time() . '.' . $this->photo->getClientOriginalExtension();

            // Compress the uploaded image
            $fullPath = storage_path('app/public/' . $imagePath);
            compressImage($fullPath, storage_path('app/public/' . str_replace('/storage', '', $imageUrl)), 75);

            // delete original image
            Storage::disk('public')->delete($imagePath);
        }

        if (!$this->isEdit) {
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make('password'),
                'role' => $this->role,
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'branch_id' => $this->branchId,
                'nip' => $this->nip,
                'name' => $this->name,
                'position' => $this->position,
                'image_url' => $imageUrl,
            ]);

            foreach ($this->shiftIds as $shiftId) {
                Schedule::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $shiftId,
                ]);
            }

            $this->dispatch('alert-shown', message: 'Data pengguna berhasil dibuat!', type: 'success');
        } else {
            $user = User::find($this->userId);
            $user->name = $this->name;
            $user->email = $this->email;
            $user->role = $this->role;
            $user->save();

            $employee = Employee::find($user->employee?->id);
            $employee->branch_id = $this->branchId;
            $employee->name = $this->name;
            $employee->position = $this->position;
            if ($imageUrl) {
                $employee->image_url = $imageUrl;
            }
            $employee->save();

            Schedule::where('employee_id', $employee->id)->delete();

            foreach ($this->shiftIds as $shiftId) {
                Schedule::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $shiftId,
                ]);
            }

            $this->dispatch('alert-shown', message: 'Data pengguna berhasil diperbarui!', type: 'success');
        }

        $this->modal('form-data')->close();
    }

    public function resetForm()
    {
        $this->userId = null;
        $this->isEdit = false;

        $this->nip = '';
        $this->name = '';
        $this->email = '';
        $this->role = '';
        $this->branchId = '';
        $this->position = '';
        $this->shiftIds = [];
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Pengguna</h2>
        <button class="text-sm px-2 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer"
            wire:click="create">
            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" />
            Tambah Pengguna
        </button>
    </div>

    <div class="flex gap-2 mb-4">
        <div class="flex-1">
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="Cari nama, NIP, atau email..."
                icon="magnifying-glass" />
        </div>
        <div class="w-64">
            <flux:select wire:model.live="filterBranch" placeholder="Semua Cabang">
                <option value="">Semua Cabang</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch['value'] }}">{{ $branch['label'] }}</option>
                @endforeach
            </flux:select>
        </div>
    </div>

    <x-table :headers="['Karyawan', 'Cabang', 'Akses', 'Status', 'Aksi']" :rows="$requests" emptyMessage="Tidak ada data pengguna." fixedHeader="true"
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
                            <span class="text-sm text-gray-500">{{ $request->email }}</span>
                        </div>
                    </div>
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    <div class="flex flex-col items-start">
                        <span class="font-semibold">
                            {{ $request->employee?->branch?->name }}
                        </span>
                        <span>
                            {{ $request->employee?->position }}
                        </span>
                    </div>
                </x-table.cell>
                <x-table.cell class="text-xs font-bold">
                    @if ($request->role == 'admin')
                        ADMIN
                    @elseif ($request->role == 'leader')
                        KEPALA TOKO
                    @elseif ($request->role == 'employee')
                        KARYAWAN
                    @else
                        -
                    @endif
                </x-table.cell>
                <x-table.cell>
                    @if ($request->is_active)
                        <span class="text-xs text-white px-2 py-1.5 bg-green-600 rounded-md cursor-pointer"
                            wire:click="updateStatus({{ $request->id }})">
                            Aktif
                        </span>
                    @else
                        <span class="text-xs text-white px-2 py-1.5 bg-red-400 rounded-md cursor-pointer"
                            wire:click="updateStatus({{ $request->id }})">
                            Tidak Aktif
                        </span>
                    @endif
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap w-[10%]">
                    <x-button-tooltip tooltip="Lihat detail" icon="eye" wire:click="detail({{ $request->id }})"
                        class="text-sm text-gray-600 px-2 py-1 rounded hover:bg-gray-100 cursor-pointer"
                        iconClass="w-4 h-4 inline-block -mt-1">
                    </x-button-tooltip>
                    <x-button-tooltip tooltip="Edit data" icon="pencil-square" wire:click="edit({{ $request->id }})"
                        class="text-sm text-yellow-600 px-2 py-1 rounded hover:bg-yellow-100 cursor-pointer"
                        iconClass="w-4 h-4 inline-block -mt-1">
                    </x-button-tooltip>
                    <x-button-tooltip tooltip="Kelola tabungan" icon="banknotes"
                        wire:click="tabungan({{ $request->id }})"
                        class="text-sm text-green-600 px-2 py-1 rounded hover:bg-green-100 cursor-pointer"
                        iconClass="w-4 h-4 inline-block -mt-1">
                    </x-button-tooltip>
                </x-table.cell>
            </x-table.row>
        @endforeach
    </x-table>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>

    @include('livewire.user.modals.form')
    @include('livewire.user.modals.detail')
    @include('livewire.user.modals.payroll-component')
    @include('livewire.user.modals.employee-saving')
    @include('livewire.user.modals.withdrawal')
</div>
