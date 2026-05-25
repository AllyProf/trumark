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
     * Send an interactive message with up to 3 reply buttons
     * @param string $to          Recipient phone number
     * @param string $bodyText    Main message body
     * @param array  $buttons     Array of ['id'=>'...', 'title'=>'...'] (max 3)
     * @param string $headerText  Optional header text
     * @param string $footerText  Optional footer text
     */
    public function sendInteractiveButtons($to, $bodyText, array $buttons, $headerText = '', $footerText = '')
    {
        if (!$this->accessToken || !$this->phoneNumberId) {
            return ['success' => false, 'message' => 'WhatsApp configuration missing.'];
        }

        $cleanTo = preg_replace('/[^0-9]/', '', $to);

        // Build buttons array (max 3)
        $formattedButtons = [];
        foreach (array_slice($buttons, 0, 3) as $btn) {
            $formattedButtons[] = [
                'type'  => 'reply',
                'reply' => [
                    'id'    => $btn['id'],
                    'title' => mb_substr($btn['title'], 0, 20) // WhatsApp max 20 chars
                ]
            ];
        }

        $interactive = [
            'type' => 'button',
            'body' => ['text' => $bodyText],
            'action' => ['buttons' => $formattedButtons],
        ];

        if (!empty($headerText)) {
            $interactive['header'] = ['type' => 'text', 'text' => $headerText];
        }
        if (!empty($footerText)) {
            $interactive['footer'] = ['text' => $footerText];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(30)
                ->withOptions(['verify' => false])
                ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $cleanTo,
                    'type'              => 'interactive',
                    'interactive'       => $interactive,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            Log::error('WA Interactive Buttons Error: ' . $response->body());
            return ['success' => false, 'message' => $response->json()['error']['message'] ?? 'Unknown error'];

        } catch (\Exception $e) {
            Log::error('WA Interactive Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send an interactive List Message with up to 10 rows/options
     * @param string $to           Recipient phone number
     * @param string $bodyText     Main message body
     * @param string $buttonLabel  The button label that opens the list
     * @param array  $sections     Array of sections: [['title'=>'', 'rows'=>[['id'=>'','title'=>'','description'=>'']]]]
     * @param string $headerText   Optional header text
     * @param string $footerText   Optional footer text
     */
    public function sendListMessage($to, $bodyText, $buttonLabel, array $sections, $headerText = '', $footerText = '')
    {
        if (!$this->accessToken || !$this->phoneNumberId) {
            return ['success' => false, 'message' => 'WhatsApp configuration missing.'];
        }

        $cleanTo = preg_replace('/[^0-9]/', '', $to);

        $interactive = [
            'type' => 'list',
            'body' => ['text' => $bodyText],
            'action' => [
                'button'   => mb_substr($buttonLabel, 0, 20),
                'sections' => $sections,
            ],
        ];

        if (!empty($headerText)) {
            $interactive['header'] = ['type' => 'text', 'text' => $headerText];
        }
        if (!empty($footerText)) {
            $interactive['footer'] = ['text' => $footerText];
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(30)
                ->withOptions(['verify' => false])
                ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $cleanTo,
                    'type'              => 'interactive',
                    'interactive'       => $interactive,
                ]);

            if ($response->successful()) {
                return ['success' => true, 'response' => $response->json()];
            }

            Log::error('WA List Message Error: ' . $response->body());
            return ['success' => false, 'message' => $response->json()['error']['message'] ?? 'Unknown error'];

        } catch (\Exception $e) {
            Log::error('WA List Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
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
