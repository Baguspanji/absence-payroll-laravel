<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Payroll; // Import model Payroll

class PayrollController extends Controller
{
    public function showSlip(Payroll $payroll)
    {
        // Eager load relasi agar efisien
        $payroll->load('employee', 'details');

        return view('payroll.slip', compact('payroll'));
    }
}
