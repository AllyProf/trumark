<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = '78vpdtBgXuYE6kaSRVy2ZIQl4wcOWNKsH5Un0ifem3GMD1o9';
$clientId = 'trumark';
$to = '255710490428';

echo "Testing HYBRID POST (Credentials in URL)...\n";
$url = "https://api.onfonmedia.co.ke/v1/sms/SendBulkSMS?ApiKey=" . urlencode($apiKey) . "&ClientId=" . urlencode($clientId);

$response = Http::withHeaders([
    'AccessKey' => $clientId,
    'Content-Type' => 'application/json'
])->post($url, [
    'SenderId' => 'TRUMARK',
    'MessageParameters' => [
        ['Number' => $to, 'Text' => 'HYBRID POST TEST']
    ]
]);

print_r($response->json());
echo "\n";
