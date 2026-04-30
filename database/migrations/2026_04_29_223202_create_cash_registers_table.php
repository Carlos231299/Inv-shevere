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
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            
            $table->decimal('initial_cash', 12, 2)->default(0);
            
            $table->decimal('system_cash', 12, 2)->default(0);
            $table->decimal('physical_cash', 12, 2)->default(0);
            
            $table->decimal('system_nequi', 12, 2)->default(0);
            $table->decimal('physical_nequi', 12, 2)->default(0);
            
            $table->decimal('system_bancolombia', 12, 2)->default(0);
            $table->decimal('physical_bancolombia', 12, 2)->default(0);
            
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
