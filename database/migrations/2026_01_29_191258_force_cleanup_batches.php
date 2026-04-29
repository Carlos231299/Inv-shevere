<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fuerza bruta para limpiar los lotes que se resisten a morir
        Schema::disableForeignKeyConstraints();
        
        // Limpiamos la tabla de lotes
        DB::table('batches')->truncate();
        
        // También aseguramos que el stock de productos esté en 0
        DB::table('products')->update(['stock' => 0]);
        
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hay vuelta atrás de un truncate
    }
};
