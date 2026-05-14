<?php

// Fix the path to point to the project root
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SmsService;
use App\Services\WhatsAppService;

$phone = '0710490428';
$testMessage = "TRUMARK LIVE TEST: This is a professional verification message for multi-channel staff communications. SMS & WhatsApp active. ✅";

echo "--- STARTING LIVE TEST ---\n";
echo "Target Phone: $phone\n\n";

// 1. Test SMS
try {
    echo "1. Attempting SMS via Onfon...\n";
    $sms = new SmsService();
    $smsResult = $sms->sendSms($phone, $testMessage);
    echo "SMS Result: " . json_encode($smsResult) . "\n\n";
} catch (\Exception $e) {
    echo "SMS ERROR: " . $e->getMessage() . "\n\n";
}

// 2. Test WhatsApp
try {
    echo "2. Attempting WhatsApp via Meta Cloud API...\n";
    $wa = new WhatsAppService();
    $waResult = $wa->sendMessage($phone, $testMessage);
    echo "WhatsApp Result: " . json_encode($waResult) . "\n";
} catch (\Exception $e) {
    echo "WhatsApp ERROR: " . $e->getMessage() . "\n";
}

echo "\n--- TEST COMPLETE ---\n";
