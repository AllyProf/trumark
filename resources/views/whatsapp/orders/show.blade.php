@extends('layouts.vali')

@section('title', 'Order ' . $order->order_number)

@section('page_icon', 'fa-shopping-cart')

@section('subtitle')
WhatsApp bot order details and status management
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h3 class="tile-title mb-1">{{ $order->order_number }}</h3>
                    <span class="badge badge-primary">{{ $order->statusLabel() }}</span>
                </div>
                <a href="{{ route('whatsapp.orders.index') }}" class="btn btn-sm btn-secondary">← Back to Orders</a>
            </div>

            <table class="table table-sm table-borderless">
                <tr><th width="180">Customer</th><td>{{ $order->customer->name ?? 'WhatsApp Lead' }}</td></tr>
                <tr><th>Phone</th><td>
                    <a href="{{ route('whatsapp.chat') }}">{{ $order->phone }}</a>
                    · <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->phone) }}" target="_blank">Open WhatsApp</a>
                </td></tr>
                <tr><th>Items</th><td>{!! nl2br(e($order->items)) !!}</td></tr>
                <tr><th>Delivery Method</th><td>{{ $order->delivery_method ?? '—' }}</td></tr>
                <tr><th>Address / Branch</th><td>{{ $order->address ?? '—' }}</td></tr>
                <tr><th>Estimated Total</th><td>
                    @if($order->estimated_total)
                        <strong>TZS {{ number_format($order->estimated_total) }}</strong>
                    @else
                        Not calculated
                    @endif
                </td></tr>
                <tr><th>Created</th><td>{{ $order->created_at->format('d M Y, H:i') }}</td></tr>
                @if($order->payment_submitted_at)
                    <tr><th>Payment Submitted</th><td>{{ $order->payment_submitted_at->format('d M Y, H:i') }}</td></tr>
                @endif
            </table>

            @if(!empty($order->estimate_breakdown['breakdown']))
                <h5 class="mt-4">Price Estimate Breakdown</h5>
                <table class="table table-sm table-bordered">
                    <thead class="bg-light"><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
                    <tbody>
                        @foreach($order->estimate_breakdown['breakdown'] as $row)
                            <tr>
                                <td>{{ $row['label'] }}</td>
                                <td>{{ $row['quantity'] }}</td>
                                <td>TZS {{ number_format($row['unit_price']) }}</td>
                                <td>TZS {{ number_format($row['line_total']) }}</td>
                            </tr>
                        @endforeach
                        @if(!empty($order->estimate_breakdown['delivery_fee']))
                            <tr>
                                <td colspan="3">Delivery (estimate)</td>
                                <td>TZS {{ number_format($order->estimate_breakdown['delivery_fee']) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            @endif

            @if($order->payment_screenshot_path)
                <h5 class="mt-4">Payment Screenshot</h5>
                <a href="{{ asset('storage/' . $order->payment_screenshot_path) }}" target="_blank">
                    <img src="{{ asset('storage/' . $order->payment_screenshot_path) }}" alt="Payment screenshot" class="img-fluid rounded border" style="max-height: 400px;">
                </a>
            @endif

            @if($order->notes)
                <h5 class="mt-4">Notes</h5>
                <div class="alert alert-light">{!! nl2br(e($order->notes)) !!}</div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="tile">
            <h4 class="tile-title">Update Status</h4>
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
                    <label>Internal Notes</label>
                    <textarea name="notes" class="form-control" rows="4" placeholder="Staff notes...">{{ $order->notes }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-save mr-1"></i> Save Status</button>
            </form>
        </div>
    </div>
</div>
@endsection
