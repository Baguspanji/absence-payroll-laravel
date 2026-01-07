<!-- Modal Employee History Movement -->
<flux:modal name="employee-history-movement" class="md:w-5xl max-w-[80rem]">
    <div class="space-y-4">
        <div>
            <flux:heading size="lg">Riwayat Pergerakan Karyawan</flux:heading>
            <p class="text-sm text-gray-500">{{ $employeeHistoryMovementName ?? 'Karyawan' }}</p>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <!-- Left: List Section -->
            <div class="space-y-4">
                @if (!$isEditingMovement)
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-lg text-gray-800">Daftar Pergerakan</h3>
                        <button type="button" wire:click="createMovementForm"
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            <flux:icon name="plus" class="w-4 h-4 inline-block -mt-1" /> Tambah
                        </button>
                    </div>
                @endif

                @if ($employeeHistoryMovements && count($employeeHistoryMovements) > 0)
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @foreach ($employeeHistoryMovements as $movement)
                            <div class="border border-gray-300 p-3 rounded-lg">
                                <div class="flex items-center justify-between mb-3">
                                    @if ($movement->movement_type == 'rotation')
                                        <flux:badge color="blue" class="uppercase">Rotasi</flux:badge>
                                    @elseif ($movement->movement_type == 'promotion')
                                        <flux:badge color="green" class="uppercase">Promosi</flux:badge>
                                    @elseif ($movement->movement_type == 'demotion')
                                        <flux:badge color="red" class="uppercase">Demosi</flux:badge>
                                    @else
                                        <flux:badge class="uppercase">-</flux:badge>
                                    @endif
                                    <span
                                        class="text-gray-500 text-xs">{{ $movement->effective_date?->translatedFormat('d F Y') ?? '-' }}</span>
                                </div>

                                @if ($movement->from_branch_id && $movement->to_branch_id)
                                    <div class="flex gap-2 items-center text-sm mb-2">
                                        <span class="text-gray-600"><span class="font-medium">Dari:</span>
                                            {{ $movement->fromBranch?->name ?? '-' }}</span>
                                        <flux:icon name="arrow-right" class="w-4 h-4 text-gray-400" />
                                        <span class="text-gray-600"><span class="font-medium">Ke:</span>
                                            {{ $movement->toBranch?->name ?? '-' }}</span>
                                    </div>
                                @endif

                                @if ($movement->from_branch_id && !$movement->to_branch_id)
                                    <div class="flex gap-2 items-center text-sm mb-2">
                                        <span class="text-gray-600">{{ $movement->fromBranch?->name ?? '-' }}</span>
                                    </div>
                                @endif

                                @if ($movement->from_position && $movement->to_position)
                                    <div class="flex gap-2 items-center text-sm mb-2">
                                        <span class="text-gray-600"><span class="font-medium">Dari:</span>
                                            {{ $movement->from_position }}</span>
                                        <flux:icon name="arrow-right" class="w-4 h-4 text-gray-400" />
                                        <span class="text-gray-600"><span class="font-medium">Ke:</span>
                                            {{ $movement->to_position }}</span>
                                    </div>
                                @endif

                                @if ((!$movement->from_position && $movement->to_position) && (!$movement->from_branch_id && $movement->to_branch_id))
                                    <div class="flex gap-2 items-center text-sm mb-2">
                                        <span class="text-gray-600">{{ $movement->toBranch?->name ?? '-' }}</span>
                                        <flux:icon name="at-symbol" class="w-4 h-4 text-gray-400" />
                                        <span class="text-gray-600">{{ $movement->to_position }}</span>
                                    </div>
                                @endif

                                @if ($movement->notes)
                                    <p class="text-sm text-gray-700 border-t pt-2 mb-2">{{ $movement->notes }}</p>
                                @endif

                                <div class="flex items-center justify-between border-t pt-2 text-xs text-gray-500">
                                    <span>{{ $movement->created_at?->translatedFormat('d F Y H:i') ?? '-' }}</span>
                                    @if (!$isEditingMovement)
                                        <button type="button" wire:click="editMovementForm({{ $movement->id }})"
                                            class="text-blue-600 hover:text-blue-800">
                                            <flux:icon name="pencil-square" class="w-4 h-4" />
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    @if (!$isEditingMovement)
                        <div class="text-center py-8 text-gray-500">
                            <flux:icon name="inbox" class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                            <p>Tidak ada riwayat pergerakan karyawan</p>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Right: Form Section -->
            <div class="space-y-4 border-l pl-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-lg text-gray-800">
                        @if ($isEditingMovement)
                            Edit Riwayat Pergerakan
                        @else
                            Tambah Riwayat Pergerakan
                        @endif
                    </h3>
                    @if ($isEditingMovement)
                        <button type="button" wire:click="$set('isEditingMovement', false)"
                            class="text-gray-500 hover:text-gray-700">
                            <flux:icon name="x-mark" class="w-5 h-5" />
                        </button>
                    @endif
                </div>

                <div class="space-y-4">
                    <!-- Movement Type -->
                    <div>
                        <flux:select wire:model.live="movementType" label="Jenis Pergerakan">
                            <option value="">Pilih Jenis Pergerakan</option>
                            <option value="rotation">Rotasi</option>
                            <option value="promotion">Promosi</option>
                            <option value="demotion">Demosi</option>
                        </flux:select>
                    </div>

                    <!-- Branch Transfer Fields -->
                    @if (in_array($movementType, ['rotation', 'promotion', 'demotion']))
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <flux:select wire:model="fromBranchId" label="Dari Cabang">
                                        <option value="">Pilih Cabang</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch['value'] }}">{{ $branch['label'] }}</option>
                                        @endforeach
                                    </flux:select>
                                    @error('fromBranchId')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <flux:select wire:model="toBranchId" label="Ke Cabang">
                                        <option value="">Pilih Cabang</option>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch['value'] }}">{{ $branch['label'] }}</option>
                                        @endforeach
                                    </flux:select>
                                    @error('toBranchId')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Position Change Fields -->
                    @if (in_array($movementType, ['promotion', 'demotion']))
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <flux:input wire:model="fromPosition" label="Dari Jabatan" type="text"
                                        placeholder="Masukkan jabatan awal" />
                                    @error('fromPosition')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <flux:input wire:model="toPosition" label="Ke Jabatan" type="text"
                                        placeholder="Masukkan jabatan baru" />
                                    @error('toPosition')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Effective Date -->
                    <div>
                        <flux:input wire:model="effectiveDate" label="Tanggal Efektif" type="date" />
                        @error('effectiveDate')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div>
                        <flux:textarea wire:model="movementNotes" label="Catatan"
                            placeholder="Tambahkan catatan (opsional)" rows="3" />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-row gap-2 pt-4">
                        <flux:button wire:click="saveMovement" variant="primary" size="sm" class="w-xs"
                            icon="check">
                            {{ $isEditingMovement ? 'Perbarui' : 'Simpan' }}
                        </flux:button>
                        @if ($isEditingMovement)
                            <flux:button wire:click="deleteMovement({{ $selectedMovementId }})" variant="danger"
                                size="sm" class="w-xs" icon="trash"
                                onclick="return confirm('Apakah Anda yakin ingin menghapus riwayat pergerakan ini?')">
                                Hapus
                            </flux:button>
                        @endif
                        <flux:button wire:click="$set('isEditingMovement', false); resetMovementForm()"
                            variant="ghost" size="sm" class="w-xs">
                            Batal
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</flux:modal>
