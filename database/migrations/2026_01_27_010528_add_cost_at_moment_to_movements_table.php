<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->decimal('cost_at_moment', 12, 2)->after('price_at_moment')->nullable();
        });

        // Populate existing movements cost if possible (optional but good)
        DB::table('movements')->update(['cost_at_moment' => DB::raw('price_at_moment')]);
    }

    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropColumn('cost_at_moment');
        });
    }
};
