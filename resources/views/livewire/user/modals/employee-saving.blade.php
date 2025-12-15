<!-- Modal Employee Saving Data -->
<flux:modal name="employee-saving-data" class="md:min-w-[54rem]">
    <div class="space-y-4">
        <div class="flex pr-8 justify-between items-center">
            <flux:heading size="lg">Data Tabungan Karyawan</flux:heading>
            <flux:button type="button" variant="ghost" size="xs" wire:click="openWidrawalModal"
                :disabled="!$employeeSaving">
                Tarik Tabungan
            </flux:button>
        </div>

        <div class="grid grid-cols-1 gap-4">
            @if ($employeeSaving)
                <div class="mb-4">
                    <div class="flex justify-between items-center">
                        <h3 class="font-semibold text-lg text-gray-800 mb-2">Riwayat Transaksi</h3>
                        <h4 class="text-sm font-medium text-gray-700">
                            Saldo Saat Ini: <span class="font-semibold">Rp
                                {{ number_format($employeeSaving->balance, 0, ',', '.') }}</span>
                        </h4>
                    </div>
                    <x-table :headers="['Tanggal', 'Tipe', 'Jumlah', 'Saldo', 'Keterangan']" :rows="$employeeSaving->transactions ?? []" emptyMessage="Belum ada transaksi tabungan.">
                        @foreach ($employeeSaving->transactions as $transaction)
                            <x-table.row>
                                <x-table.cell class="px-3 py-2">
                                    {{ $transaction->created_at->format('d/m/Y H:i') }}</x-table.cell>
                                <x-table.cell class="px-3 py-2">
                                    <span
                                        class="px-2 py-1 text-xs rounded-md {{ $transaction->type == 'debit' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $transaction->type == 'debit' ? 'Tabungan' : 'Penarikan' }}
                                    </span>
                                </x-table.cell>
                                <x-table.cell class="px-3 py-2">Rp
                                    {{ number_format($transaction->amount, 0, ',', '.') }}</x-table.cell>
                                <x-table.cell class="px-3 py-2">Rp
                                    {{ number_format($transaction->balance_after, 0, ',', '.') }}</x-table.cell>
                                <x-table.cell
                                    class="px-3 py-2">{{ $transaction->description ?? '-' }}</x-table.cell>
                            </x-table.row>
                        @endforeach
                    </x-table>
                </div>
            @else
                <div class="py-4 text-center text-gray-500 bg-gray-50 rounded">
                    Data tabungan karyawan tidak ditemukan.
                </div>
            @endif
        </div>
    </div>
</flux:modal>
