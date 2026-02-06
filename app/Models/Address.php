<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'address',
        'city',
        'state',
        'pincode'
    ];

    /* ================= RELATIONSHIP ================= */

    // Address → User (Belongs To)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
