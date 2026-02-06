<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'order_id',
        'delivery_agent_id',
        'delivery_status',
        'order_status',
        'cancel_reason',
        'out_for_delivery_at',
        'delivered_at',
        'cancelled_at'
    ];
    protected $casts = [
        'out_for_delivery_at' => 'datetime',
        'delivered_at'        => 'datetime',
        'cancelled_at'        => 'datetime',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'delivery_agent_id');
    }
}

