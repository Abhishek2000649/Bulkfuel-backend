<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'price',
        'stock',
        'description'
    ];

    protected $appends = ['totalStock'];

    /* ======================
       RELATIONS
    ====================== */

    public function warehouseProducts()
    {
        return $this->hasMany(WarehouseProduct::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /* ======================
       ACCESSOR
    ====================== */

public function getTotalStockAttribute()
{
    return $this->warehouseProducts->sum('stock_quantity');
}

 public function warehouses() {
        return $this->belongsToMany(Warehouse::class, 'warehouse_products')
                    ->withPivot('stock');
    }

}
