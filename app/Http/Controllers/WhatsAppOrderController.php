<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppOrder;
use Illuminate\Http\Request;

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

        return view('whatsapp.orders.show', compact('order', 'statuses'));
    }

    public function updateStatus(Request $request, string $orderNumber)
    {
        $order = WhatsAppOrder::where('order_number', $orderNumber)->firstOrFail();

        $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(WhatsAppOrder::STATUSES)),
            'items' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $order->status = $request->status;
        if ($request->filled('items')) {
            $order->items = $request->items;
        }
        if ($request->has('notes')) {
            $order->notes = $request->notes;
        }
        if ($request->status === 'paid' && !$order->payment_submitted_at) {
            $order->payment_submitted_at = now();
        }
        $order->save();

        return back()->with('success', "Order {$order->order_number} updated to {$order->statusLabel()}.");
    }
}
