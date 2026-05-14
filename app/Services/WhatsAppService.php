<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $accessToken;
    protected $phoneNumberId;
    protected $baseUrl = 'https://graph.facebook.com/v20.0';

    public function __construct()
    {
        $settings = SystemSetting::pluck('value', 'key');
        $this->accessToken = $settings['whatsapp_access_token'] ?? null;
        $this->phoneNumberId = $settings['whatsapp_phone_number_id'] ?? null;
    }

    /**
     * Send a standard text message via WhatsApp Cloud API
     * (Only works within 24-hour window)
     */
    public function sendMessage($to, $message)
    {
        if (!$this->accessToken || !$this->phoneNumberId) {
            return [
                'success' => false,
                'message' => 'WhatsApp configuration missing (Token or Phone Number ID).'
            ];
        }

        $cleanTo = preg_replace('/[^0-9]/', '', $to);

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(30)
                ->withOptions(['verify' => false])
                ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $cleanTo,
                    'type'              => 'text',
                    'text'              => [
                        'body' => $message
                    ]
                ]);

            if ($response->successful()) {
                return [
                    'success'  => true,
                    'message'  => 'WhatsApp message sent successfully.',
                    'response' => $response->json()
                ];
            }

            Log::error('WhatsApp API Error: ' . $response->body());
            return [
                'success'  => false,
                'message'  => 'WhatsApp API Error: ' . ($response->json()['error']['message'] ?? 'Unknown error'),
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send a template-based message (Required for business-initiated chats)
     */
    /**
     * Send a template-based message with support for Body and Button components
     */
    public function sendTemplateMessage($to, $templateName, $languageCode = 'en', $bodyParams = [], $buttonParams = [])
    {
        if (!$this->accessToken || !$this->phoneNumberId) {
            return [
                'success' => false,
                'message' => 'WhatsApp configuration missing.'
            ];
        }

        $cleanTo = preg_replace('/[^0-9]/', '', $to);

        // Format Body Parameters
        $formattedBodyParams = [];
        foreach ($bodyParams as $key => $value) {
            $param = ['type' => 'text', 'text' => (string)$value];
            if (is_string($key)) $param['parameter_name'] = $key;
            $formattedBodyParams[] = $param;
        }

        // Format Button Parameters
        $formattedButtonParams = [];
        foreach ($buttonParams as $value) {
            $formattedButtonParams[] = ['type' => 'text', 'text' => (string)$value];
        }

        try {
            $components = [];
            
            // Add Body Component
            if (!empty($formattedBodyParams)) {
                $components[] = [
                    'type' => 'body',
                    'parameters' => $formattedBodyParams
                ];
            }

            // Add Button Component (URL Button at index 0)
            if (!empty($formattedButtonParams)) {
                $components[] = [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => $formattedButtonParams
                ];
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'to'                => $cleanTo,
                'type'              => 'template',
                'template'          => [
                    'name' => $templateName,
                    'language' => ['code' => $languageCode],
                    'components' => $components
                ]
            ];

            $response = Http::withToken($this->accessToken)
                ->timeout(30)
                ->withOptions(['verify' => false])
                ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                return [
                    'success'  => true,
                    'message'  => 'Template message sent successfully.',
                    'response' => $response->json()
                ];
            }

            Log::error('WhatsApp API Error: ' . $response->body());
            return [
                'success'  => false,
                'message'  => 'WhatsApp API Error: ' . ($response->json()['error']['message'] ?? 'Unknown error'),
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp Template Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
}
