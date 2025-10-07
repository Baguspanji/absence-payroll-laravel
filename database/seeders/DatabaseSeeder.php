<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
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
            'nip' => '1',
            'user_id' => 1,
            'name' => 'John Doe',
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
            'type' => 'earning',
        ]);

        \App\Models\PayrollComponent::create([
            'name' => 'Tunjangan Transportasi',
            'type' => 'earning',
        ]);

        \App\Models\PayrollComponent::create([
            'name' => 'Potongan Absen',
            'type' => 'deduction',
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
            'amount' => 200000,
        ]);
    }
}
