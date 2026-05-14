<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SystemSetting;

$keys = ['sms_api_key', 'sms_client_id', 'sms_sender_id'];

echo "--- DEEP CREDENTIAL SCAN ---\n";
foreach ($keys as $key) {
    $value = SystemSetting::where('key', $key)->first()?->value;
    if ($value === null) {
        echo "{$key}: NOT FOUND\n";
        continue;
    }
    
    $len = strlen($value);
    $trimmed = trim($value);
    $trimmedLen = strlen($trimmed);
    
    echo "Key: {$key}\n";
    echo "Value: [{$value}]\n";
    echo "Length: {$len}\n";
    
    if ($len !== $trimmedLen) {
        echo "!!! WARNING: Found " . ($len - $trimmedLen) . " hidden spaces/characters !!!\n";
    }
    echo "----------------------------\n";
}
