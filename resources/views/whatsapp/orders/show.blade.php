@extends('layouts.vali')

@section('title', 'Order ' . $order->order_number)

@section('page_icon', 'fa-shopping-cart')

@section('subtitle')
WhatsApp bot order details, reply, and status management
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-lg-7">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap">
                <div>
                    <h3 class="tile-title mb-1">{{ $order->order_number }}</h3>
                    <span class="badge badge-primary">{{ $order->statusLabel() }}</span>
                </div>
                <div class="mb-2">
                    <a href="{{ route('whatsapp.chat', ['phone' => $order->phone]) }}" class="btn btn-sm btn-success">
                        <i class="fa fa-whatsapp"></i> Full Chat
                    </a>
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->phone) }}" target="_blank" class="btn btn-sm btn-outline-success">
                        Open WhatsApp
                    </a>
                    <a href="{{ route('whatsapp.orders.index') }}" class="btn btn-sm btn-secondary">← Back</a>
                </div>
            </div>

            <table class="table table-sm table-borderless">
                <tr><th width="180">Customer</th><td>{{ $order->customer->name ?? 'WhatsApp Lead' }}</td></tr>
                <tr><th>Phone</th><td><strong>{{ $order->phone }}</strong></td></tr>
                <tr><th>Items</th><td>{!! nl2br(e($order->items)) !!}</td></tr>
                <tr><th>Delivery Method</th><td>{{ $order->delivery_method ?? '—' }}</td></tr>
                <tr><th>Address / Branch</th><td>{{ $order->address ?? '—' }}</td></tr>
                <tr><th>Estimated Total</th><td>
                    @if($order->estimated_total)
                        <strong>TZS {{ number_format($order->estimated_total) }}</strong>
                    @else
                        —
                    @endif
                </td></tr>
                <tr><th>Created</th><td>{{ $order->created_at->format('d M Y, H:i') }}</td></tr>
                @if($order->payment_submitted_at)
                    <tr><th>Payment Submitted</th><td>{{ $order->payment_submitted_at->format('d M Y, H:i') }}</td></tr>
                @endif
            </table>

            @if($order->payment_screenshot_path)
                <h5 class="mt-3">Payment Screenshot</h5>
                <a href="{{ asset('storage/' . $order->payment_screenshot_path) }}" target="_blank">
                    <img src="{{ asset('storage/' . $order->payment_screenshot_path) }}" alt="Payment screenshot" class="img-fluid rounded border" style="max-height: 320px;">
                </a>
            @endif
        </div>

        <div class="tile">
            <h4 class="tile-title">Recent WhatsApp Messages</h4>
            <div style="max-height: 320px; overflow-y: auto; background: #f7f7f7; border-radius: 6px; padding: 12px;">
                @forelse($recentMessages as $msg)
                    @php
                        $isIn = in_array($msg->status, ['received', 'read_by_agent'], true) || str_starts_with($msg->message, 'INCOMING:');
                        $text = preg_replace('/^(INCOMING:|\[BOT REPLY\])\s*/', '', $msg->message);
                    @endphp
                    <div class="mb-2 p-2 rounded {{ $isIn ? 'bg-white border' : 'bg-primary text-white' }}" style="{{ $isIn ? '' : 'margin-left:20%;' }} {{ $isIn ? 'margin-right:20%;' : '' }}">
                        @if($msg->media_path)
                            <a href="{{ asset('storage/' . $msg->media_path) }}" target="_blank">
                                <img src="{{ asset('storage/' . $msg->media_path) }}" style="max-width:160px;border-radius:6px;" alt="media">
                            </a>
                        @endif
                        <div style="font-size:13px;">{!! nl2br(e($text)) !!}</div>
                        <small class="{{ $isIn ? 'text-muted' : 'text-white-50' }}">{{ $msg->created_at->format('d M H:i') }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0">No recent messages.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('whatsapp.orders.reply', $order->order_number) }}" class="mt-3">
                @csrf
                <div class="form-group mb-2">
                    <label class="font-weight-bold">Reply to customer on WhatsApp</label>
                    <textarea name="message" class="form-control" rows="3" required placeholder="Type reply... e.g. Bei ni TZS 25,500. Lipa kisha tuma screenshot."></textarea>
                </div>
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fa fa-paper-plane mr-1"></i> Send Reply (pauses bot 30 min)
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="tile">
            <h4 class="tile-title">Update Order</h4>
            <form method="POST" action="{{ route('whatsapp.orders.update_status', $order->order_number) }}">
                @csrf
                @method('PATCH')
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control" required>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ $order->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Items</label>
                    <textarea name="items" class="form-control" rows="4">{{ $order->items }}</textarea>
                </div>
                <div class="form-group">
                    <label>Estimated Total (TZS)</label>
                    <input type="number" step="0.01" min="0" name="estimated_total" class="form-control" value="{{ $order->estimated_total }}">
                </div>
                <div class="form-group">
                    <label>Internal Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ $order->notes }}</textarea>
                </div>
                <div class="form-group">
                    <div class="animated-checkbox">
                        <label>
                            <input type="checkbox" name="notify_customer" value="1">
                            <span class="label-text">Notify customer of status change via WhatsApp</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-save mr-1"></i> Save Order</button>
            </form>
        </div>
    </div>
</div>
@endsection
