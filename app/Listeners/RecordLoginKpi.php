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
        $ip = $this->request->ip();
        $location = 'Localhost';
        $isp = 'Local Network';

        if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(3)->get("http://ip-api.com/json/{$ip}");
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['status']) && $data['status'] === 'success') {
                        $location = ($data['city'] ?? '') . ', ' . ($data['countryCode'] ?? '');
                        $location = trim($location, ', ');
                        $isp = $data['isp'] ?? 'Unknown';
                    } else {
                        $location = 'Unknown';
                        $isp = 'Unknown';
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to fetch login location for IP {$ip}: " . $e->getMessage());
                $location = 'Unknown';
                $isp = 'Unknown';
            }
        }

        // Close any previously unclosed sessions for this user before starting a new one
        $openSessions = UserLoginLog::where('user_id', $event->user->id)
            ->whereNull('logout_at')
            ->get();

        foreach ($openSessions as $session) {
            $loginTime = \Carbon\Carbon::parse($session->login_at);
            // If the old session is less than 8 hours old, cap its duration to now.
            // If it's over 8 hours old, cap duration at 480 mins (8 hours).
            $diff = $loginTime->diffInMinutes(now());
            $duration = min($diff, 480);
            
            $session->update([
                'logout_at' => $loginTime->addMinutes($duration),
                'duration_minutes' => $duration
            ]);
        }

        UserLoginLog::create([
            'user_id'    => $event->user->id,
            'login_at'   => now(),
            'ip_address' => $ip,
            'user_agent' => $this->request->userAgent(),
            'location'   => $location,
            'isp'        => $isp,
        ]);

        \App\Models\AuditLog::record('User logged in', 'Authentication', $event->user->id);
    }
}
