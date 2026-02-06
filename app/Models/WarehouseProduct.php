<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class WarehouseProduct extends Model
{
     use HasFactory;

    protected $table = 'warehouse_products';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'stock_quantity'
    ];

    /* ================= RELATIONSHIPS ================= */

    // WarehouseProduct → Warehouse
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // WarehouseProduct → Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
