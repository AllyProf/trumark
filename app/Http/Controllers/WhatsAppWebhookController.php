<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\SmsLog;

class WhatsAppWebhookController extends Controller
{
    /**
     * Webhook verification for Meta
     */
    public function verify(Request $request)
    {
        $verifyToken = 'trumark_secure_webhook_token'; // This must match what you enter in Meta
        
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode && $token) {
            if ($mode === 'subscribe' && $token === $verifyToken) {
                Log::info('WhatsApp Webhook Verified successfully.');
                return response($challenge, 200);
            }
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming WhatsApp messages and status updates
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        
        // Log the raw incoming data for debugging
        Log::info('WhatsApp Webhook Data: ' . json_encode($data));

        // Basic processing of incoming messages
        if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
            $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
            $from = $message['from']; // Customer phone number
            $text = $message['text']['body'] ?? '[Non-text message]';
            $messageId = $message['id'];

            // Find customer by phone
            $cleanPhone = preg_replace('/[^0-9]/', '', $from);
            $customer = Customer::where('phone', 'like', "%$cleanPhone%")->first();

            // Log the incoming message as an "SmsLog" for visibility in the CRM
            SmsLog::create([
                'customer_id' => $customer ? $customer->id : null,
                'phone'       => $from,
                'message'     => "INCOMING: " . $text,
                'status'      => 'received',
                'response'    => json_encode($message),
            ]);
            
            Log::info("WhatsApp Message from $from: $text");
        }

        return response('OK', 200);
    }
}
