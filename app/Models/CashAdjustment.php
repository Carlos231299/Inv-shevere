<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashAdjustment extends Model
{
    protected $fillable = [
        'cash_register_id',
        'type',
        'amount',
        'payment_method',
        'description',
        'user_id'
    ];

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }
}
