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
        $this->months = collect(range(1, 12))->mapWithKeys(fn($month) => [$month => Carbon::create()->month($month)->format('F')])->toArray();
        $this->selectedYear = Carbon::now()->year;
        $this->selectedMonth = Carbon::now()->month;
    }

    public function generatePayroll()
    {
        $this->validate(['selectedMonth' => 'required', 'selectedYear' => 'required']);

        $startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $totalDaysInMonth = $startDate->daysInMonth;

        $employees = Employee::query()
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->where('users.is_active', true)->select('employees.*')
            ->get();
        $this->results = [];

        DB::transaction(function () use ($employees, $startDate, $endDate, $totalDaysInMonth) {
            foreach ($employees as $employee) {
                // 1. Ambil data rekap absensi
                $workdays = DB::table('attendance_summaries')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->count();
                $totalLateMinutes = DB::table('attendance_summaries')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('late_minutes');
                $totalOvertimeHours = DB::table('attendance_summaries')
                    ->where('employee_id', $employee->id)
                    ->whereBetween('date', [$startDate, $endDate])
                    ->sum('overtime_hours');

                // 2. Ambil semua komponen gaji milik karyawan ini
                $components = DB::table('employee_payroll_components as epc')
                    ->join('payroll_components as pc', 'epc.payroll_component_id', '=', 'pc.id')
                    ->where('epc.employee_id', $employee->id)
                    ->select('pc.name', 'pc.type', 'pc.is_fixed', 'epc.amount')
                    ->get();

                $earnings = [];
                $deductions = [];

                // 3. Proses setiap komponen
                foreach ($components as $component) {
                    $finalAmount = $component->amount;
                    // Jika tidak tetap, hitung pro-rata berdasarkan hari kerja
                    if (!$component->is_fixed) {
                        $finalAmount = ($component->amount / $totalDaysInMonth) * $workdays;
                    }

                    if ($component->type === 'earning') {
                        $earnings[$component->name] = $finalAmount;
                    } else {
                        $deductions[$component->name] = $finalAmount;
                    }
                }

                // Tambahkan komponen dinamis (lembur & keterlambatan)
                $lateComponent = $components->firstWhere('name', 'Potongan Terlambat');
                if ($lateComponent) {
                    $deductions['Potongan Terlambat'] = $totalLateMinutes * $lateComponent->amount;
                }

                $overtimeComponent = $components->firstWhere('name', 'Upah Lembur');
                if ($overtimeComponent) {
                    $earnings['Upah Lembur'] = $totalOvertimeHours * $overtimeComponent->amount; // di sini amount = rate per jam
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
        $this->dispatch('payroll-generated', message: 'Payroll berhasil digenerate!');
    }
}; ?>

<div class="p-6">
    <h2 class="text-2xl font-bold mb-6">Generate Payroll Bulanan</h2>

    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="month" class="block text-sm font-medium text-gray-700">Bulan</label>
                <select wire:model="selectedMonth" id="month"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @foreach ($months as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700">Tahun</label>
                <select wire:model="selectedYear" id="year"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    @foreach ($years as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="self-end">
                <button wire:click="generatePayroll"
                    class="w-full inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                    Generate Payroll
                </button>
            </div>
        </div>
    </div>

    @if ($isGenerated)
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-lg font-bold mb-4">Hasil Generate Payroll</h3>
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th class="px-6 py-3">Nama Karyawan</th>
                        <th class="px-6 py-3">Gaji Bersih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($results as $result)
                        <tr class="bg-white border-b">
                            <td class="px-6 py-4 font-medium">{{ $result['employee_name'] }}</td>
                            <td class="px-6 py-4">Rp {{ number_format($result['net_salary'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
