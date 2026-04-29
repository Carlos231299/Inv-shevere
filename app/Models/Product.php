<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'sku';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sku',
        'name',
        'measure_type',
        'cost_price',
        'sale_price',
        'average_sale_price',
        'stock',
        'min_stock',
        'status'
    ];

    // Mutators for Uppercase
    public function setSkuAttribute($value)
    {
        $this->attributes['sku'] = strtoupper($value);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtoupper($value);
    }

    public function movements()
    {
        return $this->hasMany(Movement::class, 'product_sku', 'sku');
    }
}
