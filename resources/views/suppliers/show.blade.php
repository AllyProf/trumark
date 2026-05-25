@extends('layouts.vali')

@section('title', 'Supplier Details')

@section('page_icon', 'fa-truck')

@section('subtitle')
Full supplier profile and contact information
@endsection

@section('styles')
<style>
    .tile {
        border-radius: 12px !important;
        box-shadow: 0 4px 6px rgba(0,0,0,0.04) !important;
        border: 0 !important;
        margin-bottom: 25px;
    }
    .table td, .table th {
        vertical-align: middle !important;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 col-12">

        {{-- Supplier Profile Card --}}
        <div class="tile border-0 shadow-sm p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h3 class="tile-title mb-0 font-weight-bold" style="font-size: 18px;">
                    <i class="fa fa-truck text-primary mr-2"></i> SUPPLIER PROFILE
                </h3>
                <div>
                    <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-primary btn-sm mr-1">
                        <i class="fa fa-edit mr-1"></i> Edit
                    </a>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-arrow-left mr-1"></i> Back
                    </a>
                </div>
            </div>

            <div class="p-4 bg-white">
                {{-- Supplier Avatar + Name Banner --}}
                <div class="d-flex align-items-center mb-4 p-3 bg-light rounded">
                    <div class="mr-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                         style="width: 60px; height: 60px; font-size: 24px; font-weight: bold; flex-shrink: 0;">
                        {{ strtoupper(substr($supplier->name, 0, 1)) }}
                    </div>
                    <div>
                        <h4 class="mb-1 font-weight-bold">{{ $supplier->name }}</h4>
                        @if($supplier->service_offered)
                            <span class="badge badge-warning mr-1">{{ $supplier->service_offered }}</span>
                        @endif
                        @if($supplier->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </div>
                </div>

                {{-- Details Table --}}
                <table class="table table-bordered table-sm">
                    <tbody>
                        <tr>
                            <th class="bg-light" style="width: 35%;">
                                <i class="fa fa-phone mr-1 text-muted"></i> Phone Number
                            </th>
                            <td>
                                @if($supplier->phone)
                                    <a href="tel:{{ $supplier->phone }}">{{ $supplier->phone }}</a>
                                @else
                                    <span class="text-muted">Not provided</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">
                                <i class="fa fa-envelope mr-1 text-muted"></i> Email Address
                            </th>
                            <td>
                                @if($supplier->email)
                                    <a href="mailto:{{ $supplier->email }}">{{ $supplier->email }}</a>
                                @else
                                    <span class="text-muted">Not provided</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">
                                <i class="fa fa-map-marker mr-1 text-muted"></i> Location
                            </th>
                            <td>{{ $supplier->location ?: 'Not provided' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">
                                <i class="fa fa-briefcase mr-1 text-muted"></i> Service Offered
                            </th>
                            <td>{{ $supplier->service_offered ?: 'Not specified' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">
                                <i class="fa fa-sticky-note mr-1 text-muted"></i> Note
                            </th>
                            <td>
                                @if($supplier->note)
                                    <p class="mb-0" style="white-space: pre-wrap;">{{ $supplier->note }}</p>
                                @else
                                    <span class="text-muted">No notes added</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">
                                <i class="fa fa-user mr-1 text-muted"></i> Registered By
                            </th>
                            <td>{{ $supplier->creator->name ?? 'System' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">
                                <i class="fa fa-calendar mr-1 text-muted"></i> Date Registered
                            </th>
                            <td>{{ $supplier->created_at->format('d M Y, h:i A') }} <small class="text-muted">({{ $supplier->created_at->diffForHumans() }})</small></td>
                        </tr>
                        <tr>
                            <th class="bg-light">
                                <i class="fa fa-refresh mr-1 text-muted"></i> Last Updated
                            </th>
                            <td>{{ $supplier->updated_at->format('d M Y, h:i A') }} <small class="text-muted">({{ $supplier->updated_at->diffForHumans() }})</small></td>
                        </tr>
                    </tbody>
                </table>

                {{-- Action Buttons --}}
                <div class="mt-3 d-flex justify-content-between">
                    <div>
                        <form action="{{ route('suppliers.toggle_status', $supplier->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn {{ $supplier->status === 'active' ? 'btn-warning' : 'btn-success' }} btn-sm">
                                <i class="fa {{ $supplier->status === 'active' ? 'fa-ban' : 'fa-play' }} mr-1"></i>
                                {{ $supplier->status === 'active' ? 'Deactivate Supplier' : 'Activate Supplier' }}
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger btn-sm ml-1" id="deleteBtn">
                            <i class="fa fa-trash mr-1"></i> Delete Supplier
                        </button>
                        <form id="deleteSupplierForm" action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" style="display:none;">
                            @csrf
                            @method('DELETE')
                        </form>
                    </div>
                    <small class="text-muted align-self-center">Supplier ID: #{{ str_pad($supplier->id, 4, '0', STR_PAD_LEFT) }}</small>
                </div>
            </div>
        </div>

    </div>

    {{-- Quick Info Sidebar --}}
    <div class="col-md-4 col-12">
        <div class="tile border-0 shadow-sm p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom">
                <h3 class="tile-title mb-0 font-weight-bold" style="font-size: 15px;">
                    <i class="fa fa-info-circle text-primary mr-2"></i> QUICK SUMMARY
                </h3>
            </div>
            <div class="p-3 bg-white">
                <ul class="list-unstyled mb-0">
                    <li class="py-2 border-bottom d-flex justify-content-between">
                        <span class="text-muted"><i class="fa fa-truck mr-1"></i> Status</span>
                        @if($supplier->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-secondary">Inactive</span>
                        @endif
                    </li>
                    <li class="py-2 border-bottom d-flex justify-content-between">
                        <span class="text-muted"><i class="fa fa-phone mr-1"></i> Phone</span>
                        <strong>{{ $supplier->phone ?: '—' }}</strong>
                    </li>
                    <li class="py-2 border-bottom d-flex justify-content-between">
                        <span class="text-muted"><i class="fa fa-map-marker mr-1"></i> Location</span>
                        <strong>{{ $supplier->location ?: '—' }}</strong>
                    </li>
                    <li class="py-2 d-flex justify-content-between">
                        <span class="text-muted"><i class="fa fa-briefcase mr-1"></i> Service</span>
                        <strong>{{ $supplier->service_offered ?: '—' }}</strong>
                    </li>
                </ul>

                <hr>
                <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-primary btn-block btn-sm">
                    <i class="fa fa-edit mr-1"></i> Edit This Supplier
                </a>
                <a href="{{ route('suppliers.create') }}" class="btn btn-outline-primary btn-block btn-sm mt-2">
                    <i class="fa fa-plus mr-1"></i> Register Another
                </a>
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary btn-block btn-sm mt-2">
                    <i class="fa fa-list mr-1"></i> View All Suppliers
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script>
    document.getElementById('deleteBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Delete Supplier?',
            html: '<strong>{{ addslashes($supplier->name) }}</strong> will be permanently removed from the system.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#940000',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete'
        }).then(result => {
            if (result.isConfirmed) {
                document.getElementById('deleteSupplierForm').submit();
            }
        });
    });
</script>
@endsection
