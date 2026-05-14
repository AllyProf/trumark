<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\WhatsAppService;

$ws = new WhatsAppService();
$to = '0710490428';

echo "Testing MICRO-MESSAGE (Very Short)...\n";
$res = $ws->sendTemplateMessage($to, 'general_broadcast', 'en', [
    'customer_name' => 'Ally',
    'message_content' => 'Short test! Is it arriving now?'
]);
print_r($res);
