<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\OvertimeRequest; // Pastikan Anda sudah punya model ini
use App\Models\Employee; // Import model Employee
use Livewire\Attributes\Rule;

new class extends Component {
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
        if (Auth::user()->role == 'leader') {
            $this->employees = Employee::where('branch_id', Auth::user()->employee->branch_id)
                ->orderBy('name')
                ->get();
        } else {
            $this->employees = Employee::orderBy('name')->get();
        }

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

        $this->dispatch('alert-shown', message: 'Pengajuan berhasil dikirim!', type: 'success');

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

            <form wire:submit="submit" class="space-y-4">
                {{-- Dropdown Karyawan (hanya untuk Admin/Manajer) --}}
                @if (Auth::user()->role !== 'employee') {{-- Sesuaikan dengan logic role Anda --}}
                    <flux:select label="Karyawan" wire:model="employeeId" placeholder="Pilih Karyawan...">
                        @foreach ($employees as $employee)
                            <flux:select.option value="{{ $employee->id }}">{{ $employee->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                {{-- Tanggal Lembur --}}
                <flux:input type="date" label="Tanggal Lembur" wire:model="date" />

                {{-- Alasan --}}
                <flux:textarea label="Alasan Lembur" wire:model="reason" rows="3" />

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Ajukan Lembur</flux:button>
                </div>
            </form>˝
        </div>
    </div>
