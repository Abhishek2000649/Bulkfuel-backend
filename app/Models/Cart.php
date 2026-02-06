<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    // app/Models/Cart.php
    protected $fillable = ['user_id','product_id','quantity'];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }


}
