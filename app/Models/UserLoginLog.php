<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLoginLog extends Model
{
    protected $fillable = [
        'user_id',
        'login_at',
        'logout_at',
        'duration_minutes',
        'ip_address',
        'user_agent',
        'location',
        'isp'
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDurationMinutesAttribute($value): int
    {
        if ($value !== null) {
            return (int) $value;
        }

        if (!$this->login_at) {
            return 0;
        }

        $end = $this->logout_at ?? now();

        return (int) $this->login_at->diffInMinutes($end);
    }

    public function getFormattedDurationAttribute(): string
    {
        $mins = $this->duration_minutes;
        if ($mins < 60) {
            return $mins . ' min' . ($mins === 1 ? '' : 's');
        }

        $hours = intdiv($mins, 60);
        $remaining = $mins % 60;

        return $hours . 'h' . ($remaining ? ' ' . $remaining . 'm' : '');
    }
}
