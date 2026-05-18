@extends('layouts.vali')

@section('title', 'KPI Activity Ledger')

@section('page_icon', 'fa-list-alt')

@section('subtitle')
Detailed audit trail of all performance-related activities
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
    
    /* Interactive Row Hover Slide Effect */
    .table-hover tbody tr {
        transition: all 0.2s ease-in-out;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(148, 0, 0, 0.03) !important;
        transform: translateX(4px);
    }
    
    /* Card list styling */
    .card {
        transition: all 0.2s ease;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.06) !important;
    }
    
    @media (max-width: 767px) {
        .tile-title {
            font-size: 16px !important;
        }
        /* Mobile filter form styling */
        .p-3.bg-light.border-bottom {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 12px;
        }
        .p-3.bg-light.border-bottom form {
            width: 100% !important;
            display: block !important;
        }
        .p-3.bg-light.border-bottom select {
            width: 100% !important;
            margin-top: 4px;
        }
        
        /* Premium Native Mobile App Card Spacing Canvas */
        .mobile-card-container {
            background-color: #f4f6f9 !important;
            padding: 15px 12px !important;
            border-radius: 12px !important;
            margin-left: -15px !important;
            margin-right: -15px !important;
            margin-bottom: -15px !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
        }
        
        .mobile-card-container .card {
            margin-bottom: 16px !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03) !important;
        }
        
        .mobile-card-container .card:last-child {
            margin-bottom: 0 !important;
        }
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 col-12">
        <div class="tile border-0 shadow-sm p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <h5 class="mb-0 font-weight-bold"><i class="fa fa-history text-primary mr-2"></i> POINT AUDIT TRAIL</h5>
                
                @if(auth()->user()->role !== 'sales_officer')
                <form action="{{ route('kpi.activities') }}" method="GET" class="form-inline mt-2 mt-md-0 w-100 w-md-auto">
                    <label class="mr-2 small font-weight-bold text-muted text-uppercase mb-0">Filter Staff:</label>
                    <select name="user_id" class="form-control form-control-sm w-100 w-md-auto" style="min-width: 200px;" onchange="this.form.submit()">
                        <option value="">All Staff Members</option>
                        @foreach($staff as $s)
                            <option value="{{ $s->id }}" {{ request('user_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
            </div>

            <div class="p-4 bg-white">
                {{-- Desktop View (Traditional Table) --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-sm table-hover table-bordered">
                        <thead class="bg-light text-uppercase" style="font-size: 11px; letter-spacing: 1px;">
                            <tr>
                                <th style="width: 150px;">Date & Time</th>
                                @if(auth()->user()->role !== 'sales_officer')
                                    <th>Officer</th>
                                @endif
                                <th>Activity Type</th>
                                <th>Related Customer</th>
                                <th class="text-center" style="width: 100px;">Points</th>
                                <th>By</th>
                                <th>Description / Memo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $activity)
                            <tr>
                                <td><span class="text-muted small"><i class="fa fa-clock-o mr-1"></i> {{ $activity->created_at->format('d M Y, H:i') }}</span></td>
                                @if(auth()->user()->role !== 'sales_officer')
                                    <td><b>{{ $activity->user->name }}</b></td>
                                @endif
                                <td>
                                    <span class="badge {{ $activity->points >= 0 ? 'badge-success' : 'badge-danger' }} p-1" style="font-size: 10px; min-width: 120px;">
                                        {{ str_replace('_', ' ', $activity->activity_code) }}
                                    </span>
                                </td>
                                <td>
                                    @if($activity->customer)
                                        <a href="{{ route('customers.show', $activity->customer_id) }}" class="font-weight-bold"><i class="fa fa-user mr-1 text-primary"></i> {{ $activity->customer->name }}</a>
                                    @else
                                        <span class="text-muted small">N/A</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="h5 mb-0 font-weight-bold {{ $activity->points >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $activity->points > 0 ? '+' : '' }}{{ $activity->points }}
                                    </span>
                                </td>
                                <td>
                                    <small class="font-weight-bold text-muted">
                                        @if($activity->performer)
                                            {{ $activity->performer->name }} 
                                            <span class="x-small">({{ ucwords(str_replace('_', ' ', $activity->performer->role)) }})</span>
                                        @else
                                            System
                                        @endif
                                    </small>
                                </td>
                                <td><small class="text-dark">{{ $activity->description }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ auth()->user()->role !== 'sales_officer' ? 7 : 6 }}" class="text-center py-5 text-muted">No activity logs found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile View (Premium Card List) --}}
                <div class="d-block d-md-none mobile-card-container">
                    @forelse($activities as $activity)
                    <div class="card shadow-sm mb-3 border-0" style="border-radius: 12px; background: #fff; border-left: 5px solid {{ $activity->points >= 0 ? '#28a745' : '#dc3545' }}; box-shadow: 0 4px 6px rgba(0,0,0,0.04) !important;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge {{ $activity->points >= 0 ? 'badge-success' : 'badge-danger' }} p-1 text-uppercase" style="font-size: 9px; padding: 4px 8px !important; border-radius: 4px;">
                                    {{ str_replace('_', ' ', $activity->activity_code) }}
                                </span>
                                <span class="h6 mb-0 font-weight-bold {{ $activity->points >= 0 ? 'text-success' : 'text-danger' }}" style="font-size: 14px;">
                                    {{ $activity->points > 0 ? '+' : '' }}{{ $activity->points }} Points
                                </span>
                            </div>
                            
                            @if(auth()->user()->role !== 'sales_officer')
                            <div class="mb-2">
                                <small class="text-muted text-uppercase d-block" style="font-size: 8px; letter-spacing: 0.5px;">Officer</small>
                                <span class="font-weight-bold text-dark" style="font-size: 13px;">{{ $activity->user->name }}</span>
                            </div>
                            @endif

                            <div class="row mb-2">
                                <div class="col-6">
                                    <small class="text-muted text-uppercase d-block" style="font-size: 8px; letter-spacing: 0.5px;">Customer</small>
                                    @if($activity->customer)
                                        <a href="{{ route('customers.show', $activity->customer_id) }}" class="font-weight-bold text-primary" style="font-size: 12px;"><i class="fa fa-user mr-1"></i> {{ $activity->customer->name }}</a>
                                    @else
                                        <span class="text-muted" style="font-size: 12px;">N/A</span>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <small class="text-muted text-uppercase d-block" style="font-size: 8px; letter-spacing: 0.5px;">Performed By</small>
                                    <span class="font-weight-bold text-muted" style="font-size: 12px;">
                                        @if($activity->performer)
                                            {{ $activity->performer->name }}
                                        @else
                                            System
                                        @endif
                                    </span>
                                </div>
                            </div>
                            
                            @if($activity->description)
                            <div class="bg-light p-2 rounded mb-2" style="font-size: 11px; border: 1px solid #eee; color: #555;">
                                <i class="fa fa-commenting text-muted mr-1"></i> {{ $activity->description }}
                            </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top" style="font-size: 11px;">
                                <span class="text-muted"><i class="fa fa-clock-o mr-1"></i> {{ $activity->created_at->format('d M Y, H:i') }}</span>
                                @if($activity->performer && $activity->performer->role)
                                    <span class="badge badge-pill badge-light font-weight-bold" style="font-size: 9px; color:#666;">{{ ucwords(str_replace('_', ' ', $activity->performer->role)) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted bg-light rounded" style="border: 2px dashed #ddd;">
                        <i class="fa fa-history fa-2x mb-2 text-muted"></i>
                        <p class="mb-0 font-weight-bold">No activity logs found.</p>
                    </div>
                    @endforelse
                </div>

                {{-- Bootstrap Pagination --}}
                @if($activities->hasPages())
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 gap-2 px-1 text-center">
                    <small class="text-muted mb-2 mb-md-0">
                        Showing {{ $activities->firstItem() }}–{{ $activities->lastItem() }}
                        of {{ $activities->total() }} records
                    </small>
                    <div class="mobile-pagination-wrapper">
                        {{ $activities->links('pagination::bootstrap-4') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
