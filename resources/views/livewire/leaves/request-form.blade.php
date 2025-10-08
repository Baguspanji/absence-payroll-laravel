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

        $this->dispatch('alert-shown', message: 'Pengajuan berhasil dikirim!', type: 'success');

        $this->reset();
    }
}; ?>

<div>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-6">Form Pengajuan Cuti & Izin</h2>

            <form wire:submit="submit" class="space-y-4">
                {{-- Dropdown Pengajuan --}}
                <flux:select label="Tipe Pengajuan" wire:model="leaveType" placeholder="Pilih Tipe Pengajuan...">
                    <flux:select.option>Cuti</flux:select.option>
                    <flux:select.option>Izin</flux:select.option>
                </flux:select>

                {{-- Tanggal Izin --}}
                <flux:input type="date" label="Tanggal Awal Izin" wire:model="startDate" />

                <flux:input type="date" label="Tanggal Akhir Izin" wire:model="endDate" />

                {{-- Alasan --}}
                <flux:textarea label="Alasan Lembur" wire:model="reason" rows="3" />

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Ajukan Cuti</flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
