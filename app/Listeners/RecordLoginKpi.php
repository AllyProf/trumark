<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\UserLoginLog;
use Illuminate\Http\Request;

class RecordLoginKpi
{
    public function __construct(protected Request $request) {}

    public function handle(Login $event): void
    {
        UserLoginLog::create([
            'user_id'    => $event->user->id,
            'login_at'   => now(),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
