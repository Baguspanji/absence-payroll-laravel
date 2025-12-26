<?php

use Livewire\Volt\Component;
use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public $selectedMonth;
    public $selectedYear;
    public array $years = [];
    public array $months = [];
    public array $results = [];
    public $isGenerated = false;

    public function mount()
    {
        $this->years = range(Carbon::now()->year, Carbon::now()->year - 5);
        $this->months = collect(range(1, 12))->mapWithKeys(fn($month) => [$month => Carbon::create()->month($month)->translatedFormat('F')])->toArray();
        $this->selectedYear = Carbon::now()->year;
        $this->selectedMonth = Carbon::now()->month;
    }

    public function generatePayroll()
    {
        $this->validate(['selectedMonth' => 'required', 'selectedYear' => 'required']);

        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $totalDaysInMonth = $startDate->daysInMonth - 4; // dikurangi 4 hari cuti
        // $totalDaysInMonth = $startDate->daysInMonth; // --- IGNORE ---

        $employees = Employee::query()->join('users', 'employees.user_id', '=', 'users.id')->where('users.is_active', true)->select('employees.*')->get();
        $this->results = [];

        DB::transaction(function () use ($employees, $startDate, $endDate, $totalDaysInMonth) {
            foreach ($employees as $employee) {
                // 1. Ambil data rekap absensi
                $workdays = DB::table('attendance_summaries')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->count();
                $totalWorkHours = DB::table('attendance_summaries')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('work_hours');
                $totalDayLate = DB::table('attendance_summaries')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->where('late_minutes', '>', 0)
                    ->count();
                $totalOvertimeHours = DB::table('attendance_summaries')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('overtime_hours');

                // 2. Ambil semua komponen gaji milik karyawan ini
                $components = DB::table('employee_payroll_components as epc')->join('payroll_components as pc', 'epc.payroll_component_id', '=', 'pc.id')->where('epc.employee_id', $employee->id)->select('pc.name', 'pc.type', 'pc.is_fixed', 'epc.amount')->get();

                $earnings = [];
                $deductions = [];

                // 3. Proses setiap komponen
                foreach ($components as $component) {
                    if ($component->type == null) continue; // Skip komponen gaji pokok, lembur & keterlambatan di sini

                    $finalAmount = $component->amount;
                    // Jika tidak tetap, hitung pro-rata berdasarkan hari kerja
                    if (!$component->is_fixed) {
                        $finalAmount = ($component->amount / $totalDaysInMonth) * $workdays;
                    }

                    if ($component->type === 'earning') {
                        $earnings[$component->name] = $finalAmount;
                    } else if ($component->type === 'deduction') {
                        $deductions[$component->name] = $finalAmount;
                    }
                }

                // Tambahkan komponen dinamis (lembur & keterlambatan)
                $lateComponent = $components->firstWhere('name', 'Potongan Terlambat');
                if ($lateComponent) {
                    // terlambat otomatis terhitung sebagai jumlah hari terlambat
                    $deductions['Potongan Terlambat'] = $totalDayLate * $lateComponent->amount;
                }

                $overtimeComponent = $components->firstWhere('name', 'Upah Lembur');
                if ($overtimeComponent) {
                    // Konversi jam lembur ke hari
                    $totalOvertimeDays = ceil($totalOvertimeHours / 8); // Asumsi 1 hari kerja = 8 jam
                    $earnings['Upah Lembur'] = $totalOvertimeDays * $overtimeComponent->amount; // di sini amount = rate per jam
                }

                $basicSalaryComponent = $components->firstWhere('name', 'Gaji Pokok');
                if ($basicSalaryComponent) {
                    // Konversi jam kerja ke hari
                    $totalWorkDays = ceil($totalWorkHours / 8); // Asumsi
                    $earnings['Gaji Pokok'] = ($basicSalaryComponent->amount / $totalDaysInMonth) * $totalWorkDays;
                }

                $savingComponent = $components->firstWhere('name', 'Potongan Tabungan');
                if ($savingComponent) {
                    $deductions['Potongan Tabungan'] = $savingComponent->amount;

                    $savingService = app()->make(\App\Services\EmployeeSavingService::class);
                    $savingService->deposit($employee, $savingComponent->amount, 'Potongan Tabungan Payroll ' . $startDate->translatedFormat('F Y'), null);
                }

                // 4. Hitung Total dan simpan
                $totalEarnings = array_sum($earnings);
                $totalDeductions = array_sum($deductions);
                $netSalary = $totalEarnings - $totalDeductions;

                // 5. Simpan ke database
                $payroll = Payroll::create([
                    'employee_id' => $employee->id,
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'net_salary' => $netSalary,
                ]);

                foreach ($earnings as $desc => $amount) {
                    $payroll->details()->create(['description' => $desc, 'type' => 'earning', 'amount' => $amount]);
                }
                foreach ($deductions as $desc => $amount) {
                    $payroll->details()->create(['description' => $desc, 'type' => 'deduction', 'amount' => $amount]);
                }

                $this->results[] = ['employee_name' => $employee->name, 'net_salary' => $netSalary];
            }
        });

        $this->isGenerated = true;
        $this->dispatch('payroll-generated', message: 'Penggajian berhasil digenerate!');

        $this->dispatch('alert-shown', message: 'Penggajian berhasil digenerate!', type: 'success');
    }
}; ?>

<div class="px-6 py-4">
    <h2 class="text-2xl font-bold mb-6">Generate Gaji Bulanan</h2>

    <div class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:select label="Bulan" wire:model="selectedMonth" placeholder="Pilih Bulan...">
                @foreach ($months as $num => $name)
                    <flux:select.option value="{{ $num }}">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select label="Tahun" wire:model="selectedYear" placeholder="Pilih Tahun...">
                @foreach ($years as $year)
                    <flux:select.option value="{{ $year }}">{{ $year }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="self-end">
                <flux:button type="submit" wire:click="generatePayroll" variant="primary">Generate Payroll</flux:button>
            </div>
        </div>
    </div>

    @if ($isGenerated)
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-bold mb-4">Hasil Generate Payroll</h3>
            <x-table :headers="['Nama Karyawan', 'Gaji Bersih']" :rows="$results" emptyMessage="Tidak ada hasil payroll." fixedHeader="true"
                maxHeight="540px">
                @foreach ($results as $result)
                    <x-table.row>
                        <x-table.cell class="font-medium text-gray-900">
                            {{ $result['employee_name'] }}
                        </x-table.cell>
                        <x-table.cell class="font-semibold text-green-600">
                            Rp {{ number_format($result['net_salary'], 2, ',', '.') }}
                        </x-table.cell>
                    </x-table.row>
                @endforeach
            </x-table>
        </div>
    @endif
</div>
