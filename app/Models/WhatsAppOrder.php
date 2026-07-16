<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppOrder extends Model
{
    protected $table = 'whatsapp_orders';

    public const STATUSES = [
        'pending' => 'Pending Payment',
        'payment_submitted' => 'Payment Submitted',
        'paid' => 'Paid',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'order_number',
        'customer_id',
        'phone',
        'items',
        'delivery_method',
        'address',
        'estimated_total',
        'estimate_breakdown',
        'status',
        'payment_screenshot_path',
        'payment_submitted_at',
        'notes',
    ];

    protected $casts = [
        'estimated_total' => 'decimal:2',
        'estimate_breakdown' => 'array',
        'payment_submitted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public static function generateOrderNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "TRM-{$year}-";
        $last = self::where('order_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('order_number');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function isAwaitingPayment(): bool
    {
        return in_array($this->status, ['pending', 'payment_submitted'], true);
    }
}
