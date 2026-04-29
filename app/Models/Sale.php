<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'total_amount',
        'discount',
        'payment_method',
        'received_amount', 
        'change_amount'
    ];

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function salePayments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
    // Simplification: A Sale can have one credit record if it was fully or partially credit
    public function credit()
    {
        return $this->hasOne(Credit::class);
    }
}
