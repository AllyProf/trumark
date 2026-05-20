<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'name', 'event_date', 'message', 'target_service', 'target_location', 'target_stage', 'status', 'created_by', 'channels'
    ];

    protected $casts = [
        'event_date' => 'date',
        'channels' => 'array'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
