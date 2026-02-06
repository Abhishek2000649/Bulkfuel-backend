<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderWarehouse extends Model
{
    use HasFactory;
     protected $fillable = [
        'order_id',
        'warehouse_id'
    ];
}
