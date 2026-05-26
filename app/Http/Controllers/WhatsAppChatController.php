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
        // Query to get the latest message for each unique phone number
        $threadsQuery = SmsLog::select('phone', DB::raw('MAX(id) as max_id'))
            ->groupBy('phone');

        $threads = DB::table(DB::raw("({$threadsQuery->toSql()}) as t"))
            ->mergeBindings($threadsQuery->getQuery())
            ->join('sms_logs', 'sms_logs.id', '=', 't.max_id')
            ->leftJoin('customers', function($join) {
                $join->on('customers.phone', '=', 'sms_logs.phone')
                     ->orOn(DB::raw("REPLACE(customers.phone, '+', '')"), '=', 'sms_logs.phone');
            })
            ->select(
                'sms_logs.phone',
                'sms_logs.message',
                'sms_logs.status',
                'sms_logs.created_at',
                'customers.name as customer_name',
                'customers.id as customer_id'
            )
            ->orderBy('sms_logs.created_at', 'desc')
            ->get();

        // Check bot pause state for each thread
        $threads = $threads->map(function($thread) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $thread->phone);
            $thread->is_bot_paused = Cache::get("wa_bot_paused_" . $cleanPhone, false);
            return $thread;
        });

        return view('whatsapp.chat', compact('threads'));
    }

    /**
     * Get message thread for a specific phone number
     */
    public function thread($phone)
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        $messages = SmsLog::where('phone', 'like', "%$cleanPhone%")
            ->orderBy('created_at', 'asc')
            ->get();

        $isBotPaused = Cache::get("wa_bot_paused_" . $cleanPhone, false);

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'is_bot_paused' => $isBotPaused
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
            // Save to logs
            $log = SmsLog::create([
                'customer_id' => $customer ? $customer->id : null,
                'sender_id' => auth()->id(),
                'phone' => $phone,
                'message' => $messageText,
                'status' => 'sent',
            ]);

            // Auto-pause automated bot for 30 minutes when human replies
            Cache::put("wa_bot_paused_" . $cleanPhone, true, now()->addMinutes(30));

            return response()->json([
                'success' => true,
                'log' => $log,
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
}
