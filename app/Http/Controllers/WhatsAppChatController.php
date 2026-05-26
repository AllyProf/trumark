<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use App\Models\Customer;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsAppChatController extends Controller
{
    protected $whatsapp;

    public function __construct(WhatsAppService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }

    /**
     * Display the main chat dashboard
     */
    public function index()
    {
        $threads = $this->buildThreads();
        return view('whatsapp.chat', compact('threads'));
    }

    /**
     * Return threads list as JSON for real-time AJAX polling
     */
    public function threadsData()
    {
        $threads = $this->buildThreads();
        return response()->json(['success' => true, 'threads' => $threads]);
    }

    /**
     * Shared thread-building logic:
     * ONLY shows phones that have at least one incoming (status='received') WhatsApp message.
     * This prevents SMS campaign logs from polluting the WhatsApp chat dashboard.
     */
    private function buildThreads()
    {
        // Step 1: Get only phones that have at least one real incoming WhatsApp message
        $waPhones = SmsLog::where('status', 'received')
            ->pluck('phone')
            ->map(fn($p) => preg_replace('/[^0-9]/', '', $p))
            ->unique()
            ->values();

        if ($waPhones->isEmpty()) {
            return collect();
        }

        // Step 2: For each of those phones, get the latest message record (any direction)
        $maxIds = SmsLog::whereIn(DB::raw("REPLACE(REPLACE(phone, '+', ''), ' ', '')"), $waPhones)
            ->groupBy('phone')
            ->select('phone', DB::raw('MAX(id) as max_id'))
            ->pluck('max_id');

        // Step 3: Get unread count per phone (messages with status='received' not yet replied to)
        $unreadCounts = SmsLog::where('status', 'received')
            ->groupBy('phone')
            ->select('phone', DB::raw('COUNT(*) as cnt'))
            ->pluck('cnt', 'phone');

        $threads = SmsLog::whereIn('sms_logs.id', $maxIds)
            ->leftJoin('customers', function($join) {
                $join->on(DB::raw("REPLACE(customers.phone, '+', '')"), '=', DB::raw("REPLACE(sms_logs.phone, '+', '')"));
            })
            ->select(
                'sms_logs.id',
                'sms_logs.phone',
                'sms_logs.message',
                'sms_logs.status',
                'sms_logs.created_at',
                'customers.name as customer_name',
                'customers.id as customer_id'
            )
            ->orderBy('sms_logs.created_at', 'desc')
            ->get()
            ->map(function($thread) use ($unreadCounts) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $thread->phone);
                $thread->is_bot_paused = Cache::get("wa_bot_paused_" . $cleanPhone, false);
                $thread->unread_count = $unreadCounts[$thread->phone] ?? 0;
                return $thread;
            });

        return $threads;
    }

    /**
     * Search CRM customers by name or phone for the New Chat modal
     */
    public function searchCustomers(Request $request)
    {
        $q = $request->get('q', '');
        $customers = Customer::where('name', 'like', "%$q%")
            ->orWhere('phone', 'like', "%$q%")
            ->select('id', 'name', 'phone')
            ->limit(10)
            ->get();
        return response()->json($customers);
    }

    /**
     * Get message thread for a specific phone number
     */
    public function thread($phone)
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // Only load messages that belong to WhatsApp interactions:
        // - Incoming: status='received'
        // - Outgoing bot/human replies linked to this phone
        // We identify WhatsApp outgoing by sender_id IS NULL (bot) or sender_id IS NOT NULL (human)
        // and the phone matching. We exclude records where status='sent' and message does NOT
        // start with 'INCOMING' but was created by a campaign (no sender_id, no 'received' peer).
        $messages = SmsLog::where('phone', 'like', "%$cleanPhone%")
            ->where(function($q) {
                // Incoming messages from customer
                $q->whereIn('status', ['received', 'read_by_agent'])
                  // Manual replies sent by a CRM staff member
                  ->orWhereNotNull('sender_id')
                  // Bot auto-replies stored with [BOT REPLY] prefix
                  ->orWhere('message', 'like', '[BOT REPLY]%');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Build customer data for 24h window check
        $lastCustomerMsg = SmsLog::where('phone', 'like', "%$cleanPhone%")
            ->where('status', 'received')
            ->latest()
            ->first();

        $isBotPaused = Cache::get("wa_bot_paused_" . $cleanPhone, false);

        // 24h window: customer can receive free-form messages within 24h of their last inbound
        $windowOpen = $lastCustomerMsg
            ? now()->diffInHours($lastCustomerMsg->created_at, false) > -24
            : false;

        // Get CRM customer info
        $customer = Customer::where('phone', 'like', "%$cleanPhone%")->first();

        return response()->json([
            'success'      => true,
            'messages'     => $messages,
            'is_bot_paused'=> $isBotPaused,
            'window_open'  => $windowOpen,
            'customer'     => $customer ? ['id' => $customer->id, 'name' => $customer->name] : null,
        ]);
    }

    /**
     * Send manual WhatsApp reply and auto-pause the bot
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string'
        ]);

        $phone = $request->phone;
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $messageText = $request->message;

        $customer = Customer::where('phone', 'like', "%$cleanPhone%")->first();

        // Send via Meta Cloud API
        $result = $this->whatsapp->sendMessage($phone, $messageText);

        if ($result['success']) {
            // Extract wamid from Meta API response for read-receipt tracking
            $wamid = $result['response']['messages'][0]['id'] ?? null;

            // Save to logs with the whatsapp message ID
            $log = SmsLog::create([
                'customer_id'        => $customer ? $customer->id : null,
                'sender_id'          => auth()->id(),
                'phone'              => $phone,
                'message'            => $messageText,
                'status'             => 'sent',
                'whatsapp_message_id'=> $wamid,
            ]);

            // Auto-pause automated bot for 30 minutes when human replies
            Cache::put("wa_bot_paused_" . $cleanPhone, true, now()->addMinutes(30));

            return response()->json([
                'success'       => true,
                'log'           => $log,
                'is_bot_paused' => true
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to send message.'
        ], 500);
    }

    /**
     * Manually toggle bot status (Active vs. Paused) for a contact
     */
    public function toggleBot($phone)
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $key = "wa_bot_paused_" . $cleanPhone;
        
        $current = Cache::get($key, false);

        if ($current) {
            Cache::forget($key);
            $status = 'active';
        } else {
            // Pause bot for 24 hours
            Cache::put($key, true, now()->addHours(24));
            $status = 'paused';
        }

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    /**
     * Mark all incoming messages for a phone as read (clears unread count)
     */
    public function markRead($phone)
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        SmsLog::where('phone', 'like', "%$cleanPhone%")
            ->where('status', 'received')
            ->update(['status' => 'read_by_agent']);

        return response()->json(['success' => true]);
    }
}
