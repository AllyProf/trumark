<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiNote extends Model
{
    protected $fillable = [
        'user_id',
        'manager_id',
        'note',
        'type',
        'is_visible_to_staff',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
