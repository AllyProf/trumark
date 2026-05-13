<?php

namespace App\Services;

use App\Models\SystemSetting;

class SmsService
{
    private $clientId;
    private $apiKey;
    private $senderId;
    private $baseUrl = 'https://api.onfonmedia.co.ke/v1/sms/SendBulkSMS';

    public function __construct()
    {
        $this->clientId = SystemSetting::get('sms_client_id', 'trumark');
        $this->apiKey   = SystemSetting::get('sms_api_key', '78vpdtBgXuYE6kaSRVy2ZIQl4wcOWNKsH5Un0ifem3GMD1o9');
        $this->senderId = SystemSetting::get('sms_sender_id', 'TRUMARK');
    }

    /**
     * Send SMS via Onfon API
     */
    public function sendSms($phoneNumber, $message)
    {
        $phone_no = $this->formatPhoneNumber($phoneNumber);
        
        $payload = [
            'SenderId' => $this->senderId,
            'IsUnicode' => true,
            'IsFlash' => false,
            'MessageParameters' => [
                [
                    'Number' => $phone_no,
                    'Text' => $message
                ]
            ],
            'ApiKey' => $this->apiKey,
            'ClientId' => $this->clientId
        ];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                'AccessKey: ' . $this->clientId,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $result = json_decode($response, true);
        
        $success = ($httpCode == 200 || $httpCode == 201) && 
                   isset($result['ErrorCode']) && 
                   ((string)$result['ErrorCode'] === '000' || (string)$result['ErrorCode'] === '0');

        return [
            'success' => $success,
            'response' => $response,
            'http_code' => $httpCode,
            'error_message' => $result['ErrorDescription'] ?? null
        ];
    }

    /**
     * Get account balance from Onfon
     */
    public function getBalance()
    {
        $url = "https://api.onfonmedia.co.ke/v1/sms/Balance?ApiKey=" . urlencode($this->apiKey) . "&ClientId=" . urlencode($this->clientId);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'AccessKey: ' . $this->clientId,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        $result = json_decode($response, true);
        
        if ($httpCode == 200 && isset($result['Data'][0]['Credits'])) {
            return [
                'success' => true,
                'balance' => $result['Data'][0]['Credits'],
                'type'    => $result['Data'][0]['PluginType'] ?? 'SMS'
            ];
        }

        return [
            'success' => false,
            'error'   => $result['ErrorDescription'] ?? 'Unable to fetch balance'
        ];
    }

    private function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // For Onfon, they often expect the full international format like 255...
        if (substr($phone, 0, 1) == '0') {
            $phone = '255' . substr($phone, 1);
        }
        
        if (substr($phone, 0, 3) != '255') {
            $phone = '255' . $phone;
        }
        
        return $phone;
    }
}
