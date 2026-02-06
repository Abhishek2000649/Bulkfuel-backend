<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settlement extends Model
{
    protected $fillable = [
        'delivery_agent_id',
        'total_amount',
        'from_date',
        'to_date',
        'status',
        'settlement_mode',
        'settlement_date',
    ];

    protected $casts = [
        'from_date'       => 'datetime',
        'to_date'         => 'datetime',
        'settlement_date' => 'datetime',
        'total_amount'    => 'decimal:2',
    ];

    /**
     * Settlement belongs to a delivery agent
     */
    public function deliveryAgent()
    {
        return $this->belongsTo(User::class, 'delivery_agent_id');
    }

    /**
     * Settlement has many orders
     * orders.settlement_id → settlements.id
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'settlement_id');
    }
}
