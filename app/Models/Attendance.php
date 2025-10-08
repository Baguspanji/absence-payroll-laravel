<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_nip',
        'timestamp',
        'status_scan',
        'device_sn',
        'is_processed',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_processed' => 'boolean',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_nip', 'nip');
    }
}
