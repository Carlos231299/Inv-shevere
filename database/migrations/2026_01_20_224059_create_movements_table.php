<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['purchase', 'sale', 'adjustment_in', 'adjustment_out', 'return']);
            $table->string('product_sku');
            $table->foreign('product_sku')->references('sku')->on('products')->onDelete('cascade');
            
            $table->decimal('quantity', 10, 3);
            $table->decimal('price_at_moment', 10, 2);
            $table->decimal('total', 10, 2);
            
            $table->foreignId('user_id')->constrained();
            $table->foreignId('client_id')->nullable()->constrained();
            
            $table->string('payment_method')->nullable(); // cash, credit, transfer
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
