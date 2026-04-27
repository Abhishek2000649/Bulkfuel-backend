<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OrderAddress;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'settlement_id',
        'total_amount',
        'warehouse_id',
        'order_address_id',
        'pincode',
        'payment_method',
        'payment_status',
        'settlement_status',
        'delivery_otp',
        'otp_verified_at',

    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Order → Order Items (IMPORTANT)
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Order → Payment (optional)
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function delivery()
    {
        return $this->hasOne(Delivery::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouses()
    {
        return $this->belongsToMany(
            Warehouse::class,
            'order_warehouses'
        );
    }

    public function settlement()
    {
        return $this->belongsTo(Settlement::class);
    }
    public function orderAddress()
    {
        return $this->belongsTo(OrderAddress::class, 'order_address_id');
    }
}
