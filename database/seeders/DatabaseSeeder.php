<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(count: 10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@admin.com',
            'role' => 'admin',
        ]);

        // create branchs row
        \App\Models\Branch::create([
            'name' => 'Head Office',
            'address' => 'Jl. Merdeka No. 1, Jakarta',
        ]);

        // create employees row
        \App\Models\Employee::create([
            'nip' => '20250001',
            'user_id' => 1,
            'name' => 'Administrator',
            'position' => 'Manager',
            'branch_id' => 1,
        ]);

        // create shifts row
        \App\Models\Shift::create([
            'name' => 'Shift Pagi',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // create schedules row
        \App\Models\Schedule::create([
            'employee_id' => 1,
            'shift_id' => 1,
            'date' => date('Y-m-d'),
        ]);

        // create payroll components
        \App\Models\PayrollComponent::create([
            'name' => 'Gaji Pokok',
            'type' => null,
            'is_fixed' => false,
        ]);

        \App\Models\PayrollComponent::create([
            'name' => 'Tunjangan Transportasi',
            'type' => 'earning',
            'is_fixed' => true,
        ]);

        \App\Models\PayrollComponent::create([
            'name' => 'Upah Lembur',
            'type' => null,
            'is_fixed' => false,
        ]);

        \App\Models\PayrollComponent::create([
            'name' => 'Potongan Tabungan',
            'type' => null,
            'is_fixed' => true,
        ]);

        \App\Models\PayrollComponent::create([
            'name' => 'Potongan Voucher',
            'type' => 'deduction',
            'is_fixed' => true,
        ]);

        \App\Models\PayrollComponent::create([
            'name' => 'Potongan Terlambat',
            'type' => null,
            'is_fixed' => false,
        ]);

        \App\Models\PayrollComponent::create([
            'name' => 'Tunjangan BJPS',
            'type' => 'deduction',
            'is_fixed' => true,
        ]);

        // create employee payroll components
        \App\Models\EmployeePayrollComponent::create([
            'employee_id' => 1,
            'payroll_component_id' => 1,
            'amount' => 5000000,
        ]);

        \App\Models\EmployeePayrollComponent::create([
            'employee_id' => 1,
            'payroll_component_id' => 2,
            'amount' => 500000,
        ]);

        \App\Models\EmployeePayrollComponent::create([
            'employee_id' => 1,
            'payroll_component_id' => 3,
            'amount' => 100000,
        ]);

        \App\Models\EmployeePayrollComponent::create([
            'employee_id' => 1,
            'payroll_component_id' => 4,
            'amount' => 20000,
        ]);

        \App\Models\EmployeePayrollComponent::create([
            'employee_id' => 1,
            'payroll_component_id' => 5,
            'amount' => 35000,
        ]);
    }
}
