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
        return [
            'requests' => User::query()
                ->with('employee.branch') // Eager load relasi employee
                ->latest()
                ->paginate(10),
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

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Foto</th>
                    <th scope="col" class="px-6 py-3">Karyawan</th>
                    <th scope="col" class="px-6 py-3">Email</th>
                    <th scope="col" class="px-6 py-3">Cabang</th>
                    <th scope="col" class="px-6 py-3">Jabatan</th>
                    <th scope="col" class="px-6 py-3">Akses</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">
                            @if ($request->employee?->image_url)
                                <img src="{{ $request->employee?->image_url }}" alt="Foto Karyawan"
                                    class="w-10 h-10 rounded-full object-cover" />
                            @else
                                <div
                                    class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                    <flux:icon name="user" class="w-6 h-6" />
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <div class="flex flex-col items-start space-x-3">
                                <span class="font-mono text-green-600">{{ $request->employee?->nip }}</span>
                                <span>{{ $request->employee?->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">{{ $request->email }}</td>
                        <td class="px-6 py-4">{{ $request->employee?->branch?->name }}</td>
                        <td class="px-6 py-4">{{ $request->employee?->position }}</td>
                        <td class="px-6 py-4 text-xs font-bold">
                            @if ($request->role == 'admin')
                                ADMIN
                            @elseif ($request->role == 'leader')
                                KEPALA TOKO
                            @elseif ($request->role == 'employee')
                                KARYAWAN
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4">
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
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button wire:click="detail({{ $request->id }})"
                                class="text-sm text-gray-600 px-2 py-1 rounded hover:bg-gray-100 cursor-pointer">
                                <flux:icon name="eye" class="w-4 h-4 inline-block -mt-1" />
                                Detail
                            </button>
                            <button wire:click="edit({{ $request->id }})"
                                class="text-sm text-yellow-600 px-2 py-1 rounded hover:bg-yellow-100 cursor-pointer">
                                <flux:icon name="pencil-square" class="w-4 h-4 inline-block -mt-1" />
                                Edit
                            </button>
                            <button wire:click="tabungan({{ $request->id }})"
                                class="text-sm text-green-600 px-2 py-1 rounded hover:bg-green-100 cursor-pointer">
                                <flux:icon name="banknotes" class="w-4 h-4 inline-block -mt-1" />
                                Tabungan
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b">
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            Tidak ada data pengguna.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>

    <!-- Modal Form -->
    <flux:modal name="form-data" class="md:w-4xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <flux:heading size="lg">{{ $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' }}</flux:heading>
            </div>

            <div class="md:col-span-2">
                <flux:input label="Nama" placeholder="Masukkan Nama" wire:model="name" />
            </div>

            <flux:input label="Nip/Pin ADMIN" placeholder="Masukkan Nip/Pin ADMIN" wire:model="nip" readonly />

            <flux:input label="Email" placeholder="Masukkan Email" wire:model="email" />

            <flux:select label="Akses" wire:model="role" placeholder="Pilih Akses...">
                <flux:select.option value="admin">ADMIN</flux:select.option>
                <flux:select.option value="leader">KEPALA TOKO</flux:select.option>
                <flux:select.option value="employee">KARYAWAN</flux:select.option>
            </flux:select>

            <flux:select label="Cabang" wire:model="branchId" placeholder="Pilih Cabang...">
                @foreach ($branches as $item)
                    <flux:select.option value="{{ $item['value'] }}">{{ $item['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input label="Jabatan" placeholder="Masukkan Jabatan" wire:model="position" />

            <div class="col-span-2">
                <flux:field label="Shift">
                    <flux:label class="mb-2 block text-sm font-medium text-gray-700">Pilih Shift Karyawan</flux:label>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($shifts as $shift)
                            <flux:checkbox wire:model="shiftIds" value="{{ $shift['value'] }}"
                                label="{{ $shift['label'] }}" />
                        @endforeach
                    </div>
                </flux:field>
            </div>

            <div class="md:col-span-2">
                <flux:input label="Foto Karyawan" type="file" wire:model="photo" />
            </div>

            <div class="flex md:col-span-2">
                <flux:spacer />
                <flux:button type="button" wire:click="submit" variant="primary">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Detail -->
    <flux:modal name="detail-data" class="md:w-4xl">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Detail Pengguna</flux:heading>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div class="space-y-4 pr-4">
                    <div class="mb-4">
                        <h3 class="font-semibold text-lg text-gray-800 mb-2">Informasi Karyawan</h3>
                        <div class="flex md:flex-row-reverse gap-6 justify-between">
                            <div>
                                <img src="{{ $photo }}" alt="Foto Karyawan"
                                    class="w-24 rounded-md object-cover" />
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-sm min-w-sm">
                                <div>NIP/PIN</div>
                                <div class="col-span-2">{{ $nip }}</div>

                                <div>Nama</div>
                                <div class="col-span-2">{{ $name }}</div>

                                <div>Email</div>
                                <div class="col-span-2">{{ $email }}</div>

                                <div>Akses</div>
                                <div class="col-span-2">
                                    <span class="text-xs font-bold px-2 py-1 rounded-md bg-blue-100 text-blue-800">
                                        @if ($role == 'admin')
                                            ADMIN
                                        @elseif ($role == 'leader')
                                            KEPALA TOKO
                                        @elseif ($role == 'employee')
                                            KARYAWAN
                                        @else
                                            -
                                        @endif
                                    </span>
                                </div>

                                <div>Cabang</div>
                                <div class="col-span-2">
                                    @if ($branchId)
                                        {{ collect($branches)->firstWhere('value', $branchId)['label'] ?? '' }}
                                    @endif
                                </div>

                                <div>Jabatan</div>
                                <div class="col-span-2">{{ $position }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="mb-4">
                        <h3 class="font-bold text-lg text-gray-800 mb-2">Jadwal Kerja</h3>

                        @if ($schedules)
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2">Shift</th>
                                            <th class="px-3 py-2">Jam Masuk</th>
                                            <th class="px-3 py-2">Jam Pulang</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($schedules as $schedule)
                                            <tr class="bg-white border-b">
                                                <td class="px-3 py-2">{{ $schedule->shift?->name }}</td>
                                                <td class="px-3 py-2">{{ $schedule->shift?->clock_in }}</td>
                                                <td class="px-3 py-2">{{ $schedule->shift?->clock_out }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="py-4 text-center text-gray-500 bg-gray-50 rounded">
                                Tidak ada jadwal yang ditetapkan
                            </div>
                        @endif
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="mb-4">
                        <h3 class="font-bold text-lg text-gray-800 mb-2">Gaji Kerja</h3>

                        @if (count($payrollComponents) > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2">Nama Gaji</th>
                                            <th class="px-3 py-2">Tipe</th>
                                            <th class="px-3 py-2">Jumlah</th>
                                            <th class="px-3 py-2">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($payrollComponents as $index => $item)
                                            <tr class="bg-white border-b">
                                                <td class="px-3 py-2 whitespace-nowrap">{{ $item->name }}</td>
                                                <td class="px-3 py-2">
                                                    {{ $item->pivot?->type == 'earning' ? 'Pendapatan' : 'Potongan' }}
                                                </td>
                                                <td class="px-3 py-2">Rp
                                                    {{ number_format($item->pivot?->amount, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2">
                                                    <button wire:click="removePayrollComponent({{ $index }})"
                                                        class="text-red-500 hover:text-red-700">
                                                        <flux:icon name="trash" class="w-4 h-4 inline-block" />
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="py-4 text-center text-gray-500 bg-gray-50 rounded">
                                Tidak ada komponen gaji yang ditetapkan
                            </div>
                        @endif

                        <div class="mt-4">
                            <button type="button" class="text-blue-600 hover:text-blue-800"
                                x-on:click="$flux.modal('add-payroll-component').show()">
                                <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" /> Tambah Komponen Gaji
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Add Payroll Component -->
    <flux:modal name="add-payroll-component" class="md:w-md">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Tambah Komponen Gaji</flux:heading>
            </div>

            <flux:select label="Komponen" wire:model="selectedComponent" placeholder="Pilih Komponen Gaji...">
                @foreach ($optionPayrollComponents as $item)
                    <flux:select.option value="{{ $item['value'] }}">{{ $item['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input type="number" label="Jumlah" placeholder="Masukkan Jumlah" wire:model="payrollAmount" />

            <div class="flex justify-end space-x-2">
                <flux:button type="button" wire:click="$dispatch('closeModal', 'add-payroll-component')"
                    variant="filled">Batal</flux:button>
                <flux:button type="button" wire:click="addPayrollComponent" variant="primary">Tambah</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Employee Saving Data -->
    <flux:modal name="employee-saving-data" class="md:min-w-[54rem]">
        <div class="space-y-4">
            <div class="flex pr-8 justify-between items-center">
                <flux:heading size="lg">Data Tabungan Karyawan</flux:heading>
                <flux:button type="button" variant="ghost" size="xs" wire:click="openWidrawalModal"
                    :disabled="!$employeeSaving">
                    Tarik Tabungan
                </flux:button>
            </div>

            <div class="grid grid-cols-1 gap-4">
                @if ($employeeSaving)
                    <div class="mb-4">
                        <div class="flex justify-between items-center">
                            <h3 class="font-semibold text-lg text-gray-800 mb-2">Riwayat Transaksi</h3>
                            <h4 class="text-sm font-medium text-gray-700">
                                Saldo Saat Ini: <span class="font-semibold">Rp
                                    {{ number_format($employeeSaving->balance, 0, ',', '.') }}</span>
                            </h4>
                        </div>
                        @if ($employeeSaving->transactions && $employeeSaving->transactions->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left text-gray-500">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2">Tanggal</th>
                                            <th class="px-3 py-2">Tipe</th>
                                            <th class="px-3 py-2">Jumlah</th>
                                            <th class="px-3 py-2">Saldo</th>
                                            <th class="px-3 py-2">Keterangan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($employeeSaving->transactions as $transaction)
                                            <tr class="bg-white border-b">
                                                <td class="px-3 py-2">
                                                    {{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                                <td class="px-3 py-2">
                                                    <span
                                                        class="px-2 py-1 text-xs rounded-md {{ $transaction->type == 'debit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $transaction->type == 'debit' ? 'Tabungan' : 'Penarikan' }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2">Rp
                                                    {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2">Rp
                                                    {{ number_format($transaction->balance_after, 0, ',', '.') }}</td>
                                                <td class="px-3 py-2">{{ $transaction->description ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="py-4 text-center text-gray-500 bg-gray-50 rounded">
                                Belum ada transaksi tabungan.
                            </div>
                        @endif
                    </div>
                @else
                    <div class="py-4 text-center text-gray-500 bg-gray-50 rounded">
                        Data tabungan karyawan tidak ditemukan.
                    </div>
                @endif
            </div>
        </div>
    </flux:modal>

    <!-- Modal Widrawal -->
    <flux:modal name="employee-saving-widrawal-modal" class="md:w-md">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Tarik Tabungan Karyawan</flux:heading>
            </div>

            <flux:input type="number" label="Jumlah Penarikan" placeholder="Masukkan Jumlah Penarikan"
                wire:model="withdrawalAmount" />

            <flux:input type="text" label="Keterangan" placeholder="Masukkan Keterangan (opsional)"
                wire:model="withdrawalDescription" />

            <div class="flex justify-end space-x-2">
                <flux:button type="button" x-on:click="$flux.modal('employee-saving-widrawal-modal').close()"
                    variant="filled">Batal</flux:button>
                <flux:button type="button" wire:click="submitWidrawal" variant="primary">Tarik</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
