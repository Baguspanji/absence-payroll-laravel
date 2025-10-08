<?php

use Livewire\Volt\Component;
use App\Models\Shift;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

    public $isEdit = false;
    public $shiftId = null;
    #[Rule('required', message: 'Nama harus diisi.')]
    public $name = '';
    #[Rule('required', message: 'Nama harus diisi.')]
    public $clockIn = '';
    public $clockOut = '';

    /**
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        return [
            'requests' => Shift::query()->latest()->paginate(10),
        ];
    }

    public function edit(Shift $shift)
    {
        $this->shiftId = $shift->id;
        $this->isEdit = true;

        $this->name = $shift->name;
        $this->clockIn = $shift->clock_id;
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

            $this->dispatch('alert-shown', message: 'Data device berhasil dibuat!', type: 'success');
        } else {
            $user = Shift::find($this->shiftId);
            $user->name = $this->name;
            $user->clock_id = $this->clockIn;
            $user->clock_out = $this->clockOut;
            $user->save();

            $this->dispatch('alert-shown', message: 'Data device berhasil diperbarui!', type: 'success');
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
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Aturan Shift</h2>
        <button class="text-sm px-2 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer"
            x-on:click="$flux.modal('form-data').show(); $wire.resetForm();">
            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" />
            Tambah Shift
        </button>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Nama Shift</th>
                    <th scope="col" class="px-6 py-3">Jam Masuk</th>
                    <th scope="col" class="px-6 py-3">Jam Pulang</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->name }}</td>
                        <td class="font-mono px-6 py-4">{{ $request->clock_in }}</td>
                        <td class="font-mono px-6 py-4">{{ $request->clock_out }}</td>
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
                            Tidak ada data shift.
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
