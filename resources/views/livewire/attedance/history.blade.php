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

    <x-table :headers="['Karyawan', 'Cabang', 'Waktu', 'Device SN']" :rows="$requests" emptyMessage="Tidak ada data riwayat." fixedHeader="true"
        maxHeight="540px">
        @foreach ($requests as $request)
            <x-table.row>
                <x-table.cell class="font-medium text-gray-900 whitespace-nowrap">
                    <div class="flex items-center gap-4">
                        @if ($request->employee?->image_url)
                            <img src="{{ $request->employee?->image_url }}" alt="Foto Karyawan"
                                class="w-10 h-10 rounded object-cover" />
                        @else
                            <div class="w-10 h-10 rounded bg-gray-200 flex items-center justify-center text-gray-500">
                                <flux:icon name="user" class="w-6 h-6" />
                            </div>
                        @endif
                        <div class="flex flex-col items-start">
                            <span class="font-mono text-green-600">{{ $request->employee_nip }}</span>
                            <span>{{ $request->employee?->name }}</span>
                        </div>
                    </div>
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    @can('admin')
                        <div class="flex flex-col items-start">
                            <span class="font-semibold">
                                {{ $request->employee?->branch?->name }}
                            </span>
                        </div>
                    @endcan
                </x-table.cell>
                <x-table.cell class="px-6 py-4 font-medium text-gray-900">
                    {{ $request->timestamp }}
                </x-table.cell>
                <x-table.cell class="whitespace-nowrap">
                    {{ $request->device_sn }}
                </x-table.cell>
            </x-table.row>
        @endforeach
    </x-table>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>
