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
        Schema::table('movements', function (Blueprint $table) {
            $table->boolean('is_initial')->default(false)->after('purchase_id');
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->boolean('is_initial')->default(false)->after('status');
        });

        Schema::table('account_payables', function (Blueprint $table) {
            $table->boolean('is_initial')->default(false)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movements', function (Blueprint $table) {
            $table->dropColumn('is_initial');
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->dropColumn('is_initial');
        });

        Schema::table('account_payables', function (Blueprint $table) {
            $table->dropColumn('is_initial');
        });
    }
};
