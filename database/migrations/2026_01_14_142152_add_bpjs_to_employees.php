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
        Schema::table('employees', function (Blueprint $table) {
            $table->date('in_date')->nullable()->after('position');
            $table->date('out_date')->nullable()->after('in_date');
            $table->date('contract_end_date')->nullable()->after('out_date');
            $table->string('bpjs_card_number')->nullable()->after('contract_end_date');
            $table->boolean('is_active_bpjs')->default(false)->after('bpjs_card_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('bpjs_card_number');
            $table->dropColumn('is_active_bpjs');
        });
    }
};
