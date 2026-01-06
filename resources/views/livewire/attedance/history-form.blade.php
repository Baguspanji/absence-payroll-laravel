<div>
    <!-- Modal Form -->
    <flux:modal name="attendance-form" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $isEdit ? 'Edit Absensi' : 'Tambah Absensi' }}</flux:heading>
            </div>

            {{-- <flux:select label="Karyawan" placeholder="Pilih Karyawan" wire:model="employeeId">
                <flux:select.option value="">Pilih Karyawan</flux:select.option>
                @foreach ($employees as $employee)
                    <flux:select.option value="{{ $employee->id }}">
                        {{ $employee->nip }} - {{ $employee->name }}
                    </flux:select.option>
                @endforeach
            </flux:select> --}}
            <x-select-searchable :items="$employees" modelName="employeeId" :modelValue="$employeeId"
                    displayProperty="name" valueProperty="id" placeholder="Pilih Pegawai"
                    searchPlaceholder="Cari Pegawai..." />

            <flux:input type="datetime-local" label="Tanggal & Waktu" placeholder="Masukkan Tanggal & Waktu"
                wire:model="timestamp" />

            <flux:input label="Device SN" placeholder="Masukkan Device SN" wire:model="deviceSn" />

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" wire:click="submit" variant="primary">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
