<!-- Modal Form -->
<flux:modal name="form-data" class="md:w-4xl">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <flux:heading size="lg">{{ $isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' }}</flux:heading>
        </div>

        <div class="md:col-span-2">
            <flux:input label="Nama" placeholder="Masukkan Nama" wire:model="name" />
        </div>

        <flux:input label="Nip/Pin ADMIN" placeholder="Masukkan Nip/Pin ADMIN" wire:model="nip" readonly />

        <flux:input label="Email" placeholder="Masukkan Email" wire:model="email" />

        <flux:select label="Akses" wire:model="role" placeholder="Pilih Akses...">
            <flux:select.option value="admin">ADMIN</flux:select.option>
            <flux:select.option value="leader">KEPALA TOKO</flux:select.option>
            <flux:select.option value="employee">KARYAWAN</flux:select.option>
        </flux:select>

        <flux:select label="Cabang" wire:model="branchId" placeholder="Pilih Cabang...">
            @foreach ($branches as $item)
                <flux:select.option value="{{ $item['value'] }}">{{ $item['label'] }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input label="Jabatan" placeholder="Masukkan Jabatan" wire:model="position" />

        <div class="col-span-2">
            <flux:field label="Shift">
                <flux:label class="mb-2 block text-sm font-medium text-gray-700">Pilih Shift Karyawan</flux:label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($shifts as $shift)
                        <flux:checkbox wire:model="shiftIds" value="{{ $shift['value'] }}"
                            label="{{ $shift['label'] }}" />
                    @endforeach
                </div>
            </flux:field>
        </div>

        <div class="md:col-span-2">
            <flux:input label="Foto Karyawan" type="file" wire:model="photo" />
        </div>

        <div class="flex md:col-span-2">
            <flux:spacer />
            <flux:button type="button" wire:click="submitEmployee" variant="primary">Simpan</flux:button>
        </div>
    </div>
</flux:modal>
