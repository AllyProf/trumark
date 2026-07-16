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
     * Translate common Meta WhatsApp error codes into staff-friendly messages.
     */
    public static function humanizeMetaError(?int $code, ?string $fallback = null): string
    {
        return match ($code) {
            131042 => 'WhatsApp billing issue: Meta has blocked sends due to unsettled payments. Open Meta Business Manager → Billing and pay any outstanding balance.',
            131026 => 'WhatsApp could not deliver to this number. The customer may not have WhatsApp on this phone.',
            131047 => 'WhatsApp 24-hour window expired. Use an approved template message or wait for the customer to reply.',
            132000, 132001, 132005 => 'WhatsApp template error: check template name, language, and parameters in Meta Business Manager.',
            133010 => 'WhatsApp phone number is not registered or not linked correctly in Meta.',
            default => $fallback ?? 'WhatsApp delivery failed. Check Meta Business Manager for details.',
        };
    }

    /**
     * Parse Meta API or webhook status error payloads.
     */
    public static function extractErrorInfo(?array $payload): array
    {
        $code = null;
        $rawMessage = null;

        if (!empty($payload['error'])) {
            $code = isset($payload['error']['code']) ? (int) $payload['error']['code'] : null;
            $rawMessage = $payload['error']['message'] ?? null;
        } elseif (!empty($payload['errors'][0])) {
            $code = isset($payload['errors'][0]['code']) ? (int) $payload['errors'][0]['code'] : null;
            $rawMessage = $payload['errors'][0]['message'] ?? null;
        }

        return [
            'code' => $code,
            'raw_message' => $rawMessage,
            'user_message' => self::humanizeMetaError($code, $rawMessage),
            'is_billing_issue' => $code === 131042,
        ];
    }

    protected function failedApiResponse($response): array
    {
        $body = $response->json();
        $info = self::extractErrorInfo(is_array($body) ? $body : null);

        Log::error('WhatsApp API Error: ' . $response->body());

        return [
            'success' => false,
            'message' => $info['user_message'],
            'error_code' => $info['code'],
            'is_billing_issue' => $info['is_billing_issue'],
            'response' => $body,
        ];
    }

    public static function logResponseFromResult(array $result): ?string
    {
        if (!empty($result['response'])) {
            return is_array($result['response']) ? json_encode($result['response']) : (string) $result['response'];
        }

        if (!empty($result['message'])) {
            return json_encode([
                'error_code' => $result['error_code'] ?? null,
                'message' => $result['message'],
            ]);
        }

        return null;
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
            return $this->failedApiResponse($response);

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
            return $this->failedApiResponse($response);

        } catch (\Exception $e) {
            Log::error('WhatsApp Template Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Download inbound media (e.g. payment screenshot) from Meta WhatsApp Cloud API.
     */
    public function downloadMedia(string $mediaId): ?array
    {
        if (!$this->accessToken || !$mediaId) {
            return null;
        }

        try {
            $metaResponse = Http::withToken($this->accessToken)
                ->timeout(30)
                ->withOptions(['verify' => false])
                ->get("{$this->baseUrl}/{$mediaId}");

            if (!$metaResponse->successful()) {
                Log::error('WhatsApp media metadata error: ' . $metaResponse->body());
                return null;
            }

            $mediaUrl = $metaResponse->json('url');
            $mimeType = $metaResponse->json('mime_type', 'image/jpeg');

            if (!$mediaUrl) {
                return null;
            }

            $fileResponse = Http::withToken($this->accessToken)
                ->timeout(60)
                ->withOptions(['verify' => false])
                ->get($mediaUrl);

            if (!$fileResponse->successful()) {
                Log::error('WhatsApp media download error: ' . $fileResponse->status());
                return null;
            }

            return [
                'content' => $fileResponse->body(),
                'mime_type' => $mimeType,
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp downloadMedia exception: ' . $e->getMessage());
            return null;
        }
    }
}
