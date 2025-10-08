<?php

use Livewire\Volt\Component;
use App\Models\User;
use App\Models\Employee;
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

    public function edit(User $user)
    {
        $this->userId = $user->id;
        $this->isEdit = true;

        $this->nip = $user->employee?->nip;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->position = $user->employee?->position;

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

            Employee::create([
                'user_id' => $user->id,
                'branch_id' => 1,
                'nip' => $this->nip,
                'name' => $this->name,
                'position' => $this->position,
            ]);

            $this->dispatch('alert-shown', message: 'Data pengguna berhasil dibuat!', type: 'success');
        } else {
            $user = User::find($this->userId);
            $user->name = $this->name;
            $user->email = $this->email;
            $user->role = $this->role;
            $user->save();

            $employee = Employee::find($user->employee?->id);
            $employee->nip = $this->nip;
            $employee->name = $this->name;
            $employee->position = $this->position;
            $employee->save();

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
        $this->position = '';
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
                        <td class="px-6 py-4 space-x-2">
                            <button wire:click="edit({{ $request->id }})"
                                class="text-sm text-yellow-600 px-2 py-1 rounded hover:bg-yellow-100">
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

            <flux:input label="Jabatan" placeholder="Masukkan Jabatan" wire:model="position" />

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" wire:click="submit" variant="primary">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
