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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method'); // cash, credit, mixed
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained('providers');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });

        Schema::table('movements', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->constrained('sales')->onDelete('cascade');
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->onDelete('cascade');
        });

        Schema::table('credits', function (Blueprint $table) {
            $table->foreignId('sale_id')->nullable()->constrained('sales')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropColumn('sale_id');
        });

        Schema::table('movements', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
            $table->dropForeign(['purchase_id']);
            $table->dropColumn(['sale_id', 'purchase_id']);
        });

        Schema::dropIfExists('purchases');
        Schema::dropIfExists('sales');
    }
};
