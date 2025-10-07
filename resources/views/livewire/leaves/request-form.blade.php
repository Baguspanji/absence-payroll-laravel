<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\LeaveRequest;
use Livewire\Attributes\Rule;

new class extends Component {
    #[Rule('required', message: 'Tipe pengajuan harus diisi.')]
    public $leaveType = '';

    #[Rule('required|date', message: 'Tanggal mulai harus diisi.')]
    public $startDate = '';

    #[Rule('required|date|after_or_equal:startDate', message: 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.')]
    public $endDate = '';

    #[Rule('required|min:10', message: 'Keterangan harus diisi minimal 10 karakter.')]
    public $reason = '';

    public function submit()
    {
        $this->validate();
        $employeeId = Auth::user()->employee->id;
        LeaveRequest::create([
            'employee_id' => $employeeId,
            'leave_type' => $this->leaveType,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'reason' => $this->reason,
            'status' => 'pending',
        ]);

        session()->flash('message', 'Pengajuan berhasil dikirim.');
        $this->reset();
    }
}; ?>

<div>
    <div class="container mx-auto px-4">
        <div class="flex justify-center">
            <div class="w-full md:w-2/3 lg:w-1/2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gray-100 px-6 py-4 border-b">Form Pengajuan Cuti & Izin</div>
                    <div class="p-6">

                        <form wire:submit="submit" class="space-y-6">
                            {{-- Pesan Sukses --}}
                            @if (session('message'))
                                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md"
                                    role="alert">
                                    <span class="block sm:inline">{{ session('message') }}</span>
                                </div>
                            @endif

                            {{-- Tipe Pengajuan --}}
                            <div>
                                <label for="leaveType" class="block text-sm font-medium text-gray-700">Tipe
                                    Pengajuan</label>
                                <select wire:model="leaveType" id="leaveType"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Pilih Tipe</option>
                                    <option value="Cuti">Cuti</option>
                                    <option value="Izin">Izin</option>
                                </select>
                                @error('leaveType')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Tanggal Mulai --}}
                            <div>
                                <label for="startDate" class="block text-sm font-medium text-gray-700">Tanggal
                                    Mulai</label>
                                <input type="date" wire:model="startDate" id="startDate"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                @error('startDate')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Tanggal Selesai --}}
                            <div>
                                <label for="endDate" class="block text-sm font-medium text-gray-700">Tanggal
                                    Selesai</label>
                                <input type="date" wire:model="endDate" id="endDate"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                @error('endDate')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Keterangan --}}
                            <div>
                                <label for="reason" class="block text-sm font-medium text-gray-700">Keterangan</label>
                                <textarea wire:model="reason" id="reason" rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                                @error('reason')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <button type="submit"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    Ajukan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
