<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'requirements' => 'array',
        'school_level' => 'array',
        'expected_purchase_date' => 'date',
        'last_contacted_at' => 'date',
        'next_follow_up_date' => 'date',
        'last_survey_sent_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($customer) {
            if (!$customer->survey_uuid) {
                $customer->survey_uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function feedbacks()
    {
        return $this->hasMany(CustomerFeedback::class);
    }

    public function salesOfficer()
    {
        return $this->belongsTo(User::class, 'sales_officer_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class);
    }
}
