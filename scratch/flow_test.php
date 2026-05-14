<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SmsService;
use App\Services\WhatsAppService;
use App\Models\SystemSetting;

$phone = '0710490428';
$name = 'Test User';
$email = 'test@example.com';
$pass = 'TESTPASS';

echo "--- SIMULATING STAFF REGISTRATION FLOW ---\n";

// 1. SMS
$sms = new SmsService();
$smsMsg = "Welcome to TruMark, {$name}. Your account is ready. User: {$email}, Pass: {$pass}.";
echo "Sending SMS (Flash)...";
$smsRes = $sms->sendSms($phone, $smsMsg);
print_r($smsRes);

// 2. WhatsApp
$ws = new WhatsAppService();
$waTemplate = SystemSetting::where('key', 'whatsapp_template_staff_welcome')->first()?->value;
echo "Using Template: " . ($waTemplate ?? 'NONE') . "\n";

$waMsg = "Welcome to TruMark, {$name}. Your account is ready. User: {$email}, Pass: {$pass}.";
if ($waTemplate) {
    echo "Sending WhatsApp Template...";
    $cleanWaMsg = preg_replace('/\s+/', ' ', $waMsg);
    $waRes = $ws->sendTemplateMessage($phone, $waTemplate, 'en', [
        'customer_name' => $name,
        'message_content' => $cleanWaMsg
    ]);
} else {
    echo "Sending WhatsApp Text...";
    $waRes = $ws->sendMessage($phone, $waMsg);
}
print_r($waRes);
