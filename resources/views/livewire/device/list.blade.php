<?php

use Livewire\Volt\Component;
use App\Models\Device;
use App\Models\Branch;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

    public $isEdit = false;
    public $deviceId = null;
    #[Rule('required', message: 'Nama harus diisi.')]
    public $name = '';
    #[Rule('required', message: 'Nama harus diisi.')]
    public $serialNumber = '';
    public $branchId = '';
    public $branches = [];

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
    }

    /**
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        return [
            'requests' => Device::query()->latest()->paginate(10),
        ];
    }

    public function edit(Device $device)
    {
        $this->branchId = $device->id;
        $this->isEdit = true;

        $this->name = $device->name;
        $this->serialNumber = $device->serial_number;
        $this->branchId = $device->branchId;

        $this->modal('form-data')->show();
    }

    public function submit()
    {
        $this->validate();

        $user = Device::find($this->deviceId);
        $user->name = $this->name;
        // $user->serialNumber = $this->serialNumber;
        $user->branchId = $this->branchId;
        $user->save();

        $this->dispatch('alert-shown', message: 'Data device berhasil diperbarui!', type: 'success');

        $this->modal('form-data')->close();
    }

    public function resetForm()
    {
        $this->deviceId = null;
        $this->isEdit = false;

        $this->name = '';
        $this->serialNumber = '';
        $this->branchId = '';
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Device</h2>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Nama Device</th>
                    <th scope="col" class="px-6 py-3">Serial Number</th>
                    <th scope="col" class="px-6 py-3">Cabang</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->name }}</td>
                        <td class="font-mono px-6 py-4">{{ $request->serial_number }}</td>
                        <td class="px-6 py-4">{{ $request->branch?->name }}</td>
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
                            Tidak ada data device.
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

            <flux:input label="Serial Number" placeholder="Masukkan Serial Number" wire:model="serialNumber" readonly />


            <flux:select label="Cabang" wire:model="branchId" placeholder="Pilih Cabang...">
                @foreach ($branches as $item)
                    <flux:select.option value="{{ $item['value'] }}">{{ $item['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" wire:click="submit" variant="primary">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
