<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Schedule;
use App\Models\Shift;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

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

    public $position = '';
    #[Rule('required', message: 'Cabang harus dipilih.')]
    public $branchId = '';

    public $branches = [];
    #[Rule('required', message: 'Cabang harus dipilih.')]
    public $shiftId = '';

    public $shifts = [];

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

    public function detail(User $user)
    {
        $this->userId = $user->id;

        $this->nip = $user->employee?->nip;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->position = $user->employee?->position;
        $this->branchId = $user->employee?->branch_id;

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
        $this->position = $user->employee?->position;
        $this->branchId = $user->employee?->branch_id;
        $this->shiftId = $user->employee?->schedule?->shift_id;

        $this->modal('form-data')->show();
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
            ]);

            Schedule::create([
                'employee_id' => $employee->id,
                'shift_id' => $this->shiftId,
                'date' => now()
            ]);

            $this->dispatch('alert-shown', message: 'Data pengguna berhasil dibuat!', type: 'success');
        } else {
            $user = User::find($this->userId);
            $user->name = $this->name;
            $user->email = $this->email;
            $user->role = $this->role;
            $user->save();

            $employee = Employee::find($user->employee?->id);
            $employee->branch_id = $this->branchId;
            $employee->nip = $this->nip;
            $employee->name = $this->name;
            $employee->position = $this->position;
            $employee->save();

            $schedule = Schedule::where('employee_id', $employee->id)->first();
            $schedule->shift_id = $this->shiftId;
            $schedule->save();

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
        $this->shiftId = '';
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Pengguna</h2>
        <button class="text-sm px-2 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer"
            x-on:click="$flux.modal('form-data').show(); $wire.resetForm();">
            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" />
            Tambah Pengguna
        </button>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">#</th>
                    <th scope="col" class="px-6 py-3">Nama Karyawan</th>
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
                        <td class="font-mono px-6 py-4 text-green-600">{{ $request->employee?->nip }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->employee?->name }}</td>
                        <td class="px-6 py-4">{{ $request->email }}</td>
                        <td class="px-6 py-4">{{ $request->employee?->branch?->name }}</td>
                        <td class="px-6 py-4">{{ $request->employee?->position }}</td>
                        <td class="px-6 py-4 font-semibold">{{ strToUpper($request->role) }}</td>
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
    <flux:modal name="form-data" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' }}</flux:heading>
            </div>

            <flux:input label="Nip/Pin ADMIN" placeholder="Masukkan Nip/Pin ADMIN" wire:model="nip" />

            <flux:input label="Nama" placeholder="Masukkan Nama" wire:model="name" />

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

            <flux:select label="Shift" wire:model="shiftId" placeholder="Pilih Shift...">
                @foreach ($shifts as $item)
                    <flux:select.option value="{{ $item['value'] }}">{{ $item['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input label="Jabatan" placeholder="Masukkan Jabatan" wire:model="position" />

            <div class="flex">
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
                <div class="space-y-4 border-r pr-4">
                    <div class="mb-4">
                        <h3 class="font-semibold text-lg text-gray-800 mb-2">Informasi Karyawan</h3>
                        <div class="grid grid-cols-3 gap-2 text-sm">
                            <div>NIP/PIN</div>
                            <div class="col-span-2">{{ $nip }}</div>

                            <div>Nama</div>
                            <div class="col-span-2">{{ $name }}</div>

                            <div>Email</div>
                            <div class="col-span-2">{{ $email }}</div>

                            <div>Akses</div>
                            <div class="col-span-2"><span
                                    class="text-xs px-2 py-1 rounded-md bg-blue-100 text-blue-800">{{ strToUpper($role) }}</span>
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

                <div class="space-y-4">
                    <div class="mb-4">
                        <h3 class="font-bold text-lg text-gray-800 mb-2">Jadwal Kerja</h3>
                        @php
                            $schedules = $userId
                                ? \App\Models\Schedule::with('shift')->where('employee_id', $userId)->get()
                                : collect([]);
                        @endphp

                        @if ($schedules->count() > 0)
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
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" x-on:click="$flux.modal('detail-data').close()" variant="ghost">
                    Kembali
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
