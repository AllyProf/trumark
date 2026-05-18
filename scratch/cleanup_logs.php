<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$groups = App\Models\UserLoginLog::get()->groupBy(function($log) {
    return $log->user_id . '_' . $log->login_at->toIso8601String();
});
$deletedCount = 0;
foreach ($groups as $group) {
    if ($group->count() > 1) {
        $keep = $group->whereNotNull('logout_at')->first() ?? $group->first();
        foreach ($group as $log) {
            if ($log->id !== $keep->id) {
                $log->delete();
                $deletedCount++;
            }
        }
    }
}
echo "Deleted {$deletedCount} duplicate logs.\n";
