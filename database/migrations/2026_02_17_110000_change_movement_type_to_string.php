<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Option 1: Modify Enum directly (Database dependent, usually requires raw SQL)
        // Since we are using MySQL/MariaDB likely (based on previous logs showing typical LAMP stack paths)
        
        // We will try to change the column to string to be more flexible, or just append the value.
        // Changing to string is safer for future flexibility.
        
        Schema::table('movements', function (Blueprint $table) {
            $table->string('type', 50)->change();
        });
    }

    public function down(): void
    {
        // Revert is risky if we have data, but we can try to revert to enum
        // For now, we will leave it as string or just do nothing.
    }
};
