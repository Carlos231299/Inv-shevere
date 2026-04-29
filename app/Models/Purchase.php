<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
        'provider_id',
        'user_id',
        'total_amount',
    ];

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function accountPayable()
    {
        // Since we don't have a direct purchase_id in AP table (Phase 2 limitation), we link by description
        // Ideally we should add purchase_id to AP table, but for now this works as per Controller logic
        return $this->hasOne(AccountPayable::class, 'description', 'description_match')
                    ->withDefault(); 
    }
    
    // Accessor to match the description format "Compra #ID"
    public function getDescriptionMatchAttribute()
    {
        return "Compra #{$this->id}";
    }
}
