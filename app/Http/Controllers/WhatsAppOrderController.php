<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppOrder;
use Illuminate\Http\Request;

class WhatsAppOrderController extends Controller
{
    public function index(Request $request)
    {
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

        return view('whatsapp.orders.index', compact('orders', 'statuses'));
    }

    public function show(string $orderNumber)
    {
        $order = WhatsAppOrder::with('customer')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $statuses = WhatsAppOrder::STATUSES;

        return view('whatsapp.orders.show', compact('order', 'statuses'));
    }

    public function updateStatus(Request $request, string $orderNumber)
    {
        $order = WhatsAppOrder::where('order_number', $orderNumber)->firstOrFail();

        $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(WhatsAppOrder::STATUSES)),
            'notes' => 'nullable|string|max:2000',
        ]);

        $order->status = $request->status;
        if ($request->filled('notes')) {
            $order->notes = $request->notes;
        }
        if ($request->status === 'paid' && !$order->payment_submitted_at) {
            $order->payment_submitted_at = now();
        }
        $order->save();

        return back()->with('success', "Order {$order->order_number} updated to {$order->statusLabel()}.");
    }
}
