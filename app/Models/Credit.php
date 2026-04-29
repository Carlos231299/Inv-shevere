<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'movement_id',
        'sale_id',
        'description',
        'total_debt',
        'paid_amount',
        'status',
        'is_initial'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function movement()
    {
        return $this->belongsTo(Movement::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(CreditPayment::class);
    }
}
