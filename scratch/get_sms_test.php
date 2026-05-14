<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = '78vpdtBgXuYE6kaSRVy2ZIQl4wcOWNKsH5Un0ifem3GMD1o9';
$clientId = 'trumark';
$to = '255710490428';
$text = 'TRUMARK GET TEST: Testing direct URL delivery.';

echo "Testing SMS via GET request (Raw)...\n";
$url = "https://api.onfonmedia.co.ke/v1/sms/SendBulkSMS";

$response = Http::get($url, [
    'ApiKey' => $apiKey,
    'ClientId' => $clientId,
    'SenderId' => 'TRUMARK',
    'Number' => $to,
    'Text' => $text
]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
