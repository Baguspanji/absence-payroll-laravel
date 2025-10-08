<?php

use Livewire\Volt\Component;
use App\Models\Branch;
use App\Models\Employee;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

    public $isEdit = false;
    public $branchId = null;
    #[Rule('required', message: 'Nama harus diisi.')]
    public $name = '';
    #[Rule('required', message: 'Nama harus diisi.')]
    public $address = '';
    public $lat = '';
    public $long = '';

    /**
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        return [
            'requests' => Branch::query()->latest()->paginate(10),
        ];
    }

    public function edit(Branch $user)
    {
        $this->branchId = $user->id;
        $this->isEdit = true;

        $this->name = $user->name;
        $this->address = $user->address;
        $latlong = explode(',', $user->latlong);
        if (count(explode(',', $user->latlong)) == 2) {
            $this->lat = $latlong[0];
            $this->long = $latlong[1];
        }

        $this->modal('form-data')->show();
    }

    public function submit()
    {
        $this->validate();

        if (!$this->isEdit) {
            $user = Branch::create([
                'name' => $this->name,
                'address' => $this->address,
                'latlong' => str_replace(',', '.', $this->lat) . ',' . str_replace(',', '.', $this->long),
            ]);

            $this->dispatch('alert-shown', message: 'Data cabang berhasil dibuat!', type: 'success');
        } else {
            $user = Branch::find($this->branchId);
            $user->name = $this->name;
            $user->address = $this->address;
            $user->latlong = str_replace(',', '.', $this->lat) . ',' . str_replace(',', '.', $this->long);
            $user->save();

            $this->dispatch('alert-shown', message: 'Data cabang berhasil diperbarui!', type: 'success');
        }

        $this->modal('form-data')->close();
    }

    public function resetForm()
    {
        $this->branchId = null;
        $this->isEdit = false;

        $this->name = '';
        $this->address = '';
        $this->lat = '';
        $this->long = '';
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Cabang</h2>
        <button class="text-sm px-2 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer"
            x-on:click="$flux.modal('form-data').show(); $wire.resetForm();">
            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" />
            Tambah Cabang
        </button>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Nama Cabang</th>
                    <th scope="col" class="px-6 py-3">Alamat</th>
                    <th scope="col" class="px-6 py-3">Latlong</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->name }}</td>
                        <td class="px-6 py-4">{{ $request->address }}</td>
                        <td class="font-mono px-6 py-4">{{ $request->latlong ?? '-' }}</td>
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
                            Tidak ada data cabang.
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
                <flux:heading size="lg">{{ $isEdit ? 'Edit Cabang' : 'Tambah Cabang' }}</flux:heading>
            </div>

            <flux:input label="Nama" placeholder="Masukkan Nama" wire:model="name" />

            <flux:input label="Alamat" placeholder="Masukkan Alamat" wire:model="address" />

            <div class="flex items-center gap-2">
                <flux:input label="Latitude" placeholder="Masukkan Latitude" wire:model="lat" />
                <flux:input label="Longitude" placeholder="Masukkan Longitude" wire:model="long" />
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" wire:click="submit" variant="primary">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
