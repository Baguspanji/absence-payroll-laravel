<?php

use Livewire\Volt\Component;
use App\Models\Attendance;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    /**
     * Mengambil data pengajuan untuk ditampilkan.
     */
    public function with(): array
    {
        if (Auth::user()->role == 'leader') {
            return [
                'requests' => Attendance::query()
                    ->with('employee')
                    ->whereHas('employee', function ($q) {
                        $q->where('branch_id', Auth::user()->employee->branch_id);
                    })
                    ->latest()
                    ->paginate(10),
            ];
        }

        return [
            'requests' => Attendance::query()->with('employee.branch')->latest()->paginate(10),
        ];
    }
}; ?>

<div class="px-6 py-4">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold mb-4">Riwayat Absensi</h2>
    </div>

    <div class="overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">#</th>
                    <th scope="col" class="px-6 py-3">Nama Pegawai</th>
                    @can('admin')
                        <th scope="col" class="px-6 py-3">Cabang</th>
                    @endcan
                    <th scope="col" class="px-6 py-3">Waktu</th>
                    <th scope="col" class="px-6 py-3">Status Scan</th>
                    <th scope="col" class="px-6 py-3">Device SN</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="font-mono px-6 py-4">{{ $request->employee_nip }}</td>
                        <td class="font-mono px-6 py-4">{{ $request->employee?->name }}</td>
                        @can('admin')
                            <td class="font-mono px-6 py-4">{{ $request->employee?->branch?->name }}</td>
                        @endcan
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $request->timestamp }}</td>
                        <td class="px-6 py-4">{{ $request->status_scan ? 'Masuk' : 'Pulang' }}</td>
                        <td class="px-6 py-4">{{ $request->device_sn }}</td>
                    </tr>
                @empty
                    <tr class="bg-white border-b">
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Tidak ada data riwayat.
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
