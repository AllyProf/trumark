<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'category',
        'ip_address',
        'location',
        'isp',
        'user_agent'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to easily record dynamic audit logs
     */
    public static function record(string $action, string $category = 'General', ?int $userId = null)
    {
        $ip = request()->ip();
        $userAgent = request()->userAgent();
        $uid = $userId ?? auth()->id();
        
        $location = 'Localhost';
        $isp = 'Local Network';

        if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(2)->get("http://ip-api.com/json/{$ip}");
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
                $location = 'Unknown';
                $isp = 'Unknown';
            }
        }

        return self::create([
            'user_id'    => $uid,
            'action'     => $action,
            'category'   => $category,
            'ip_address' => $ip,
            'location'   => $location,
            'isp'        => $isp,
            'user_agent' => $userAgent
        ]);
    }
}
