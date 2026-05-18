@extends('layouts.vali')

@section('title', 'KPI Activity Ledger')

@section('page_icon', 'fa-list-alt')

@section('subtitle')
Detailed audit trail of all performance-related activities
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile border-0 shadow-sm p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold"><i class="fa fa-history text-primary mr-2"></i> POINT AUDIT TRAIL</h5>
                
                @if(auth()->user()->role !== 'sales_officer')
                <form action="{{ route('kpi.activities') }}" method="GET" class="form-inline">
                    <label class="mr-2 small font-weight-bold text-muted text-uppercase">Filter Staff:</label>
                    <select name="user_id" class="form-control form-control-sm" style="width: 200px;" onchange="this.form.submit()">
                        <option value="">All Staff Members</option>
                        @foreach($staff as $s)
                            <option value="{{ $s->id }}" {{ request('user_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
            </div>

            <div class="p-4 bg-white">
                <div class="table-responsive">
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
                                <td colspan="6" class="text-center py-5 text-muted">No activity logs found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Bootstrap Pagination --}}
                @if($activities->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3 px-1">
                    <small class="text-muted">
                        Showing {{ $activities->firstItem() }}–{{ $activities->lastItem() }}
                        of {{ $activities->total() }} records
                    </small>
                    {{ $activities->links('pagination::bootstrap-4') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
