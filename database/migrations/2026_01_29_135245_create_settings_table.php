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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('settings')->insert([
            [
                'key' => 'initial_inventory_mode',
                'value' => 'false',
                'description' => 'Modo de inventario inicial activo/inactivo',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'initial_cash_balance',
                'value' => '0',
                'description' => 'Saldo inicial de efectivo en caja',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'initial_mode_closed_at',
                'value' => null,
                'description' => 'Fecha en que se cerró el modo inicial',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
