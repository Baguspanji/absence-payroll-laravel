<?php

use Livewire\Volt\Component;
use App\Models\LeaveRequest;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

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
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        if (Auth::user()->role == 'leader') {
            return [
                'requests' => LeaveRequest::where('status_approval', 'pending')
                    ->with('employee')
                    ->whereHas('employee', function ($q) {
                        $q->where('branch_id', Auth::user()->employee->branch_id);
                    })
                    ->latest()
                    ->paginate(10),
            ];
        }

        return [
            'requests' => LeaveRequest::where('status_approval', 'pending')
                ->with('employee.branch') // Eager load relasi employee
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div class="px-6 py-4">
    <h2 class="text-2xl font-bold mb-4">Persetujuan Cuti & Izin</h2>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">#</th>
                    <th scope="col" class="px-6 py-3">Nama Karyawan</th>
                    @can('admin')
                        <th scope="col" class="px-6 py-3">Cabang</th>
                    @endcan
                    <th scope="col" class="px-6 py-3">Tipe</th>
                    <th scope="col" class="px-6 py-3">Tanggal</th>
                    <th scope="col" class="px-6 py-3">Alasan</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="font-mono px-6 py-4">{{ $request->employee?->nip }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->employee->name }}</td>
                        @can('admin')
                            <td class="font-mono px-6 py-4">{{ $request->employee?->branch?->name }}</td>
                        @endcan
                        <td class="px-6 py-4">{{ $request->leave_type }}</td>
                        <td class="px-6 py-4">
                            {{ \Carbon\Carbon::parse($request->start_date)->translatedFormat('d M Y') }} -
                            {{ \Carbon\Carbon::parse($request->end_date)->translatedFormat('d M Y') }}</td>
                        <td class="px-6 py-4">{{ $request->reason }}</td>
                        <td class="px-6 py-4 space-x-2">
                            <button wire:click="approve({{ $request->id }})"
                                wire:confirm="Anda yakin ingin menyetujui pengajuan ini?"
                                class="text-xs font-medium px-2 py-1.5 bg-green-600 text-white rounded-md cursor-pointer">
                                Approve
                            </button>
                            <button wire:click="reject({{ $request->id }})"
                                wire:confirm="Anda yakin ingin menolak pengajuan ini?"
                                class="text-xs font-medium px-2 py-1.5 bg-red-600 text-white rounded-md cursor-pointer">
                                Reject
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b">
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Tidak ada pengajuan yang perlu disetujui.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>
