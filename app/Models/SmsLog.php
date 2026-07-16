<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'customer_id',
        'sender_id',
        'phone',
        'message',
        'media_path',
        'status',
        'response',
        'whatsapp_message_id',
    ];

    public function mediaUrl(): ?string
    {
        if (empty($this->media_path)) {
            return null;
        }

        return asset('storage/' . ltrim($this->media_path, '/'));
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
