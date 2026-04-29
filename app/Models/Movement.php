<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'product_sku',
        'batch_identifier',
        'quantity',
        'price_at_moment',
        'cost_at_moment',
        'total',
        'user_id',
        'client_id',
        'payment_method',
        'sale_id',
        'purchase_id',
        'is_initial'
    ];

    /**
     * Scope to filter only operational (non-initial) movements
     */
    public function scopeOperational($query)
    {
        return $query->where('is_initial', false);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_sku', 'sku');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function credit()
    {
        return $this->hasOne(Credit::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
