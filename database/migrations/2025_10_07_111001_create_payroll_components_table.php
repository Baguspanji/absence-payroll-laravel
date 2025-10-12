<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();               // Cth: Gaji Pokok, Tunjangan Transport
            $table->enum('type', ['earning', 'deduction'])->nullable(); // Jenis: pendapatan atau potongan
            $table->boolean('is_fixed')->default(true);     // Apakah jumlahnya tetap atau dihitung harian (pro-rata)
            $table->timestamps();
        });

        Schema::create('employee_payroll_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('payroll_component_id')->constrained('payroll_components')->onDelete('cascade');
            $table->decimal('amount', 15, 2); // Jumlah spesifik untuk karyawan ini
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_components');
        Schema::dropIfExists('payroll_components');
    }
};
