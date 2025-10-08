<?php

use Livewire\Volt\Component;
use App\Models\PayrollComponent;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

    public $isEdit = false;
    public $payrollComponentId = null;
    #[Rule('required', message: 'Nama harus diisi.')]
    public $name = '';
    #[Rule('required', message: 'Nama harus diisi.')]
    public $type = '';
    public $isFixed = false;

    /**
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        return [
            'requests' => PayrollComponent::query()->latest()->paginate(10),
        ];
    }

    public function edit(PayrollComponent $payrollComponent)
    {
        $this->payrollComponentId = $payrollComponent->id;
        $this->isEdit = true;

        $this->name = $payrollComponent->name;
        $this->type = $payrollComponent->type;
        $this->isFixed = $payrollComponent->is_fixed;

        $this->modal('form-data')->show();
    }

    public function submit()
    {
        $this->validate();

        if (!$this->isEdit) {
            PayrollComponent::create([
                'name' => $this->name,
                'type' => $this->type,
                'is_fixed' => $this->isFixed,
            ]);

            $this->dispatch('alert-shown', message: 'Data master gaji berhasil dibuat!', type: 'success');
        } else {
            $user = PayrollComponent::find($this->payrollComponentId);
            $user->name = $this->name;
            $user->type = $this->type;
            $user->is_fixed = $this->isFixed;
            $user->save();

            $this->dispatch('alert-shown', message: 'Data master gaji berhasil diperbarui!', type: 'success');
        }

        $this->modal('form-data')->close();
    }

    public function resetForm()
    {
        $this->payrollComponentId = null;
        $this->isEdit = false;

        $this->name = '';
        $this->type = '';
        $this->isFixed = false;
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Master Gaji</h2>
        <button class="text-sm px-2 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer"
            x-on:click="$flux.modal('form-data').show(); $wire.resetForm();">
            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" />
            Tambah Master Gaji
        </button>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Nama Master</th>
                    <th scope="col" class="px-6 py-3">Tipe</th>
                    <th scope="col" class="px-6 py-3">Sifat Tetap</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->name }}</td>
                        <td class="px-6 py-4">
                            {{ $request->type == 'earning' ? 'Pendapatan' : 'Potongan' }}
                        </td>
                        <td class="px-6 py-4">{{ $request->is_fixed ? 'Ya' : 'Tidak' }}</td>
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
                            Tidak ada data master.
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
                <flux:heading size="lg">{{ $isEdit ? 'Edit Master Gaji' : 'Tambah Master Gaji' }}</flux:heading>
            </div>

            <flux:input label="Nama" placeholder="Masukkan Nama" wire:model="name" />

            <flux:select label="Tipe" wire:model="type" placeholder="Pilih Tipe...">
                <flux:select.option value="earning">Pendapatan</flux:select.option>
                <flux:select.option value="deduction">Potongan</flux:select.option>
            </flux:select>

            <flux:field variant="inline">
                <flux:checkbox wire:model="isFixed" />
                <flux:label>Sifat Gaji Tetap</flux:label>
                <flux:error name="isFixed" />
            </flux:field>

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" wire:click="submit" variant="primary">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
