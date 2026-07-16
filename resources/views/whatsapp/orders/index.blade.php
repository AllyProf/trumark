@extends('layouts.vali')

@section('title', 'WhatsApp Orders')

@section('page_icon', 'fa-shopping-cart')

@section('subtitle')
Bot orders, payment screenshots, and delivery status
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<style>
    .tile { border-top: 3px solid #25D366; border-radius: 4px; }
    .status-badge { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h3 class="tile-title mb-2 mb-md-0">WhatsApp Bot Orders</h3>
                <form method="GET" class="form-inline">
                    <input type="text" name="search" class="form-control form-control-sm mr-2 mb-2" placeholder="Order, phone, items..." value="{{ request('search') }}">
                    <select name="status" class="form-control form-control-sm mr-2 mb-2">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm mb-2"><i class="fa fa-filter mr-1"></i> Filter</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Items</th>
                            <th>Delivery</th>
                            <th>Estimate</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td><strong>{{ $order->order_number }}</strong></td>
                                <td>{{ $order->customer->name ?? 'WhatsApp Lead' }}</td>
                                <td>{{ $order->phone }}</td>
                                <td>{{ Str::limit($order->items, 60) }}</td>
                                <td>{{ Str::limit($order->delivery_method, 30) }}</td>
                                <td>
                                    @if($order->estimated_total)
                                        TZS {{ number_format($order->estimated_total) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $badge = match($order->status) {
                                            'pending' => 'warning',
                                            'payment_submitted' => 'info',
                                            'paid', 'confirmed' => 'primary',
                                            'preparing', 'shipped' => 'secondary',
                                            'delivered' => 'success',
                                            'cancelled' => 'danger',
                                            default => 'light',
                                        };
                                    @endphp
                                    <span class="badge badge-{{ $badge }} status-badge">{{ $order->statusLabel() }}</span>
                                </td>
                                <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('whatsapp.orders.show', $order->order_number) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No WhatsApp orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
