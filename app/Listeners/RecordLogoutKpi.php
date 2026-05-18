<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Models\UserLoginLog;

class RecordLogoutKpi
{
    public function handle(Logout $event): void
    {
        if (!$event->user) return;

        $log = UserLoginLog::where('user_id', $event->user->id)
            ->whereNull('logout_at')
            ->orderBy('login_at', 'desc')
            ->first();

        if ($log) {
            $logoutAt = now();
            $duration = $log->login_at->diffInMinutes($logoutAt);
            
            $log->update([
                'logout_at' => $logoutAt,
                'duration_minutes' => $duration
            ]);
        }

        \App\Models\AuditLog::record('User logged out', 'Authentication', $event->user->id);
    }
}
