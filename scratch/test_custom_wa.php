<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\WhatsAppService;

try {
    $wa = new WhatsAppService();
    $result = $wa->sendMessage('0744341239', 'Hello Ofeni mambo vipi ba mjogode');
    
    echo "RESULT: " . json_encode($result, JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
