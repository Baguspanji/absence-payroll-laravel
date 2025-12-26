<?php

use Livewire\Volt\Component;
use App\Models\Shift;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $isEdit = false;
    public $shiftId = null;
    #[Rule('required', message: 'Nama harus diisi.')]
    public $name = '';
    #[Rule('required', message: 'Jam masuk harus diisi.')]
    public $clockIn = '';
    #[Rule('required', message: 'Jam pulang harus diisi.')]
    public $clockOut = '';

    /**
     * Mengambil data shift untuk ditampilkan.
     */
    public function with(): array
    {
        $query = Shift::query();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('clock_in', 'like', '%' . $this->search . '%')
                    ->orWhere('clock_out', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'requests' => $query->latest()->paginate(10),
        ];
    }

    public function edit(Shift $shift)
    {
        $this->shiftId = $shift->id;
        $this->isEdit = true;

        $this->name = $shift->name;
        $this->clockIn = $shift->clock_in;
        $this->clockOut = $shift->clock_out;

        $this->modal('form-data')->show();
    }

    public function submit()
    {
        $this->validate();

        if (!$this->isEdit) {
            Shift::create([
                'name' => $this->name,
                'clock_in' => $this->clockIn,
                'clock_out' => $this->clockOut,
            ]);

            $this->dispatch('alert-shown', message: 'Data shift berhasil dibuat!', type: 'success');
        } else {
            $user = Shift::find($this->shiftId);
            $user->name = $this->name;
            $user->clock_in = $this->clockIn;
            $user->clock_out = $this->clockOut;
            $user->save();

            $this->dispatch('alert-shown', message: 'Data shift berhasil diperbarui!', type: 'success');
        }

        $this->modal('form-data')->close();
    }

    public function resetForm()
    {
        $this->shiftId = null;
        $this->isEdit = false;

        $this->name = '';
        $this->clockIn = '';
        $this->clockOut = '';
    }

    public function create()
    {
        $this->resetForm();
        $this->modal('form-data')->show();
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Aturan Shift</h2>
        <button class="text-sm px-2 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer"
            wire:click="create">
            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" />
            Tambah Shift
        </button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search"
            placeholder="Cari nama atau jam shift..."
            icon="magnifying-glass" />
    </div>

    <x-table :headers="['Nama Shift', 'Jam Masuk', 'Jam Pulang', 'Aksi']" :rows="$requests" emptyMessage="Tidak ada data shift." fixedHeader="true"
        maxHeight="540px">
        @foreach ($requests as $request)
            <x-table.row>
                <x-table.cell class="font-medium text-gray-900">
                    {{ $request->name }}
                </x-table.cell>
                <x-table.cell class="font-mono">
                    {{ $request->clock_in }}
                </x-table.cell>
                <x-table.cell class="font-mono">
                    {{ $request->clock_out }}
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
                <flux:heading size="lg">{{ $isEdit ? 'Edit Shift' : 'Tambah Shift' }}</flux:heading>
            </div>

            <flux:input label="Nama" placeholder="Masukkan Nama" wire:model="name" />

            <flux:input type="time" label="Jam Masuk" placeholder="Masukkan Jam Masuk" wire:model="clockIn" />

            <flux:input type="time" label="Jam Pulang" placeholder="Masukkan Jam Pulang" wire:model="clockOut" />

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" wire:click="submit" variant="primary">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
