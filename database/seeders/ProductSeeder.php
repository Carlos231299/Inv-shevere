<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            ['sku' => '1001', 'name' => 'Carne de Res - Pulpa', 'measure_type' => 'kg', 'cost_price' => 25000, 'sale_price' => 32000, 'min_stock' => 5],
            ['sku' => '1002', 'name' => 'Carne de Res - Molida', 'measure_type' => 'kg', 'cost_price' => 20000, 'sale_price' => 26000, 'min_stock' => 5],
            ['sku' => '1003', 'name' => 'Costilla de Res', 'measure_type' => 'kg', 'cost_price' => 18000, 'sale_price' => 24000, 'min_stock' => 5],
            ['sku' => '1004', 'name' => 'Lomo Fino', 'measure_type' => 'kg', 'cost_price' => 35000, 'sale_price' => 45000, 'min_stock' => 3],
            ['sku' => '1005', 'name' => 'Punta de Anca', 'measure_type' => 'kg', 'cost_price' => 30000, 'sale_price' => 40000, 'min_stock' => 3],
            ['sku' => '2001', 'name' => 'Pierna de Cerdo', 'measure_type' => 'kg', 'cost_price' => 18000, 'sale_price' => 24000, 'min_stock' => 5],
            ['sku' => '2002', 'name' => 'Lomo de Cerdo', 'measure_type' => 'kg', 'cost_price' => 20000, 'sale_price' => 28000, 'min_stock' => 5],
            ['sku' => '2003', 'name' => 'Costilla de Cerdo', 'measure_type' => 'kg', 'cost_price' => 22000, 'sale_price' => 29000, 'min_stock' => 5],
            ['sku' => '2004', 'name' => 'Chicharrón', 'measure_type' => 'kg', 'cost_price' => 15000, 'sale_price' => 20000, 'min_stock' => 5],
            ['sku' => '2005', 'name' => 'Tocino', 'measure_type' => 'kg', 'cost_price' => 12000, 'sale_price' => 16000, 'min_stock' => 5],
            ['sku' => '3001', 'name' => 'Pollo Entero', 'measure_type' => 'kg', 'cost_price' => 9000, 'sale_price' => 12000, 'min_stock' => 10],
            ['sku' => '3002', 'name' => 'Pechuga de Pollo', 'measure_type' => 'kg', 'cost_price' => 14000, 'sale_price' => 19000, 'min_stock' => 8],
            ['sku' => '3003', 'name' => 'Muslos de Pollo', 'measure_type' => 'kg', 'cost_price' => 8000, 'sale_price' => 11000, 'min_stock' => 8],
            ['sku' => '3004', 'name' => 'Alas de Pollo', 'measure_type' => 'kg', 'cost_price' => 10000, 'sale_price' => 14000, 'min_stock' => 8],
            ['sku' => '4001', 'name' => 'Queso Costeño', 'measure_type' => 'kg', 'cost_price' => 20000, 'sale_price' => 26000, 'min_stock' => 5],
            ['sku' => '4002', 'name' => 'Suero Costeño', 'measure_type' => 'unit', 'cost_price' => 5000, 'sale_price' => 7000, 'min_stock' => 10],
            ['sku' => '4003', 'name' => 'Mantequilla', 'measure_type' => 'unit', 'cost_price' => 8000, 'sale_price' => 10500, 'min_stock' => 10],
            ['sku' => '5001', 'name' => 'Chorizo Santarrosano', 'measure_type' => 'unit', 'cost_price' => 2000, 'sale_price' => 3500, 'min_stock' => 20],
            ['sku' => '5002', 'name' => 'Salchichón Cervecero', 'measure_type' => 'unit', 'cost_price' => 15000, 'sale_price' => 20000, 'min_stock' => 5],
            ['sku' => '5003', 'name' => 'Butifarra', 'measure_type' => 'unit', 'cost_price' => 12000, 'sale_price' => 16000, 'min_stock' => 10],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['sku' => $product['sku']], array_merge($product, ['stock' => 10, 'status' => 'active']));
        }
    }
}
