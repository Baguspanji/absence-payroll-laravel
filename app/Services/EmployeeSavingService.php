<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeSavingTransaction;
use Illuminate\Support\Facades\DB;

class EmployeeSavingService
{
    public function deposit(Employee $employee, float $amount, ?string $description = null, ?int $createdBy = null): EmployeeSavingTransaction
    {
        return DB::transaction(function () use ($employee, $amount, $description, $createdBy) {
            $saving = $employee->employeeSaving()->firstOrCreate(['employee_id' => $employee->id]);

            $balanceBefore = $saving->balance ?? 0;
            $balanceAfter = $balanceBefore + $amount;

            $saving->update(['balance' => $balanceAfter]);

            return $saving->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description ?? 'Setoran tabungan',
                'created_by' => $createdBy,
            ]);
        });
    }

    public function withdraw(Employee $employee, float $amount, ?string $description = null, ?int $createdBy = null): EmployeeSavingTransaction
    {
        return DB::transaction(function () use ($employee, $amount, $description, $createdBy) {
            $saving = $employee->employeeSaving;

            if (! $saving || $saving->balance < $amount) {
                throw new \Exception('Saldo tidak mencukupi');
            }

            $balanceBefore = $saving->balance;
            $balanceAfter = $balanceBefore - $amount;

            $saving->update(['balance' => $balanceAfter]);

            return $saving->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $description ?? 'Penarikan tabungan',
                'created_by' => $createdBy,
            ]);
        });
    }
}
