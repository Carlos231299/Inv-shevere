<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $fillable = [
        'product_sku',
        'batch_number',
        'purchase_id',
        'initial_quantity',
        'current_quantity',
        'cost_price',
        'sale_price',
        'status',
        'created_at',
        'updated_at'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_sku', 'sku');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
