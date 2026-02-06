<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = [
        'name','address','city','state','pincode','phone'
    ];

    public function orders()
{
    return $this->belongsToMany(
        Order::class,
        'order_warehouses'
    );
}

}

