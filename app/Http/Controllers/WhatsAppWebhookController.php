<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\SmsLog;

class WhatsAppWebhookController extends Controller
{
    protected $whatsapp;

    public function __construct(\App\Services\WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Webhook verification for Meta
     */
    public function verify(Request $request)
    {
        $verifyToken = 'trumark_secure_webhook_token';
        
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
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
        Log::info('[WA-BOT] Incoming webhook payload: ' . json_encode($data));

        try {
            // Basic processing of incoming messages
            if (isset($data['entry'][0]['changes'][0]['value']['messages'][0])) {
                $message = $data['entry'][0]['changes'][0]['value']['messages'][0];
                $from    = $message['from'];
                $text    = trim($message['text']['body'] ?? '');

                Log::info("[WA-BOT] Message received from $from: \"$text\"");

                if (empty($text)) {
                    Log::info('[WA-BOT] Non-text message ignored.');
                    return response('OK', 200);
                }

                // Find customer by phone
                $cleanPhone = preg_replace('/[^0-9]/', '', $from);
                $customer   = Customer::where('phone', 'like', "%$cleanPhone%")->first();

                // Log incoming message to CRM
                SmsLog::create([
                    'customer_id' => $customer ? $customer->id : null,
                    'phone'       => $from,
                    'message'     => "INCOMING: " . $text,
                    'status'      => 'received',
                    'response'    => json_encode($message),
                ]);

                // ROUTING: 1. Ice Breakers -> 2. Commands -> 3. AI Fallback
                $responseMessage = $this->handleIceBreaker($text);
                if ($responseMessage) {
                    Log::info('[WA-BOT] Matched ICE BREAKER.');
                } elseif (str_starts_with($text, '/')) {
                    Log::info('[WA-BOT] Routing to COMMAND handler.');
                    $responseMessage = $this->handleCommand($text, $from);
                } else {
                    Log::info('[WA-BOT] No match — falling back to GEMINI AI.');
                    $responseMessage = $this->askGeminiAI($text);
                }

                Log::info('[WA-BOT] Response to send: ' . ($responseMessage ?? 'NULL'));

                // Dispatch reply
                if ($responseMessage) {
                    $sendResult = $this->whatsapp->sendMessage($from, $responseMessage);
                    Log::info('[WA-BOT] WhatsApp send result: ' . json_encode($sendResult));

                    SmsLog::create([
                        'customer_id' => $customer ? $customer->id : null,
                        'phone'       => $from,
                        'message'     => "[BOT REPLY] " . $responseMessage,
                        'status'      => $sendResult['success'] ? 'sent' : 'failed',
                    ]);
                }
            } else {
                // Status update (delivery receipts, etc.) — just acknowledge
                Log::info('[WA-BOT] Non-message webhook event received (status update or other).');
            }

        } catch (\Throwable $e) {
            Log::error('[WA-BOT] EXCEPTION in handle(): ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
        }

        return response('OK', 200);
    }

    /**
     * Exact matches for Ice Breaker Buttons
     */
    protected function handleIceBreaker($text)
    {
        $iceBreakers = [
            'School Books / Vitabu vya Shule' => "📚 We offer a wide range of School Books for Nursery, Primary, Secondary, and A-Level! Type /books for details.",
            'School Books' => "📚 We offer a wide range of School Books for Nursery, Primary, Secondary, and A-Level! Type /books for details.",
            'Stationery & Office Supplies' => "🛒 TRUMARK provides top-quality stationery and office supplies. Type /stationery for categories or /wholesale for bulk orders.",
            'Stationery' => "🛒 TRUMARK provides top-quality stationery and office supplies. Type /stationery for categories or /wholesale for bulk orders.",
            'Delivery Information / Usafirishaji' => "🚚 We deliver inside and outside Tanzania! Type /delivery to see our delivery options and times.",
            'Delivery Information' => "🚚 We deliver inside and outside Tanzania! Type /delivery to see our delivery options and times.",
            'Delivery' => "🚚 We deliver inside and outside Tanzania! Type /delivery to see our delivery options and times.",
            'Customer Support / Huduma kwa Wateja' => "💬 TRUMARK Customer Support is here to help! Type /support to connect with a representative.",
            'Customer Support' => "💬 TRUMARK Customer Support is here to help! Type /support to connect with a representative.",
            'Support' => "💬 TRUMARK Customer Support is here to help! Type /support to connect with a representative."
        ];

        // Case-insensitive exact match
        foreach ($iceBreakers as $breaker => $reply) {
            if (strtolower(trim($text)) === strtolower($breaker)) {
                return $reply;
            }
        }
        return null;
    }

    /**
     * Route specific commands
     */
    protected function handleCommand($text, $customerPhone)
    {
        // Extract command without slash and convert to lowercase
        $parts = explode(' ', strtolower(trim($text)));
        $command = ltrim($parts[0], '/');

        switch ($command) {
            // Core
            case 'products':
                return "📦 *Our Products*\nWe sell Stationery, School Books, and offer Printing Services. Use /books or /stationery to learn more.";
            case 'books':
                return "📚 *School Books*\nWe stock NECTA curriculum books for:\n- Nursery\n- Primary\n- Secondary (O-Level)\n- A-Level\nType /revision for past papers!";
            case 'location':
                return "📍 *TRUMARK Locations*\nVisit us at:\n1. Ubungo Branch\n2. Kimara Branch\nWe are open Monday to Saturday!";
            case 'delivery':
                return "🚚 *Delivery Services*\nWe deliver to all regions inside Tanzania, and also coordinate international shipments. Type /order to place a delivery order.";
            case 'printing':
                return "🖨️ *Printing Services*\nWe offer:\n- Printing (Color/B&W)\n- Photocopying\n- Scanning\n- Document Binding\nVisit our branches for service.";
            case 'wholesale':
                return "📦 *Wholesale Orders*\nRunning a school or business? We offer bulk discounts on stationery and books. Contact us directly using /support.";
            case 'quotation':
                return "📄 *Quotations*\nNeed an official proforma invoice for your school or institution? Please provide your requirements and our team will prepare it! Type /support to send details.";
            
            // Info
            case 'hours':
                return "⏰ *Working Hours*\nMonday - Saturday: 8:00 AM - 6:00 PM\nSunday & Public Holidays: Closed.";
            case 'payment':
                return "💳 *Payment Methods*\nWe accept:\n- M-Pesa / Tigo Pesa / Airtel Money\n- Bank Transfers (NMB, CRDB)\n- Cash (at branches)";
            case 'catalog':
                return "📑 *Product Catalog*\nWe are currently updating our PDF catalog. Please specify what you need, and our AI or support team will assist you!";
            case 'trust':
                return "⭐ *Why TRUMARK?*\nWe are reliable, offer competitive prices, and guarantee high-quality educational materials and stationery.";
            case 'pricing':
                return "💰 *Pricing*\nPrices vary by item and quantity. Wholesale prices are available. Ask our AI for a specific item, or request a /quotation.";
            
            // Edu & Products
            case 'stationery':
                return "✏️ *Stationery*\nWe have: Pens, notebooks, box files, printing paper (Reams), markers, staplers, and more!";
            case 'revision':
                return "📖 *Revision Books*\nWe have NECTA past papers and review books for Standard 4, 7, Form 2, 4, and 6.";
            case 'subjects':
                return "🔬 *Subjects*\nWe cover Physics, Chemistry, Biology, Mathematics, History, Geography, English, and Kiswahili.";
            case 'schoolpacks':
                return "🎒 *School Packs*\nSave money with our Back-to-School packages! Everything a student needs in one bundle.";
            
            // Customer Service
            case 'order':
                return "🛒 *How to Order*\nList your items here, tell us your delivery region, and we will arrange the payment and dispatch!";
            case 'track':
                return "🔍 *Order Tracking*\nTo track an existing order, please type /support and share your receipt number with our team.";
            case 'help':
                return "🆘 *Help Menu*\nCommands you can use:\n/products, /books, /stationery, /delivery, /location, /support, /order";
            case 'contact':
            case 'support':
                return $this->triggerHumanHandoff($customerPhone);

            default:
                return "❓ Unknown command: /$command. Type /help to see available options.";
        }
    }

    /**
     * Hand-off to human support
     */
    protected function triggerHumanHandoff($customerPhone)
    {
        $adminPhone = env('WHATSAPP_ADMIN_PHONE');
        
        // Notify admin silently
        if ($adminPhone) {
            $alertMsg = "🚨 *Support Request Alert!*\nCustomer +{$customerPhone} needs human assistance.\nLink to message them: https://wa.me/{$customerPhone}";
            $this->whatsapp->sendMessage($adminPhone, $alertMsg);
        }

        // Return user response
        return "TRUMARK Customer Support 😊\n\n📞 Call / WhatsApp: 0794 467 694\n\nWe assist with:\n• Orders\n• Products\n• Delivery\n• Pricing\n• Printing services\n\nReply here for immediate help, or click to chat with a human directly: https://wa.me/255794467694";
    }

    /**
     * AI Fallback via Google Gemini API
     */
    protected function askGeminiAI($text)
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return "Samahani, sijaelewa. (AI is currently offline). Tafadhali tumia /help kuona maelekezo, au /support kuongea na mhudumu wetu.";
        }

        $systemPrompt = "You are TRUMARK Stationery & Books AI assistant. Respond in Swahili with simple English when needed. Be short, helpful, professional, and friendly. You handle school books, stationery, printing services, delivery, orders, and pricing. If the user is unclear, ask a follow-up question. Do not use formatting like bolding or italics excessively.";

        try {
            $response = \Illuminate\Support\Facades\Http::post("https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    // Simulate system context via conversation turns
                    [
                        'role'  => 'user',
                        'parts' => [['text' => $systemPrompt . "\n\nCustomer message: " . $text]]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 200,
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['candidates'][0]['content']['parts'][0]['text'] ?? "Samahani, nimeshindwa kupata jibu kwa sasa. Tumia /support.";
            }

            Log::error('Gemini API Error: ' . $response->body());
            return "Samahani, mtandao wetu uko chini kidogo. Tafadhali tumia /support.";

        } catch (\Exception $e) {
            Log::error('Gemini Request Failed: ' . $e->getMessage());
            return "Samahani, nimeshindwa kuunganishwa. Tafadhali tumia /support.";
        }
    }
}
