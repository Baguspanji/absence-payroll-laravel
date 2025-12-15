<!-- Modal Detail -->
<flux:modal name="detail-data" class="md:w-4xl">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Detail Pengguna</flux:heading>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <div class="space-y-4 pr-4">
                <div class="mb-4">
                    <h3 class="font-semibold text-lg text-gray-800 mb-2">Informasi Karyawan</h3>
                    <div class="flex md:flex-row-reverse gap-6 justify-between">
                        <div>
                            <img src="{{ $photo }}" alt="Foto Karyawan"
                                class="w-24 rounded-md object-cover" />
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-sm min-w-sm">
                            <div>NIP/PIN</div>
                            <div class="col-span-2">{{ $nip }}</div>

                            <div>Nama</div>
                            <div class="col-span-2">{{ $name }}</div>

                            <div>Email</div>
                            <div class="col-span-2">{{ $email }}</div>

                            <div>Akses</div>
                            <div class="col-span-2">
                                <span class="text-xs font-bold px-2 py-1 rounded-md bg-blue-100 text-blue-800">
                                    @if ($role == 'admin')
                                        ADMIN
                                    @elseif ($role == 'leader')
                                        KEPALA TOKO
                                    @elseif ($role == 'employee')
                                        KARYAWAN
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>

                            <div>Cabang</div>
                            <div class="col-span-2">
                                @if ($branchId)
                                    {{ collect($branches)->firstWhere('value', $branchId)['label'] ?? '' }}
                                @endif
                            </div>

                            <div>Jabatan</div>
                            <div class="col-span-2">{{ $position }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="mb-4">
                    <h3 class="font-bold text-lg text-gray-800 mb-2">Jadwal Kerja</h3>

                    <x-table :headers="['Shift', 'Jam Masuk', 'Jam Pulang']" :rows="$schedules" emptyMessage="Tidak ada jadwal yang ditetapkan">
                        @foreach ($schedules as $schedule)
                            <x-table.row>
                                <x-table.cell class="px-3 py-2">{{ $schedule->shift?->name }}</x-table.cell>
                                <x-table.cell class="px-3 py-2">{{ $schedule->shift?->clock_in }}</x-table.cell>
                                <x-table.cell class="px-3 py-2">{{ $schedule->shift?->clock_out }}</x-table.cell>
                            </x-table.row>
                        @endforeach
                    </x-table>
                </div>
            </div>

            <div class="space-y-4">
                <div class="mb-4">
                    <h3 class="font-bold text-lg text-gray-800 mb-2">Gaji Kerja</h3>

                    <x-table :headers="['Nama Gaji', 'Tipe', 'Jumlah', 'Aksi']" :rows="$payrollComponents"
                        emptyMessage="Tidak ada komponen gaji yang ditetapkan">
                        @foreach ($payrollComponents as $index => $item)
                            <x-table.row>
                                <x-table.cell
                                    class="px-3 py-2 whitespace-nowrap">{{ $item->name }}</x-table.cell>
                                <x-table.cell class="px-3 py-2">
                                    {{ $item->pivot?->type == 'earning' ? 'Pendapatan' : 'Potongan' }}
                                </x-table.cell>
                                <x-table.cell class="px-3 py-2">Rp
                                    {{ number_format($item->pivot?->amount, 0, ',', '.') }}</x-table.cell>
                                <x-table.cell class="px-3 py-2">
                                    <button wire:click="removePayrollComponent({{ $index }})"
                                        class="text-red-500 hover:text-red-700">
                                        <flux:icon name="trash" class="w-4 h-4 inline-block" />
                                    </button>
                                </x-table.cell>
                            </x-table.row>
                        @endforeach
                    </x-table>

                    <div class="mt-4">
                        <button type="button" class="text-blue-600 hover:text-blue-800"
                            x-on:click="$flux.modal('add-payroll-component').show()">
                            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" /> Tambah Komponen Gaji
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</flux:modal>
