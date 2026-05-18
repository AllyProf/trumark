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

        UserLoginLog::create([
            'user_id'    => $event->user->id,
            'login_at'   => now(),
            'ip_address' => $ip,
            'user_agent' => $this->request->userAgent(),
            'location'   => $location,
            'isp'        => $isp,
        ]);
    }
}
