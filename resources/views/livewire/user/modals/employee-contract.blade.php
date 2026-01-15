<!-- Modal Employee Contract -->
<flux:modal name="employee-contract" class="md:w-5xl max-w-[80rem]">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Kontrak Karyawan</flux:heading>
            <p class="text-sm text-gray-500">{{ $employeeContractName ?? 'Karyawan' }}</p>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- Left: List Section -->
            <div class="space-y-4">
                @if (!$isEditingContract)
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-lg text-gray-800">Daftar Kontrak</h3>
                        <button type="button" wire:click="createContractForm"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" /> Tambah
                        </button>
                    </div>
                @endif

                @if ($employeeContracts && count($employeeContracts) > 0)
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach ($employeeContracts as $contract)
                            <div class="border border-gray-300 p-3 rounded-lg">
                                <div class="flex items-center justify-between mb-3">
                                    @if ($contract->status == 'active')
                                        <flux:badge color="green" class="uppercase">Aktif</flux:badge>
                                    @elseif ($contract->status == 'inactive')
                                        <flux:badge color="red" class="uppercase">Tidak Aktif</flux:badge>
                                    @elseif ($contract->status == 'expired')
                                        <flux:badge color="yellow" class="uppercase">Kadaluarsa</flux:badge>
                                    @else
                                        <flux:badge class="uppercase">-</flux:badge>
                                    @endif
                                    <span
                                        class="text-gray-500 text-xs">{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->translatedFormat('d F Y') : '-' }}</span>
                                </div>

                                <div class="space-y-2 mb-2">
                                    <div class="text-sm">
                                        <span class="font-medium">Nomor Kontrak:</span>
                                        <span class="text-gray-600">{{ $contract->contract_number }}</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="font-medium">Jenis Kontrak:</span>
                                        <span class="text-gray-600">{{ $contract->contract_type }}</span>
                                    </div>
                                    @if ($contract->start_date && $contract->end_date)
                                        <div class="flex gap-2 items-center text-sm">
                                            <span
                                                class="text-gray-600">{{ \Carbon\Carbon::parse($contract->start_date)->translatedFormat('d F Y') }}</span>
                                            <flux:icon name="arrow-right" class="w-4 h-4 text-gray-400" />
                                            <span
                                                class="text-gray-600">{{ \Carbon\Carbon::parse($contract->end_date)->translatedFormat('d F Y') }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center justify-between border-t pt-2 text-xs text-gray-500">
                                    <span>{{ $contract->created_at?->translatedFormat('d F Y H:i') ?? '-' }}</span>
                                    @if (!$isEditingContract)
                                        <button type="button" wire:click="editContractForm({{ $contract->id }})"
                                            class="text-blue-600 hover:text-blue-800">
                                            <flux:icon name="pencil-square" class="w-4 h-4" />
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @if (!$isEditingContract)
                        <div class="text-center py-8 text-gray-500">
                            <flux:icon name="inbox" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                            <p>Tidak ada data kontrak karyawan</p>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Right: Form Section -->
            <div class="space-y-4 border-l pl-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-lg text-gray-800">
                        @if ($isEditingContract)
                            Edit Kontrak
                        @else
                            Tambah Kontrak
                        @endif
                    </h3>
                    @if ($isEditingContract)
                        <button type="button" wire:click="$set('isEditingContract', false)"
                            class="text-gray-500 hover:text-gray-700">
                            <flux:icon name="x-mark" class="w-5 h-5" />
                        </button>
                    @endif
                </div>

                <div class="space-y-4">
                    <!-- Contract Number -->
                    <div>
                        <flux:input wire:model="contractNumber" label="Nomor Kontrak" type="text"
                            placeholder="Masukkan nomor kontrak" />
                        @error('contractNumber')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Contract Type -->
                    <div>
                        <flux:select wire:model="contractType" label="Jenis Kontrak">
                            <option value="">Pilih Jenis Kontrak</option>
                            <option value="tetap">Tetap</option>
                            <option value="kontrak">Kontrak</option>
                            <option value="magang">Magang</option>
                            <option value="kerjasama">Kerjasama</option>
                        </flux:select>
                        @error('contractType')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Start Date & End Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <flux:input wire:model="contractStartDate" label="Tanggal Mulai" type="date" />
                            @error('contractStartDate')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <flux:input wire:model="contractEndDate" label="Tanggal Berakhir" type="date" />
                            @error('contractEndDate')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <flux:select wire:model="contractStatus" label="Status">
                            <option value="">Pilih Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                            <option value="expired">Kadaluarsa</option>
                        </flux:select>
                        @error('contractStatus')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- File Upload -->
                    <div>
                        <flux:input wire:model="contractFile" label="File Kontrak" type="file"
                            accept=".pdf,.doc,.docx" />
                        @error('contractFile')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                        @if ($contractFilePath && !$contractFile)
                            <p class="text-xs text-gray-500 mt-2">File saat ini: <a href="{{ $contractFilePath }}"
                                    target="_blank" class="text-blue-600 hover:underline">Lihat File</a></p>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-row gap-2 pt-4">
                        <flux:button wire:click="saveContract" variant="primary" size="sm" class="w-xs"
                            icon="check">
                            {{ $isEditingContract ? 'Perbarui' : 'Simpan' }}
                        </flux:button>
                        @if ($isEditingContract)
                            <flux:button wire:click="deleteContract({{ $selectedContractId }})" variant="danger"
                                size="sm" class="w-xs" icon="trash"
                                onclick="return confirm('Apakah Anda yakin ingin menghapus kontrak ini?')">
                                Hapus
                            </flux:button>
                        @endif
                        <flux:button x-on:click="$flux.modal('employee-contract').close()" variant="ghost"
                            size="sm" class="w-xs">
                            Batal
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</flux:modal>
