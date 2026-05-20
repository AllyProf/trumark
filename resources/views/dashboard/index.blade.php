@extends('layouts.vali')

@section('title', 'Dashboard Overview')

@section('page_icon', 'fa-dashboard')

@section('subtitle')
Real-time analytics and performance metrics for your business
@endsection

@section('styles')
<style>
    .tile {
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .widget-small .info h4 {
        text-transform: none;
        font-weight: 700;
    }
    /* Native branch select styling */
    .branch-native-select {
        border: 1.5px solid #ddd;
        border-radius: 6px;
        padding: 6px 32px 6px 10px;
        font-size: 13px;
        font-weight: 600;
        color: #333;
        background-color: #fff;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23940000' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        cursor: pointer;
        max-width: 100%;
        width: auto;
        transition: border-color 0.2s;
    }
    .branch-native-select:focus {
        outline: none;
        border-color: #940000;
        box-shadow: 0 0 0 3px rgba(148,0,0,0.1);
    }
    @media (max-width: 767px) {
        .branch-filter-bar {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .branch-filter-bar .d-flex.align-items-center {
            flex-direction: column !important;
            align-items: flex-start !important;
            width: 100% !important;
            gap: 8px !important;
        }
        .branch-filter-bar form { width: 100% !important; }
        .branch-native-select { width: 100% !important; }
    }
</style>
@endsection

@section('content')
<!-- Branch Filter Bar -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="tile p-3">
            <div class="d-flex align-items-center justify-content-between branch-filter-bar">
                <div class="d-flex align-items-center">
                    <i class="fa fa-filter text-primary mr-2"></i>
                    <span class="font-weight-bold text-dark mr-3">Filter by Location:</span>
                    @if(in_array(auth()->user()->role, ['super_admin', 'manager']))
                    <form action="{{ route('dashboard') }}" method="GET">
                        <select name="branch_id" class="branch-native-select" onchange="this.form.submit()">
                            <option value="">🌍 All Branches (Global)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                    📍 {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    @endif
                </div>
                <div>
                    @if(isset($currentBranch))
                        <span class="badge badge-danger px-3 py-2" style="font-size: 12px;">Active View: <strong>{{ $currentBranch->name }}</strong></span>
                    @else
                        <span class="badge badge-primary px-3 py-2" style="font-size: 12px;">Active View: <strong>Global (All Branches)</strong></span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon"><i class="icon fa fa-users fa-3x"></i>
            <div class="info">
                <h4>Total Leads</h4>
                <p><b>{{ $total_customers }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon"><i class="icon fa fa-phone fa-3x"></i>
            <div class="info">
                <h4>Pending Follow-ups</h4>
                <p><b>{{ $pending_follow_ups }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon"><i class="icon fa fa-line-chart fa-3x"></i>
            <div class="info">
                <h4>Pipeline Value</h4>
                <p><b>{{ number_format($pipeline_value) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small danger coloured-icon"><i class="icon fa fa-trophy fa-3x"></i>
            <div class="info">
                <h4>Closed Won</h4>
                <p><b>{{ number_format($won_value) }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title">Sales Funnel Performance</h3>
            <div id="salesFunnelChart" style="min-height: 350px;"></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title">Leads Registration Trend</h3>
            <div id="leadsTrendChart" style="min-height: 350px;"></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title">Lead Sources</h3>
            <div id="sourcesChart" style="min-height: 350px;"></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="tile">
            <h3 class="tile-title">Recent Actions Done</h3>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User / Customer</th>
                            <th>Action</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent_logs as $log)
                        <tr>
                            <td>
                                @if($log->customer)
                                    <b class="text-dark">{{ $log->customer->name }}</b><br>
                                    <small class="text-muted"><i class="fa fa-user mr-1"></i> {{ $log->user->name ?? 'System' }}</small>
                                @else
                                    <b class="text-dark">{{ $log->user->name ?? 'System' }}</b><br>
                                    <small class="text-muted">General Action</small>
                                @endif
                            </td>
                            <td>
                                <span class="d-block font-weight-bold" style="font-size: 13px;">{{ $log->description }}</span>
                                <div class="mt-1">
                                    <span class="badge {{ $log->points >= 0 ? 'badge-success' : 'badge-danger' }}">{{ $log->points > 0 ? '+' : '' }}{{ $log->points }} pts</span>
                                    <span class="small text-muted ml-2"><i class="fa fa-pencil text-primary mr-1"></i> By: <b class="text-dark">{{ $log->performer->name ?? 'System' }}</b></span>
                                </div>
                            </td>
                            <td class="text-muted small align-middle">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">No recent activity.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3 bg-white border-top d-flex justify-content-center">
                {{ $recent_logs->appends(request()->except('comms_page'))->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
$(document).ready(function() {
    var funnelOptions = {
        series: [{ name: 'Leads', data: @json(array_values($funnel)) }],
        chart: { 
            type: 'bar', 
            height: 350, 
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4 } },
        colors: ['#940000', '#17a2b8', '#ffc107', '#28a745', '#dc3545'],
        xaxis: { categories: @json(array_keys($funnel)) }
    };
    new ApexCharts(document.querySelector("#salesFunnelChart"), funnelOptions).render();

    var trendOptions = {
        series: [{ name: 'New Leads', data: @json($leads_by_month) }],
        chart: { 
            type: 'area', 
            height: 350, 
            toolbar: { show: false },
            zoom: { enabled: false },
            selection: { enabled: false }
        },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#940000'],
        xaxis: { categories: @json($months) }
    };
    new ApexCharts(document.querySelector("#leadsTrendChart"), trendOptions).render();

    var sourceOptions = {
        series: @json(array_values($sources)),
        chart: { 
            type: 'donut', 
            height: 350,
            zoom: { enabled: false }
        },
        labels: @json(array_keys($sources)),
        colors: ['#940000', '#17a2b8', '#28a745', '#ffc107', '#dc3545'],
        legend: { position: 'bottom' }
    };
    new ApexCharts(document.querySelector("#sourcesChart"), sourceOptions).render();
});
</script>
@endsection
