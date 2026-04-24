<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
    'user_id',

    'phone_number',
    'alternate_phone',

    'address',

    'house_no',
    'building_name',
    'street',
    'area',
    'landmark',

    'city',
    'state',
    'pincode',

    'latitude',
    'longitude',

    'delivery_instructions',

    'is_current'
];

    /* ================= RELATIONSHIP ================= */

    // Address → User (Belongs To)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
