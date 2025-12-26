<?php

use Livewire\Volt\Component;
use App\Models\Device;
use App\Models\Branch;
use Livewire\WithPagination;
use Livewire\Attributes\Rule;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $isEdit = false;
    public $deviceId = null;
    #[Rule('required', message: 'Nama harus diisi.')]
    public $name = '';
    #[Rule('required', message: 'Serial number harus diisi.')]
    public $serialNumber = '';
    #[Rule('required', message: 'CMD ID harus diisi.')]
    public $cmdId = '';
    public $isActive = false;
    public $branchId = '';
    public $branches = [];
    public $commandId = '';
    public $startDate = '';
    public $endDate = '';
    public $commands = [
        [
            'value' => 1,
            'label' => 'Check Device',
            'command' => 'C:[CmdId]:CHECK',
        ],
        [
            'value' => 2,
            'label' => 'Info Device',
            'command' => 'C:[CmdId]:INFO',
        ],
        [
            'value' => 3,
            'label' => 'Log Device',
            'command' => 'C:[CmdId]:LOG',
        ],
        [
            'value' => 4,
            'label' => 'Reboot Device',
            'command' => 'C:[CmdId]:REBOOT',
        ],
        [
            'value' => 5,
            'label' => 'Clear Log Device',
            'command' => 'C:[CmdId]:CLEAR<spasi>LOG',
        ],
        [
            'value' => 6,
            'label' => 'Data Query Attlog',
            'command' => 'C:[CmdId]:DATA<spasi>QUERY<spasi>ATTLOG<spasi>StartTime=[StartTime]<tab>EndTime=[EndTime]',
        ],
    ];

    public function mount()
    {
        $this->branches = Branch::get()
            ->map(function ($branch) {
                return [
                    'value' => $branch->id,
                    'label' => $branch->name,
                    'description' => $branch->address,
                ];
            })
            ->toArray();
    }

    /**
     * Mengambil data device untuk ditampilkan.
     */
    public function with(): array
    {
        $query = Device::query();

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')->orWhere('serial_number', 'like', '%' . $this->search . '%');
            });
        }

        return [
            'requests' => $query->latest()->paginate(10),
        ];
    }

    public function submitCommand()
    {
        $this->validate([
            'commandId' => 'required',
        ]);

        $commandSellected = collect($this->commands)->where('value', $this->commandId)->first();

        if (!$commandSellected) {
            $this->dispatch('alert-shown', message: 'Perintah tidak ditemukan!', type: 'error');
        }

        $command = str_replace('[CmdId]', $this->cmdId ?? 1, $commandSellected['command']);

        if ($this->commandId == 6) {
            if (!$this->startDate || !$this->endDate) {
                $this->dispatch('alert-shown', message: 'Start Time dan End Time harus diisi untuk perintah ini!', type: 'error');
                return;
            }

            $startTimeFormatted = date('Y-m-d H:i:s', strtotime($this->startDate));
            $endTimeFormatted = date('Y-m-d H:i:s', strtotime($this->endDate));

            $command = str_replace('[StartTime]', $startTimeFormatted, $command);
            $command = str_replace('[EndTime]', $endTimeFormatted, $command);
        }

        if (str_contains($command, '<tab>')) {
            $command = str_replace('<tab>', "\t", $command);
        }

        if (str_contains($command, '<spasi>')) {
            $command = str_replace('<spasi>', ' ', $command);
        }

        cache()->put("device_command_{$this->serialNumber}", $command, now()->addMinutes(30)); // Cache for 30 minutes
        // cache()->put("device_command_{$this->serialNumber}", "C:1:DATA QUERY tablename=user,fielddesc=*,filter=*", now()->addMinutes(10)); // Cache for 10 minutes

        $this->dispatch('alert-shown', message: 'Perintah berhasil dikirim!', type: 'success');

        $this->modal('command-data')->close();
    }

    public function command(Device $device)
    {
        $this->deviceId = $device->id;

        $this->serialNumber = $device->serial_number;
        $this->cmdId = $device->cmd_id;
        $this->commandId = '';

        $this->modal('command-data')->show();
    }

    public function edit(Device $device)
    {
        $this->deviceId = $device->id;
        $this->isEdit = true;

        $this->name = $device->name;
        $this->serialNumber = $device->serial_number;
        $this->cmdId = $device->cmd_id;
        $this->branchId = $device->branch_id;
        $this->isActive = $device->is_active;

        $this->modal('form-data')->show();
    }

    public function updateStatus(Device $device)
    {
        $device->is_active = !$device->is_active;
        $device->save();

        $this->dispatch('alert-shown', message: 'Status berhasil diperbarui!', type: 'success');
    }

    public function submit()
    {
        $this->validate();

        $user = Device::find($this->deviceId);
        $user->name = $this->name;
        // $user->serialNumber = $this->serialNumber;
        $user->cmd_id = $this->cmdId;
        $user->branch_id = $this->branchId;
        $this->is_active = $this->isActive;
        $user->save();

        $this->dispatch('alert-shown', message: 'Data device berhasil diperbarui!', type: 'success');

        $this->modal('form-data')->close();
    }

    public function resetForm()
    {
        $this->deviceId = null;
        $this->isEdit = false;

        $this->name = '';
        $this->serialNumber = '';
        $this->cmdId = '';
        $this->isActive = false;
        $this->branchId = '';
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Daftar Device</h2>
    </div>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama atau serial number device..."
            icon="magnifying-glass" />
    </div>

    <x-table :headers="['Nama Device', 'Serial Number', 'Cabang', 'Terakhir Aktif', 'Status', 'Aksi']" :rows="$requests" emptyMessage="Tidak ada data device." fixedHeader="true"
        maxHeight="540px">
        @foreach ($requests as $request)
            <x-table.row>
                <x-table.cell class="font-medium text-gray-900">
                    {{ $request->name }}
                </x-table.cell>
                <x-table.cell class="font-mono">
                    {{ $request->serial_number }}
                </x-table.cell>
                <x-table.cell>
                    {{ $request->branch?->name }}
                </x-table.cell>
                <x-table.cell>
                    {{ $request->last_sync_at ? $request->last_sync_at->format('Y-m-d H:i:s') : '-' }}
                </x-table.cell>
                <x-table.cell>
                    @if ($request->is_active)
                        <span class="text-xs text-white px-2 py-1.5 bg-green-600 rounded-md cursor-pointer"
                            wire:click="updateStatus({{ $request->id }})">
                            Aktif
                        </span>
                    @else
                        <span class="text-xs text-white px-2 py-1.5 bg-red-400 rounded-md cursor-pointer"
                            wire:click="updateStatus({{ $request->id }})">
                            Tidak Aktif
                        </span>
                    @endif
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap w-[15%]">
                    <x-button-tooltip tooltip="Kirim perintah" icon="square-terminal"
                        wire:click="command({{ $request->id }})"
                        class="text-sm text-gray-600 px-2 py-1 rounded hover:bg-gray-100 cursor-pointer"
                        iconClass="w-4 h-4 inline-block -mt-1">
                    </x-button-tooltip>
                    <x-button-tooltip tooltip="Edit data" icon="pencil-square" wire:click="edit({{ $request->id }})"
                        class="text-sm text-yellow-600 px-2 py-1 rounded hover:bg-yellow-100 cursor-pointer"
                        iconClass="w-4 h-4 inline-block -mt-1">
                    </x-button-tooltip>
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
                <flux:heading size="lg">{{ $isEdit ? 'Edit Device' : 'Edit Device' }}</flux:heading>
            </div>

            <flux:input label="Nama" placeholder="Masukkan Nama" wire:model="name" />

            <flux:input label="Serial Number" placeholder="Masukkan Serial Number" wire:model="serialNumber" readonly />

            <flux:input label="CMD ID" placeholder="Masukkan CMD" wire:model="cmdId" />

            <flux:select label="Cabang" wire:model="branchId" placeholder="Pilih Cabang...">
                @foreach ($branches as $item)
                    <flux:select.option value="{{ $item['value'] }}">{{ $item['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" wire:click="submit" variant="primary">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Command -->
    <flux:modal name="command-data" class="md:w-96">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Kirim Perintah</flux:heading>
            </div>

            <flux:input label="Serial Number" placeholder="Masukkan Serial Number" wire:model="serialNumber" readonly />

            <flux:select label="Perintah" wire:model.live="commandId" placeholder="Pilih Perintah...">
                @foreach ($commands as $item)
                    <flux:select.option value="{{ $item['value'] }}">{{ $item['label'] }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($commandId == 6)
                <flux:input type="date" label="Start Time" wire:model="startDate" />
                <flux:input type="date" label="End Time" wire:model="endDate" />
            @endif

            <div class="flex">
                <flux:spacer />
                <flux:button type="button" wire:click="submitCommand" variant="primary">Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
