<?php

use Livewire\Volt\Component;
use App\Models\Branch;
use App\Models\Employee;

new class extends Component {
    public function with(): array
    {
        $totalBranches = Branch::count();
        $totalEmployees = Employee::count();
        $totalPositions = Employee::select('position')->distinct()->count('position');

        return [
            'totalBranches' => $totalBranches,
            'totalEmployees' => $totalEmployees,
            'totalPositions' => $totalPositions,
        ];
    }
}; ?>

<div class="sticky top-0 z-10 bg-white/95 backdrop-blur-sm py-4 mb-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Total Cabang -->
        <div class="bg-gradient-to-br from-slate-50 via-blue-50 to-slate-50 rounded-xl shadow-sm hover:shadow-md transition-all px-6 py-5 border border-slate-200/50">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-slate-600 text-xs font-semibold uppercase tracking-widest">Total Cabang</p>
                    <p class="text-4xl font-bold text-slate-900 mt-2">{{ $totalBranches }}</p>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-3 shadow-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Pegawai -->
        <div class="bg-gradient-to-br from-slate-50 via-emerald-50 to-slate-50 rounded-xl shadow-sm hover:shadow-md transition-all px-6 py-5 border border-slate-200/50">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-slate-600 text-xs font-semibold uppercase tracking-widest">Total Pegawai</p>
                    <p class="text-4xl font-bold text-slate-900 mt-2">{{ $totalEmployees }}</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg p-3 shadow-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Jabatan -->
        <div class="bg-gradient-to-br from-slate-50 via-violet-50 to-slate-50 rounded-xl shadow-sm hover:shadow-md transition-all px-6 py-5 border border-slate-200/50">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-slate-600 text-xs font-semibold uppercase tracking-widest">Total Jabatan</p>
                    <p class="text-4xl font-bold text-slate-900 mt-2">{{ $totalPositions }}</p>
                </div>
                <div class="bg-gradient-to-br from-violet-500 to-violet-600 rounded-lg p-3 shadow-sm">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>
