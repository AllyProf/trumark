<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpiActivity extends Model
{
    protected $fillable = [
        'user_id',
        'customer_id',
        'performed_by',
        'activity_type',
        'activity_code',
        'points',
        'description',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
