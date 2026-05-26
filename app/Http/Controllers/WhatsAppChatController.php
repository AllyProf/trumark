<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use App\Models\Customer;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
        $user = auth()->user();

        $officers = collect();
        if ($user->role === 'super_admin' || $user->role === 'manager') {
            $officers = \App\Models\User::whereIn('role', ['sales_officer', 'manager', 'super_admin'])
                ->when($user->role === 'manager', function($q) use ($user) {
                    return $q->where('branch_id', $user->branch_id);
                })
                ->get();
        }

        return view('whatsapp.chat', compact('threads', 'officers'));
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
        // Step 1: Get phones that have ever had a real incoming WhatsApp message.
        // Include BOTH 'received' (unread) AND 'read_by_agent' (already opened by staff)
        // so that marking a thread as read does NOT remove it from the list.
        $waPhones = SmsLog::whereIn('status', ['received', 'read_by_agent'])
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

        $user = Auth::user();
        $query = SmsLog::whereIn('sms_logs.id', $maxIds)
            ->leftJoin('customers', function($join) {
                $join->on(DB::raw("REPLACE(customers.phone, '+', '')"), '=', DB::raw("REPLACE(sms_logs.phone, '+', '')"));
            })
            ->leftJoin('users as officers', 'officers.id', '=', 'customers.sales_officer_id');

        // Filter threads for Sales Officers: only assigned to them OR unassigned (including no CRM profile)
        if ($user->role === 'sales_officer') {
            $query->where(function($q) use ($user) {
                $q->where('customers.sales_officer_id', $user->id)
                  ->orWhereNull('customers.sales_officer_id')
                  ->orWhereNull('customers.id');
            });
        }

        $threads = $query->select(
                'sms_logs.id',
                'sms_logs.phone',
                'sms_logs.message',
                'sms_logs.status',
                'sms_logs.created_at',
                'customers.name as customer_name',
                'customers.id as customer_id',
                'customers.sales_officer_id as sales_officer_id',
                'officers.name as sales_officer_name'
            )
            ->orderBy('sms_logs.created_at', 'desc')
            ->get()
            ->map(function($thread) use ($unreadCounts) {
                $cleanPhone = preg_replace('/[^0-9]/', '', $thread->phone);
                $thread->is_bot_paused = $this->checkBotPausedStatus($cleanPhone);
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
        $user = Auth::user();

        // 1. Get CRM customer info
        $customer = Customer::with('salesOfficer')->where(DB::raw("REPLACE(phone, '+', '')"), '=', $cleanPhone)->first();

        // Security check for Sales Officers: prevent accessing threads assigned to other officers
        if ($user->role === 'sales_officer' && $customer) {
            if ($customer->sales_officer_id !== null && $customer->sales_officer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Huna ruhusa ya kuona mazungumzo ya mteja huyu (Kuna afisa mwingine amekabidhiwa).'
                ], 403);
            }
        }

        // Only load messages that belong to WhatsApp interactions:
        // - Incoming: status='received'
        // - Outgoing bot/human replies linked to this phone
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

        $isBotPaused = $this->checkBotPausedStatus($cleanPhone);

        // 24h window: customer can receive free-form messages within 24h of their last inbound
        $windowOpen = $lastCustomerMsg
            ? now()->diffInHours($lastCustomerMsg->created_at, false) > -24
            : false;

        return response()->json([
            'success'            => true,
            'messages'           => $messages,
            'is_bot_paused'      => $isBotPaused,
            'window_open'        => $windowOpen,
            'customer'           => $customer ? ['id' => $customer->id, 'name' => $customer->name] : null,
            'sales_officer_id'   => $customer ? $customer->sales_officer_id : null,
            'sales_officer_name' => ($customer && $customer->salesOfficer) ? $customer->salesOfficer->name : 'Hajakabidhiwa (Unassigned)',
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
        $user = Auth::user();

        // 1. Find or create the customer in the CRM
        $customer = Customer::where(DB::raw("REPLACE(phone, '+', '')"), '=', $cleanPhone)->first();

        // Security check for Sales Officers: prevent replying to threads assigned to other officers
        if ($user->role === 'sales_officer' && $customer) {
            if ($customer->sales_officer_id !== null && $customer->sales_officer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Huna ruhusa ya kumtumia mteja huyu ujumbe. Amekabidhiwa kwa afisa mwingine.'
                ], 403);
            }
        }

        // Auto-Claim and Auto-Register logic
        if (!$customer) {
            // Auto-create customer profile and assign to this sales officer
            $customer = Customer::create([
                'name'             => 'WhatsApp Lead ' . $phone,
                'phone'            => $phone,
                'status'           => 'Potential Customer',
                'source'           => 'WhatsApp',
                'sales_officer_id' => $user->role === 'sales_officer' ? $user->id : null,
            ]);
        } elseif ($customer->sales_officer_id === null && $user->role === 'sales_officer') {
            // Auto-claim the unassigned customer
            $customer->sales_officer_id = $user->id;
            $customer->save();
        }

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

            // Clear explicit active override when staff sends manual message
            Cache::forget("wa_bot_explicit_active_" . $cleanPhone);
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
        $explicitActiveKey = "wa_bot_explicit_active_" . $cleanPhone;
        
        $current = $this->checkBotPausedStatus($cleanPhone);

        if ($current) {
            // Staff explicitly wants the bot ACTIVE
            Cache::forget($key);
            Cache::put($explicitActiveKey, true, now()->addMinutes(30));
            $status = 'active';
        } else {
            // Staff explicitly wants the bot PAUSED (for 24 hours)
            Cache::forget($explicitActiveKey);
            Cache::put($key, true, now()->addHours(24));
            $status = 'paused';
        }

        return response()->json([
            'success' => true,
            'status' => $status
        ]);
    }

    /**
     * Helper to determine if bot is paused, with database fallback for cache loss
     */
    private function checkBotPausedStatus($cleanPhone)
    {
        // 1. Explicit unpause override (expires after 30 min)
        if (Cache::get("wa_bot_explicit_active_" . $cleanPhone, false)) {
            return false;
        }

        // 2. Cache-based pause
        if (Cache::get("wa_bot_paused_" . $cleanPhone, false)) {
            return true;
        }

        // 3. Database fallback (manual replies by staff in last 30 minutes)
        $recentManualReply = SmsLog::where('phone', 'like', "%$cleanPhone%")
            ->whereNotNull('sender_id')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->exists();

        if ($recentManualReply) {
            Cache::put("wa_bot_paused_" . $cleanPhone, true, now()->addMinutes(30));
            return true;
        }

        return false;
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

    /**
     * Claim an unassigned WhatsApp thread manually
     */
    public function claimChat($phone)
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $user = Auth::user();

        // Find or create customer
        $customer = Customer::where(DB::raw("REPLACE(phone, '+', '')"), '=', $cleanPhone)->first();

        if (!$customer) {
            $customer = Customer::create([
                'name'             => 'WhatsApp Lead ' . $phone,
                'phone'            => $phone,
                'status'           => 'Potential Customer',
                'source'           => 'WhatsApp',
                'sales_officer_id' => $user->id,
            ]);
        } else {
            if ($customer->sales_officer_id !== null && $customer->sales_officer_id !== $user->id && $user->role === 'sales_officer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Mazungumzo haya tayari yamechukuliwa na afisa mwingine.'
                ], 400);
            }
            $customer->sales_officer_id = $user->id;
            $customer->save();
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Umekabidhiwa mazungumzo haya rasmi!',
            'officer_name' => $user->name
        ]);
    }

    /**
     * Reassign/Transfer a WhatsApp customer thread (Admin / Manager only)
     */
    public function transferChat(Request $request, $phone)
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $user = Auth::user();

        if ($user->role !== 'manager' && $user->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Ni Mameneja na Admins pekee wenye ruhusa ya kukabidhi/kuhamisha wateja.'
            ], 403);
        }

        $request->validate([
            'sales_officer_id' => 'nullable|exists:users,id'
        ]);

        $officerId   = $request->sales_officer_id;
        $officer     = $officerId ? \App\Models\User::find($officerId) : null;
        $officerName = $officer ? $officer->name : 'Hajakabidhiwa (Unassigned)';

        $customer = Customer::where(DB::raw("REPLACE(phone, '+', '')"), '=', $cleanPhone)->first();

        if (!$customer) {
            $customer = Customer::create([
                'name'             => 'WhatsApp Lead ' . $phone,
                'phone'            => $phone,
                'status'           => 'Potential Customer',
                'source'           => 'WhatsApp',
                'sales_officer_id' => $officerId,
            ]);
        } else {
            $customer->sales_officer_id = $officerId;
            $customer->save();
        }

        return response()->json([
            'success'      => true,
            'message'      => 'Mteja amekabidhiwa kwa ' . $officerName,
            'officer_name' => $officerName
        ]);
    }
}
