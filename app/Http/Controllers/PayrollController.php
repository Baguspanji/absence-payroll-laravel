<?php

namespace App\Http\Controllers;

use App\Models\Payroll; // Import model Payroll
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function showSlip(Payroll $payroll)
    {
        // Eager load relasi agar efisien
        $payroll->load('employee', 'details');

        return view('payroll.slip', compact('payroll'));
    }
}
