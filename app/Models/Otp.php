<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
     protected $fillable = [
        'email',
        'otp',
        'expires_at'
    ];
     protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function isExpired()
    {
        return now()->gt($this->expires_at);
    }
}
