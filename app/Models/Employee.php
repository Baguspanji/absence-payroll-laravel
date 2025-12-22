<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'branch_id',
        'nip',
        'name',
        'position',
        'image_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(Schedule::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function payrollComponents(): BelongsToMany
    {
        return $this->belongsToMany(PayrollComponent::class, 'employee_payroll_components', 'employee_id', 'payroll_component_id')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function employeeSaving(): HasOne
    {
        return $this->hasOne(EmployeeSaving::class);
    }

    public function generateNip(): string
    {
        $latestEmployee = self::orderBy('nip', 'desc')->first();
        $nextNumber = $latestEmployee ? ((int) substr($latestEmployee->nip, -4)) + 1 : 1;

        return date('Y').str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
