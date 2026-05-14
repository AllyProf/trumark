<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use App\Models\SystemSetting;

$settings = SystemSetting::pluck('value', 'key');
$accessToken = $settings['whatsapp_access_token'];
$phoneNumberId = $settings['whatsapp_phone_number_id'];

$to = '255710490428';
$url = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";

echo "Testing WhatsApp with NAMED PARAMETERS...\n";

$payload = [
    'messaging_product' => 'whatsapp',
    'to' => $to,
    'type' => 'template',
    'template' => [
        'name' => 'general_broadcast',
        'language' => ['code' => 'en'],
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    [
                        'type' => 'text',
                        'parameter_name' => 'customer_name',
                        'text' => 'Ally'
                    ],
                    [
                        'type' => 'text',
                        'parameter_name' => 'message_content',
                        'text' => 'This is a live test with NAMED parameters! Your system is officially cutting-edge.'
                    ]
                ]
            ]
        ]
    ]
];

$response = Http::withToken($accessToken)->post($url, $payload);
print_r($response->json());
