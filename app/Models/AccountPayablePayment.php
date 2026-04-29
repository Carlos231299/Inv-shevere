<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountPayablePayment extends Model
{
    protected $fillable = ['account_payable_id', 'amount', 'payment_method', 'payment_date'];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function accountPayable()
    {
        return $this->belongsTo(AccountPayable::class);
    }
}
