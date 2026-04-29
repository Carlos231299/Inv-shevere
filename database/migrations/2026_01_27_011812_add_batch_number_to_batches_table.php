<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->string('batch_number')->after('product_sku')->nullable();
        });
        
        // Populate existing batches with a default number based on their creation date
        $batches = DB::table('batches')->get();
        foreach ($batches as $batch) {
            $date = \Carbon\Carbon::parse($batch->created_at);
            $baseCode = 'L-00' . $date->format('md');
            DB::table('batches')->where('id', $batch->id)->update([
                'batch_number' => $baseCode
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('batch_number');
        });
    }
};
