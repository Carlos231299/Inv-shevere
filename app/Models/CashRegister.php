<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    protected $fillable = [
        'user_id',
        'opened_at',
        'closed_at',
        'initial_cash',
        'initial_nequi',
        'initial_bancolombia',
        'system_cash',
        'physical_cash',
        'system_nequi',
        'physical_nequi',
        'system_bancolombia',
        'physical_bancolombia',
        'status',
        'notes'
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
