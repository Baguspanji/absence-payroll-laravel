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
