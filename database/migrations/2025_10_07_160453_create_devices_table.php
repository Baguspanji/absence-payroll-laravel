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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                  // Nama mesin yg mudah diingat, cth: "Pintu Depan Kantor A"
            $table->string('serial_number')->unique();               // Serial Number unik dari mesin
            $table->foreignId('branch_id')->constrained('branches'); // Relasi ke cabang
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
