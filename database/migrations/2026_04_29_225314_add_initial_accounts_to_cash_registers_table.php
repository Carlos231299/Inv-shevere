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
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->decimal('initial_nequi', 12, 2)->default(0)->after('initial_cash');
            $table->decimal('initial_bancolombia', 12, 2)->default(0)->after('initial_nequi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropColumn(['initial_nequi', 'initial_bancolombia']);
        });
    }
};
