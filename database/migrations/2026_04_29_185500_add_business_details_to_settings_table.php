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
        // Insert default business settings
        DB::table('settings')->insert([
            [
                'key' => 'business_name',
                'value' => 'AUTOSERVICIO SHEVERE',
                'description' => 'Nombre comercial del negocio',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'business_nit',
                'value' => '901.XXX.XXX-X',
                'description' => 'NIT o Identificación Tributaria',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'business_address',
                'value' => '[DIRECCIÓN AQUÍ]',
                'description' => 'Dirección física del negocio',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'business_phone',
                'value' => '[TELÉFONO AQUÍ]',
                'description' => 'Teléfono de contacto',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'business_email',
                'value' => 'contacto@shevere.com',
                'description' => 'Correo electrónico del negocio',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'business_payment_info',
                'value' => '[BANCOS AQUÍ]',
                'description' => 'Información de cuentas bancarias para pagos',
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
        DB::table('settings')->whereIn('key', [
            'business_name',
            'business_nit',
            'business_address',
            'business_phone',
            'business_email',
            'business_payment_info'
        ])->delete();
    }
};
