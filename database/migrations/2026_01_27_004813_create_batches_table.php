<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $product_sku) {
            $product_sku->id();
            $product_sku->string('product_sku');
            $product_sku->unsignedBigInteger('purchase_id')->nullable();
            $product_sku->decimal('initial_quantity', 12, 2);
            $product_sku->decimal('current_quantity', 12, 2);
            $product_sku->decimal('cost_price', 12, 2);
            $product_sku->decimal('sale_price', 12, 2);
            $product_sku->enum('status', ['active', 'exhausted'])->default('active');
            $product_sku->timestamps();

            $product_sku->foreign('product_sku')->references('sku')->on('products')->onDelete('cascade');
            $product_sku->foreign('purchase_id')->references('id')->on('purchases')->onDelete('cascade');
        });
        
        // Data Migration: Create initial batches from current stock
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            if ($product->stock > 0) {
                DB::table('batches')->insert([
                    'product_sku' => $product->sku,
                    'initial_quantity' => $product->stock,
                    'current_quantity' => $product->stock,
                    'cost_price' => $product->cost_price,
                    'sale_price' => $product->sale_price,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
