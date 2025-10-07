<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\OvertimeRequest; // Pastikan Anda sudah punya model ini
use App\Models\Employee; // Import model Employee
use Livewire\Attributes\Rule;

new class extends Component {
    // Properti untuk form
    #[Rule('required', message: 'Karyawan harus dipilih.')]
    public $employeeId = '';

    #[Rule('required|date', message: 'Tanggal lembur harus diisi.')]
    public $date = '';

    #[Rule('required|min:10', message: 'Alasan harus diisi minimal 10 karakter.')]
    public $reason = '';

    public $employees = [];

    /**
     * Method mount dijalankan saat komponen pertama kali dimuat.
     */
    public function mount()
    {
        // Isi dropdown dengan daftar karyawan
        $this->employees = Employee::orderBy('name')->get();
        // Jika yang login adalah karyawan biasa, langsung pilih dirinya sendiri
        if (Auth::user()->role === 'employee') {
            // Sesuaikan dengan nama role Anda
            $this->employeeId = Auth::user()->employee->id;
        }
    }

    /**
     * Method untuk menyimpan pengajuan lembur.
     */
    public function submit()
    {
        $this->validate();

        OvertimeRequest::create([
            'employee_id' => $this->employeeId,
            'date' => $this->date,
            'reason' => $this->reason,
            'status' => 'pending',
        ]);

        session()->flash('message', 'Pengajuan lembur berhasil dikirim.');

        $this->reset('date', 'reason');
        // Jangan reset employeeId jika yang login adalah karyawan biasa
        if (Auth::user()->role !== 'employee') {
            $this->reset('employeeId');
        }
    }
}; ?>

<div>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-6">Form Pengajuan Lembur</h2>

            <form wire:submit="submit" class="space-y-6">
                @if (session('message'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-md" role="alert">
                        <span>{{ session('message') }}</span>
                    </div>
                @endif

                {{-- Dropdown Karyawan (hanya untuk Admin/Manajer) --}}
                @if (Auth::user()->role !== 'employee') {{-- Sesuaikan dengan logic role Anda --}}
                    <div>
                        <label for="employeeId" class="block text-sm font-medium text-gray-700">Pilih Karyawan</label>
                        <select wire:model="employeeId" id="employeeId"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                            @endforeach
                        </select>
                        @error('employeeId')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                {{-- Tanggal Lembur --}}
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">Tanggal Lembur</label>
                    <input type="date" wire:model="date" id="date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    @error('date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Alasan --}}
                <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700">Alasan Lembur</label>
                    <textarea wire:model="reason" id="reason" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                    @error('reason')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <button type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        Ajukan Lembur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
