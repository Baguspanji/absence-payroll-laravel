<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Livewire\Attributes\Rule;

new class extends Component {
    #[Rule('required', message: 'Karyawan harus dipilih.')]
    public $employeeId = '';
    #[Rule('required', message: 'Tipe pengajuan harus diisi.')]
    public $leaveType = '';

    #[Rule('required|date', message: 'Tanggal mulai harus diisi.')]
    public $startDate = '';

    #[Rule('required|date|after_or_equal:startDate', message: 'Tanggal selesai harus setelah atau sama dengan tanggal mulai.')]
    public $endDate = '';

    #[Rule('required|min:10', message: 'Keterangan harus diisi minimal 10 karakter.')]
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

    public function submit()
    {
        $this->validate();
        LeaveRequest::create([
            'employee_id' => $this->employeeId,
            'leave_type' => $this->leaveType,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'reason' => $this->reason,
            'status' => 'pending',
        ]);

        $this->dispatch('alert-shown', message: 'Pengajuan berhasil dikirim!', type: 'success');

        $this->reset();
    }
}; ?>

<div>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-6">Form Pengajuan Cuti & Izin</h2>

            <form wire:submit="submit" class="space-y-4">
                {{-- Dropdown Karyawan (hanya untuk Admin/Manajer) --}}
                @if (Auth::user()->role !== 'employee') {{-- Sesuaikan dengan logic role Anda --}}
                    <flux:select label="Karyawan" wire:model="employeeId" placeholder="Pilih Karyawan...">
                        @foreach ($employees as $employee)
                            <flux:select.option value="{{ $employee->id }}">{{ $employee->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                {{-- Dropdown Pengajuan --}}
                <flux:select label="Tipe Pengajuan" wire:model="leaveType" placeholder="Pilih Tipe Pengajuan...">
                    <flux:select.option>Cuti</flux:select.option>
                    <flux:select.option>Izin</flux:select.option>
                </flux:select>

                {{-- Tanggal Izin --}}
                <flux:input type="date" label="Tanggal Awal Izin" wire:model="startDate" />

                <flux:input type="date" label="Tanggal Akhir Izin" wire:model="endDate" />

                {{-- Alasan --}}
                <flux:textarea label="Alasan Cuti" wire:model="reason" rows="3" />

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Ajukan Cuti</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
