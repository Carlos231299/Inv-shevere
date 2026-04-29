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
        // Add ON UPDATE CASCADE to batches table
        Schema::table('batches', function (Blueprint $table) {
            $table->dropForeign(['product_sku']);
            $table->foreign('product_sku')
                  ->references('sku')
                  ->on('products')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });

        // Add ON UPDATE CASCADE to movements table
        Schema::table('movements', function (Blueprint $table) {
            $table->dropForeign(['product_sku']);
            $table->foreign('product_sku')
                  ->references('sku')
                  ->on('products')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropForeign(['product_sku']);
            $table->foreign('product_sku')
                  ->references('sku')
                  ->on('products')
                  ->onDelete('cascade');
        });

        Schema::table('movements', function (Blueprint $table) {
            $table->dropForeign(['product_sku']);
            $table->foreign('product_sku')
                  ->references('sku')
                  ->on('products')
                  ->onDelete('cascade');
        });
    }
};
