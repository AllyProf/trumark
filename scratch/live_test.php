<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\WhatsAppService;

$ws = new WhatsAppService();
$to = '255610836323';

echo "Testing LIVE DELIVERY to: {$to}...\n";
$res = $ws->sendTemplateMessage($to, 'general_broadcast', 'en', [
    'customer_name' => 'Test Target',
    'message_content' => 'Final Live Test from TRUMARK. Is this arriving?'
]);
print_r($res);
