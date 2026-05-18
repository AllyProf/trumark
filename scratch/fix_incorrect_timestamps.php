<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logs = App\Models\UserLoginLog::get();
echo "Found " . $logs->count() . " records to inspect/fix.\n";

$fixedCount = 0;
foreach ($logs as $log) {
    $realLogin = $log->created_at;
    $log->login_at = $realLogin;

    if ($log->logout_at) {
        // Recalculate duration using real UTC dates
        $duration = $realLogin->diffInMinutes($log->logout_at);
        $log->duration_minutes = $duration;
    } else {
        $log->duration_minutes = 0;
    }

    $log->save();
    $fixedCount++;
}

echo "Restored and recalculated {$fixedCount} records successfully.\n";
