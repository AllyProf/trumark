<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logs = App\Models\UserLoginLog::whereNull('location')->orWhere('location', '')->get();
echo "Found " . $logs->count() . " records to backfill.\n";

$backfilled = 0;
foreach ($logs as $log) {
    $ip = $log->ip_address;
    $location = 'Localhost';

    if ($ip && $ip !== '127.0.0.1' && $ip !== '::1') {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)->get("http://ip-api.com/json/{$ip}");
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    $location = ($data['city'] ?? '') . ', ' . ($data['countryCode'] ?? '');
                    $location = trim($location, ', ');
                } else {
                    $location = 'Unknown';
                }
            }
        } catch (\Exception $e) {
            $location = 'Unknown';
        }
        // Small sleep to respect API limits (max 45/minute)
        usleep(300000); // 300ms
    }

    $log->update(['location' => $location]);
    $backfilled++;
    echo "Updated ID {$log->id} ({$ip}) -> {$location}\n";
}

echo "Backfilled {$backfilled} records successfully.\n";
