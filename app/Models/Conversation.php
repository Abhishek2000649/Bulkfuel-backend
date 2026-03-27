<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'admin_id',
        'role',
        'last_message',
        'last_message_at'
    ];

    /**
     * Messages (1 conversation → many messages)
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * User (sender side)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Admin
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}