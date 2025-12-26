<?php

use Livewire\Volt\Component;
use App\Models\LeaveRequest;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    public $search = '';

    /**
     * Menyetujui sebuah pengajuan cuti.
     */
    public function approve(LeaveRequest $leaveRequest)
    {
        $leaveRequest->update(['status_approval' => 'approved']);
        $this->dispatch('alert-shown', message: 'Pengajuan berhasil disetujui!', type: 'success');
    }

    /**
     * Menolak sebuah pengajuan cuti.
     */
    public function reject(LeaveRequest $leaveRequest)
    {
        $leaveRequest->update(['status_approval' => 'rejected']);
        $this->dispatch('alert-shown', message: 'Pengajuan berhasil ditolak!', type: 'success');
    }

    /**
     * Mengambil data pengajuan cuti untuk ditampilkan.
     */
    public function with(): array
    {
        $query = LeaveRequest::with('employee.branch');

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('employee', function ($eq) {
                    $eq->where('name', 'like', '%' . $this->search . '%')->orWhere('nip', 'like', '%' . $this->search . '%');
                })->orWhere('reason', 'like', '%' . $this->search . '%');
            });
        }

        // Filter by branch for leaders
        if (Auth::user()->role == 'leader') {
            $query->whereHas('employee', function ($q) {
                $q->where('branch_id', Auth::user()->employee->branch_id);
            });
        }

        return [
            'requests' => $query->latest()->paginate(10),
        ];
    }
}; ?>

<div class="px-6 py-4">
    <h2 class="text-2xl font-bold mb-4">Persetujuan Cuti & Izin</h2>

    <div class="mb-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama karyawan, NIP, atau alasan..."
            icon="magnifying-glass" />
    </div>

    @can('admin')
        <x-table :headers="['NIP', 'Nama Karyawan', 'Cabang', 'Tipe', 'Tanggal', 'Alasan', 'Aksi']" :rows="$requests" emptyMessage="Tidak ada pengajuan yang perlu disetujui."
            fixedHeader="true" maxHeight="540px">
            @foreach ($requests as $request)
                <x-table.row>
                    <x-table.cell class="font-mono">
                        {{ $request->employee?->nip }}
                    </x-table.cell>
                    <x-table.cell class="font-medium text-gray-900">
                        {{ $request->employee->name }}
                    </x-table.cell>
                    <x-table.cell>
                        {{ $request->employee?->branch?->name }}
                    </x-table.cell>
                    <x-table.cell>
                        {{ $request->leave_type }}
                    </x-table.cell>
                    <x-table.cell class="text-sm">
                        {{ \Carbon\Carbon::parse($request->start_date)->translatedFormat('d M Y') }} -
                        {{ \Carbon\Carbon::parse($request->end_date)->translatedFormat('d M Y') }}
                    </x-table.cell>
                    <x-table.cell>
                        {{ $request->reason }}
                    </x-table.cell>
                    <x-table.cell class="whitespace-nowrap w-[12%]">
                        @if ($request->status_approval === 'approved')
                            <span
                                class="inline-flex items-center px-2 py-1.5 rounded-md text-xs font-medium bg-green-100 text-green-800">
                                Disetujui
                            </span>
                        @elseif ($request->status_approval === 'rejected')
                            <span
                                class="inline-flex items-center px-2 py-1.5 rounded-md text-xs font-medium bg-red-100 text-red-800">
                                Ditolak
                            </span>
                        @else
                            <x-button-tooltip tooltip="Setujui pengajuan" icon="check-circle"
                                wire:click="approve({{ $request->id }})"
                                wire:confirm="Anda yakin ingin menyetujui pengajuan ini?"
                                class="text-sm text-green-600 px-2 py-1 rounded hover:bg-green-100 cursor-pointer"
                                iconClass="w-4 h-4 inline-block -mt-1">
                            </x-button-tooltip>
                            <x-button-tooltip tooltip="Tolak pengajuan" icon="x-circle"
                                wire:click="reject({{ $request->id }})"
                                wire:confirm="Anda yakin ingin menolak pengajuan ini?"
                                class="text-sm text-red-600 px-2 py-1 rounded hover:bg-red-100 cursor-pointer"
                                iconClass="w-4 h-4 inline-block -mt-1">
                            </x-button-tooltip>
                        @endif
                    </x-table.cell>
                </x-table.row>
            @endforeach
        </x-table>
    @else
        <x-table :headers="['NIP', 'Nama Karyawan', 'Tipe', 'Tanggal', 'Alasan', 'Aksi']" :rows="$requests" emptyMessage="Tidak ada pengajuan yang perlu disetujui."
            fixedHeader="true" maxHeight="540px">
            @foreach ($requests as $request)
                <x-table.row>
                    <x-table.cell class="font-mono">
                        {{ $request->employee?->nip }}
                    </x-table.cell>
                    <x-table.cell class="font-medium text-gray-900">
                        {{ $request->employee->name }}
                    </x-table.cell>
                    <x-table.cell>
                        {{ $request->leave_type }}
                    </x-table.cell>
                    <x-table.cell class="text-sm">
                        {{ \Carbon\Carbon::parse($request->start_date)->translatedFormat('d M Y') }} -
                        {{ \Carbon\Carbon::parse($request->end_date)->translatedFormat('d M Y') }}
                    </x-table.cell>
                    <x-table.cell>
                        {{ $request->reason }}
                    </x-table.cell>
                    <x-table.cell class="whitespace-nowrap w-[12%]">
                        @if ($request->status_approval === 'approved')
                            <span
                                class="inline-flex items-center px-2 py-1.5 rounded-md text-xs font-medium bg-green-100 text-green-800">
                                Disetujui
                            </span>
                        @elseif ($request->status_approval === 'rejected')
                            <span
                                class="inline-flex items-center px-2 py-1.5 rounded-md text-xs font-medium bg-red-100 text-red-800">
                                Ditolak
                            </span>
                        @else
                            <x-button-tooltip tooltip="Setujui pengajuan" icon="check-circle"
                                wire:click="approve({{ $request->id }})"
                                wire:confirm="Anda yakin ingin menyetujui pengajuan ini?"
                                class="text-sm text-green-600 px-2 py-1 rounded hover:bg-green-100 cursor-pointer"
                                iconClass="w-4 h-4 inline-block -mt-1">
                            </x-button-tooltip>
                            <x-button-tooltip tooltip="Tolak pengajuan" icon="x-circle"
                                wire:click="reject({{ $request->id }})"
                                wire:confirm="Anda yakin ingin menolak pengajuan ini?"
                                class="text-sm text-red-600 px-2 py-1 rounded hover:bg-red-100 cursor-pointer"
                                iconClass="w-4 h-4 inline-block -mt-1">
                            </x-button-tooltip>
                        @endif
                    </x-table.cell>
                </x-table.row>
            @endforeach
        </x-table>
    @endcan

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>
