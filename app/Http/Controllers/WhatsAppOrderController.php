<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use App\Models\WhatsAppOrder;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class WhatsAppOrderController extends Controller
{
    public function index(Request $request)
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('whatsapp_orders')) {
            return view('whatsapp.orders.index', [
                'orders' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 25),
                'statuses' => WhatsAppOrder::STATUSES,
                'tableMissing' => true,
            ]);
        }

        $query = WhatsAppOrder::with('customer')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('items', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(25)->withQueryString();
        $statuses = WhatsAppOrder::STATUSES;

        return view('whatsapp.orders.index', [
            'orders' => $orders,
            'statuses' => $statuses,
            'tableMissing' => false,
        ]);
    }

    public function show(string $orderNumber)
    {
        $order = WhatsAppOrder::with('customer')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $statuses = WhatsAppOrder::STATUSES;
        $cleanPhone = preg_replace('/[^0-9]/', '', $order->phone);

        $recentMessages = SmsLog::where('phone', 'like', "%{$cleanPhone}%")
            ->where(function ($q) {
                $q->whereIn('status', ['received', 'read_by_agent'])
                    ->orWhereNotNull('sender_id')
                    ->orWhere('message', 'like', '[BOT REPLY]%');
            })
            ->orderByDesc('created_at')
            ->limit(12)
            ->get()
            ->reverse()
            ->values();

        return view('whatsapp.orders.show', compact('order', 'statuses', 'recentMessages'));
    }

    public function updateStatus(Request $request, string $orderNumber)
    {
        $order = WhatsAppOrder::where('order_number', $orderNumber)->firstOrFail();

        $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(WhatsAppOrder::STATUSES)),
            'items' => 'nullable|string|max:5000',
            'estimated_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'notify_customer' => 'nullable|boolean',
        ]);

        $oldStatus = $order->status;
        $order->status = $request->status;
        if ($request->filled('items')) {
            $order->items = $request->items;
        }
        if ($request->filled('estimated_total')) {
            $order->estimated_total = $request->estimated_total;
        }
        if ($request->has('notes')) {
            $order->notes = $request->notes;
        }
        if ($request->status === 'paid' && !$order->payment_submitted_at) {
            $order->payment_submitted_at = now();
        }
        $order->save();

        if ($request->boolean('notify_customer') && $oldStatus !== $order->status) {
            $this->notifyCustomerStatus($order);
        }

        return back()->with('success', "Order {$order->order_number} updated to {$order->statusLabel()}.");
    }

    public function reply(Request $request, string $orderNumber, WhatsAppService $whatsapp)
    {
        $order = WhatsAppOrder::where('order_number', $orderNumber)->firstOrFail();

        $request->validate([
            'message' => 'required|string|max:4000',
        ]);

        $result = $whatsapp->sendMessage($order->phone, $request->message);

        if (!($result['success'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'Failed to send WhatsApp message.');
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $order->phone);
        Cache::put('wa_bot_paused_' . $cleanPhone, true, now()->addMinutes(30));

        SmsLog::create([
            'customer_id' => $order->customer_id,
            'sender_id' => Auth::id(),
            'phone' => $order->phone,
            'message' => $request->message,
            'status' => 'sent',
            'whatsapp_message_id' => $result['response']['messages'][0]['id'] ?? null,
            'response' => json_encode(['from_order_page' => $order->order_number]),
        ]);

        return back()->with('success', 'Reply sent. Bot paused for 30 minutes for this customer.');
    }

    protected function notifyCustomerStatus(WhatsAppOrder $order): void
    {
        $msg = "📦 *Order Update — {$order->order_number}*\n\n"
            . "Status: *{$order->statusLabel()}*\n"
            . "Items: {$order->items}\n";

        if ($order->estimated_total) {
            $msg .= 'Total: TZS ' . number_format((float) $order->estimated_total) . "\n";
        }

        $msg .= "\nAsante — TRUMARK";

        try {
            $whatsapp = app(WhatsAppService::class);
            $whatsapp->sendMessage($order->phone, $msg);
            SmsLog::create([
                'customer_id' => $order->customer_id,
                'sender_id' => Auth::id(),
                'phone' => $order->phone,
                'message' => '[BOT REPLY] ' . $msg,
                'status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            // ignore notify failures
        }
    }
}
