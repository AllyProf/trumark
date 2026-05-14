<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\WhatsAppService;

try {
    $wa = new WhatsAppService();
    $result = $wa->sendTemplateMessage('255710490428', 'hello_world', 'en_US');
    
    echo "RESULT: " . json_encode($result, JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
