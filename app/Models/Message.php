<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message',
        'file',
        'type',
        'is_seen'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Sender (User/Admin/Delivery Agent)
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
