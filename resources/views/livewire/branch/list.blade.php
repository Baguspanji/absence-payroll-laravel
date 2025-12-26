<?php

use Livewire\Volt\Component;
use App\Models\Branch;
use App\Models\Employee;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $isEdit = false;
    public $branchId = null;
    #[Rule('required', message: 'Nama harus diisi.')]
    public $name = '';
    #[Rule('required', message: 'Alamat harus diisi.')]
    public $address = '';
    public $lat = '';
    public $long = '';

    /**
     * Mengambil data cabang untuk ditampilkan.
     */
    public function with(): array
    {
        $query = Branch::query();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('address', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'requests' => $query->latest()->paginate(10),
        ];
    }

    public function edit(Branch $branch)
    {
        $this->branchId = $branch->id;
        $this->isEdit = true;

        $this->name = $branch->name;
        $this->address = $branch->address;
        $latlong = explode(',', $branch->latlong);
        if (count(explode(',', $branch->latlong)) == 2) {
            $this->lat = $latlong[0];
            $this->long = $latlong[1];
        }

        $this->modal('form-data')->show();
    }

    public function submit()
    {
        $this->validate();

        if (!$this->isEdit) {
            $device = Branch::create([
                'name' => $this->name,
                'address' => $this->address,
                'latlong' => str_replace(',', '.', $this->lat) . ',' . str_replace(',', '.', $this->long),
            ]);

            $this->dispatch('alert-shown', message: 'Data cabang berhasil dibuat!', type: 'success');
        } else {
            $device = Branch::find($this->branchId);
            $device->name = $this->name;
            $device->address = $this->address;
            $device->latlong = str_replace(',', '.', $this->lat) . ',' . str_replace(',', '.', $this->long);
            $device->save();

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

    public function create()
    {
        $this->resetForm();
        $this->modal('form-data')->show();
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Cabang</h2>
        <button class="text-sm px-2 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer"
            wire:click="create">
            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" />
            Tambah Cabang
        </button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search"
            placeholder="Cari nama atau alamat cabang..."
            icon="magnifying-glass" />
    </div>

    <x-table :headers="['Nama Cabang', 'Alamat', 'Latlong', 'Aksi']" :rows="$requests" emptyMessage="Tidak ada data cabang." fixedHeader="true"
        maxHeight="540px">
        @foreach ($requests as $request)
            <x-table.row>
                <x-table.cell class="font-medium text-gray-900">
                    {{ $request->name }}
                </x-table.cell>
                <x-table.cell>
                    {{ $request->address }}
                </x-table.cell>
                <x-table.cell class="font-mono">
                    {{ $request->latlong ?? '-' }}
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap w-[10%]">
                    <x-button-tooltip tooltip="Edit data" icon="pencil-square" wire:click="edit({{ $request->id }})"
                        class="text-sm text-yellow-600 px-2 py-1 rounded hover:bg-yellow-100 cursor-pointer"
                        iconClass="w-4 h-4 inline-block -mt-1">
                    </x-button-tooltip>
                </x-table.cell>
            </x-table.row>
        @endforeach
    </x-table>

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
