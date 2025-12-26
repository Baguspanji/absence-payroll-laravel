<?php

use Livewire\Volt\Component;
use App\Models\PayrollComponent;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $isEdit = false;
    public $payrollComponentId = null;
    #[Rule('required', message: 'Nama harus diisi.')]
    public $name = '';
    #[Rule('required', message: 'Tipe harus dipilih.')]
    public $type = '';
    public $isFixed = false;

    /**
     * Mengambil data komponen gaji untuk ditampilkan.
     */
    public function with(): array
    {
        $query = PayrollComponent::query();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'requests' => $query->orderBy('type', 'asc')->latest()->paginate(10),
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

    public function create()
    {
        $this->resetForm();
        $this->modal('form-data')->show();
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Master Gaji</h2>
        <button class="text-sm px-2 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer"
            wire:click="create">
            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" />
            Tambah Master Gaji
        </button>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search"
            placeholder="Cari nama komponen gaji..."
            icon="magnifying-glass" />
    </div>

    <x-table :headers="['Nama Master', 'Tipe', 'Sifat Gaji', 'Aksi']" :rows="$requests" emptyMessage="Tidak ada data master." fixedHeader="true"
        maxHeight="540px">
        @foreach ($requests as $request)
            <x-table.row>
                <x-table.cell class="font-medium text-gray-900">
                    {{ $request->name }}
                </x-table.cell>
                <x-table.cell>
                    @if ($request->type == 'earning')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Pendapatan
                        </span>
                    @elseif ($request->type == 'deduction')
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Potongan
                        </span>
                    @else
                        -
                    @endif
                </x-table.cell>
                <x-table.cell>
                    @if ($request->is_fixed)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Tetap
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Harian
                        </span>
                    @endif
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap w-[10%]">
                    @if ($request->type == null)
                        <button type="button"
                            class="text-sm text-gray-600 px-2 py-1 rounded hover:bg-gray-100 cursor-not-allowed"
                            disabled>
                            <flux:icon name="lock-closed" class="w-4 h-4 inline-block -mt-1" />
                        </button>
                    @else
                        <x-button-tooltip tooltip="Edit data" icon="pencil-square" wire:click="edit({{ $request->id }})"
                            class="text-sm text-yellow-600 px-2 py-1 rounded hover:bg-yellow-100 cursor-pointer"
                            iconClass="w-4 h-4 inline-block -mt-1">
                        </x-button-tooltip>
                    @endif
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
