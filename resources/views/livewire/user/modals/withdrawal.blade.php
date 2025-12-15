<!-- Modal Widrawal -->
<flux:modal name="employee-saving-widrawal-modal" class="md:w-md">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Tarik Tabungan Karyawan</flux:heading>
        </div>

        <flux:input type="number" label="Jumlah Penarikan" placeholder="Masukkan Jumlah Penarikan"
            wire:model="withdrawalAmount" />

        <flux:input type="text" label="Keterangan" placeholder="Masukkan Keterangan (opsional)"
            wire:model="withdrawalDescription" />

        <div class="flex justify-end space-x-2">
            <flux:button type="button" x-on:click="$flux.modal('employee-saving-widrawal-modal').close()"
                variant="filled">Batal</flux:button>
            <flux:button type="button" wire:click="submitWidrawal" variant="primary">Tarik</flux:button>
        </div>
    </div>
</flux:modal>
