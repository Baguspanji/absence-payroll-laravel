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
        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->unsignedBigInteger('schedule_id')->nullable()->after('branch_id');
            $table->unsignedBigInteger('shift_id')->nullable()->after('schedule_id');
            $table->string('shift_name')->nullable()->after('shift_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_summaries', function (Blueprint $table) {
            $table->dropColumn(['schedule_id', 'shift_id', 'shift_name']);
        });
    }
};
