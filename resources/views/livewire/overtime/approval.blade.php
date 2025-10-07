<?php

use Livewire\Volt\Component;
use App\Models\OvertimeRequest;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    /**
     * Menyetujui sebuah pengajuan lembur.
     */
    public function approve(OvertimeRequest $overtimeRequest)
    {
        $overtimeRequest->update(['status_approval' => 'approved']);
    }

    /**
     * Menolak sebuah pengajuan lembur.
     */
    public function reject(OvertimeRequest $overtimeRequest)
    {
        $overtimeRequest->update(['status_approval' => 'rejected']);
    }

    /**
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        return [
            'requests' => OvertimeRequest::where('status_approval', 'pending')
                ->with('employee') // Eager load relasi employee
                ->latest()
                ->paginate(10),
        ];
    }
}; ?>

<div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Persetujuan Lembur</h2>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">Nama Karyawan</th>
                    <th scope="col" class="px-6 py-3">Tanggal Lembur</th>
                    <th scope="col" class="px-6 py-3">Alasan</th>
                    <th scope="col" class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->employee->name }}</td>
                        <td class="px-6 py-4">{{ \Carbon\Carbon::parse($request->date)->format('d M Y') }}</td>
                        <td class="px-6 py-4">{{ $request->reason }}</td>
                        <td class="px-6 py-4 space-x-2">
                            <button wire:click="approve({{ $request->id }})"
                                wire:confirm="Anda yakin ingin menyetujui pengajuan ini?"
                                class="font-medium text-green-600 hover:underline">
                                Approve
                            </button>
                            <button wire:click="reject({{ $request->id }})"
                                wire:confirm="Anda yakin ingin menolak pengajuan ini?"
                                class="font-medium text-red-600 hover:underline">
                                Reject
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b">
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                            Tidak ada pengajuan lembur yang perlu disetujui.
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
