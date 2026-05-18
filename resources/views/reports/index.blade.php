@extends('layouts.vali')

@section('title', 'CRM Reports & Analytics')

@section('page_icon', 'fa-pie-chart')

@section('subtitle')
Detailed breakdown of sales performance, lead conversion and pipeline trends
@endsection

@section('styles')
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<style>
    .widget-small { 
        min-height: 90px; 
        display: flex; 
        align-items: center; 
        margin-bottom: 20px; 
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .widget-small .icon {
        width: 60px;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .widget-small .info {
        padding: 10px 15px;
        flex-grow: 1;
    }
    .widget-small .info h4 {
        text-transform: none;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 2px;
        color: #666;
    }
    .widget-small .info p {
        margin-bottom: 0;
        font-size: 18px;
    }
    .conv-bar-wrap { background: #e9ecef; border-radius: 20px; height: 12px; overflow: hidden; margin-top: 5px; }
    .conv-bar { background: #940000; height: 100%; border-radius: 20px; transition: width 1.5s ease; }
    
    @media print {
        .app-header, .app-sidebar, .d-print-none, .btn, form, .dataTables_filter, .dataTables_length, .dataTables_paginate {
            display: none !important;
        }
        .app-content {
            margin-left: 0 !important;
            margin-top: 0 !important;
            padding: 0 !important;
        }
        .tile {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin-bottom: 0 !important;
        }
        body {
            background-color: #fff !important;
        }
    }
</style>
@endsection

@section('content')

@php
    $stageColors = [
        'Inquiry'         => '#adb5bd',
        'Quotation Sent'  => '#17a2b8',
        'Negotiation'     => '#007bff',
        'Order Confirmed' => '#20c997',
        'Delivered'       => '#6f42c1',
        'Payment Pending' => '#ffc107',
        'Closed Won'      => '#28a745',
        'Closed Lost'     => '#dc3545',
    ];

    if (!function_exists('formatKpiValue')) {
        function formatKpiValue($value) {
            if ($value >= 1000000) {
                return number_format($value / 1000000, 1) . 'M';
            } elseif ($value >= 1000) {
                return number_format($value / 1000, 1) . 'K';
            }
            return number_format($value);
        }
    }
@endphp

<div class="row mb-4 d-print-none">
    <div class="col-md-12">
        <div class="tile p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fa fa-filter text-primary mr-2"></i>
                    <span class="font-weight-bold mr-3">Report Scope:</span>
                    @if(auth()->user()->role === 'super_admin')
                    <form action="{{ route('reports.index') }}" method="GET" class="d-inline-block">
                        <select name="branch_id" class="form-control select2-branch" onchange="this.form.submit()" style="width: 250px;">
                            <option value="">🌍 All Branches (Global)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>
                                    📍 {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    @endif
                </div>
                <button onclick="window.print();" class="btn btn-primary btn-sm">
                    <i class="fa fa-print mr-1"></i> Print / Export PDF
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon"><i class="icon fa fa-users fa-2x"></i>
            <div class="info">
                <h4>Total Leads</h4>
                <p><b>{{ $totalLeads }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon"><i class="icon fa fa-line-chart fa-2x"></i>
            <div class="info">
                <h4>Pipeline Value</h4>
                <p><b>{{ formatKpiValue($totalPipelineValue) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small success coloured-icon"><i class="icon fa fa-trophy fa-2x"></i>
            <div class="info">
                <h4>Revenue Won</h4>
                <p><b>{{ formatKpiValue($totalWonValue) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon"><i class="icon fa fa-clock-o fa-2x"></i>
            <div class="info">
                <h4>Pending</h4>
                <p><b>{{ $pendingPayment }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="tile">
            <h3 class="tile-title">Sales Funnel Distribution</h3>
            <canvas id="stageChart" height="300"></canvas>
        </div>
    </div>
    <div class="col-md-8">
        <div class="tile">
            <h3 class="tile-title">Monthly Lead Registrations</h3>
            <canvas id="monthlyChart" height="150"></canvas>
        </div>
    </div>
</div>



<div class="row">
    <div class="col-md-8">
        <div class="tile">
            <h3 class="tile-title">Pipeline Value by Stage (TZS)</h3>
            <canvas id="pipelineChart" height="150"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="tile">
            <h3 class="tile-title">Market Segment Breakdown</h3>
            <canvas id="typeChart" height="200"></canvas>
            <div class="mt-4">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 rounded mb-1">
                        <span><i class="fa fa-university mr-2 text-primary"></i> Primary Schools</span>
                        <span class="badge badge-primary badge-pill">{{ $stats['primary_schools'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 rounded mb-1">
                        <span><i class="fa fa-graduation-cap mr-2 text-info"></i> Secondary Schools</span>
                        <span class="badge badge-info badge-pill">{{ $stats['secondary_schools'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 rounded mb-1">
                        <span><i class="fa fa-user-circle mr-2 text-warning"></i> Parents</span>
                        <span class="badge badge-warning badge-pill">{{ $stats['parents'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 rounded mb-1">
                        <span><i class="fa fa-street-view mr-2 text-danger"></i> Walk-ins</span>
                        <span class="badge badge-danger badge-pill">{{ $stats['walk_ins'] }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->role !== 'sales_officer')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Sales Officer Performance Ranking</h3>
            <div class="table-responsive">
                <table class="table table-hover" id="performanceTable">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Officer & Level</th>
                            <th>Total Leads</th>
                            <th>KPI Score</th>
                            <th>Market Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topOfficers as $i => $officer)
                        <tr>
                            <td><b>#{{ $i + 1 }}</b></td>
                            <td>
                                <b>{{ $officer->salesOfficer->name ?? 'Unassigned' }}</b><br>
                                <span class="badge" style="background: {{ $officer->kpi_level['color'] }}; color: white; font-size: 10px;">
                                    <i class="fa {{ $officer->kpi_level['icon'] }} mr-1"></i> {{ $officer->kpi_level['name'] }}
                                </span>
                            </td>
                            <td>{{ $officer->lead_count }}</td>
                            <td>
                                <span class="text-primary font-weight-bold">{{ $officer->kpi_points }}</span> <small class="text-muted">pts</small>
                            </td>
                            <td>
                                @php $pct = $totalLeads > 0 ? ($officer->lead_count / $totalLeads) * 100 : 0; @endphp
                                <div class="conv-bar-wrap">
                                    <div class="conv-bar" style="width: {{ $pct }}%"></div>
                                </div>
                                <small>{{ round($pct, 1) }}% Performance Share</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Attendance & Usage ─────────────────────────────────────────── --}}
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title"><i class="fa fa-clock-o mr-2 text-primary"></i> Staff Attendance & System Usage</h3>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>Staff Member</th>
                            <th>Entry Time (Login)</th>
                            <th>Exit Time (Logout)</th>
                            <th>Active Duration</th>
                            <th>IP Address</th>
                            <th>Location</th>
                            @if(in_array(auth()->user()->role, ['super_admin', 'manager']))
                            <th class="d-print-none">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($usageLogs as $log)
                        <tr>
                            <td><b>{{ $log->user->name ?? 'Unknown' }}</b></td>
                            <td><span class="text-success font-weight-bold">{{ $log->login_at->format('d M, Y - H:i') }}</span></td>
                            <td>
                                @if($log->logout_at)
                                    <span class="text-muted">{{ $log->logout_at->format('H:i') }}</span>
                                @else
                                    <span class="badge badge-success">Currently Active</span>
                                @endif
                            </td>
                            <td>
                                @if($log->logout_at)
                                    <span class="badge badge-light border">{{ $log->duration_minutes }} mins</span>
                                @else
                                    <span class="text-muted"><i class="fa fa-spinner fa-spin mr-1"></i> Calculating...</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted d-block font-weight-bold">{{ $log->ip_address }}</small>
                                @if($log->isp && $log->isp !== 'Unknown')
                                    <span class="badge badge-light border text-secondary mt-1" style="font-size: 10px; font-weight: 600; letter-spacing: 0.3px;">
                                        <i class="fa fa-wifi text-primary mr-1" style="font-size: 9px;"></i> {{ $log->isp }}
                                    </span>
                                @else
                                    <span class="badge badge-light border text-muted mt-1" style="font-size: 10px; font-weight: 500;">
                                        <i class="fa fa-wifi mr-1" style="font-size: 9px;"></i> Local / Unknown
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($log->location && $log->location !== 'Unknown')
                                    <span class="badge badge-pill badge-info"><i class="fa fa-map-marker mr-1"></i> {{ $log->location }}</span>
                                @else
                                    <span class="text-muted"><i class="fa fa-map-marker mr-1"></i> Local / Unknown</span>
                                @endif
                            </td>
                            @if(in_array(auth()->user()->role, ['super_admin', 'manager']))
                            <td class="d-print-none">
                                @if(!$log->logout_at)
                                    <form id="force-logout-form-{{ $log->id }}" action="{{ route('reports.force_logout', $log->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="button" class="btn btn-danger btn-sm px-2 py-1 end-session-btn" data-id="{{ $log->id }}" data-name="{{ $log->user->name ?? 'Unknown' }}" style="font-size: 11px; border-radius: 20px;">
                                            <i class="fa fa-power-off mr-1"></i> End Session
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted"><i class="fa fa-check-circle text-success"></i> Closed</span>
                                @endif
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ in_array(auth()->user()->role, ['super_admin', 'manager']) ? 7 : 6 }}" class="text-center py-4 text-muted">No usage logs found for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{-- Bootstrap Pagination --}}
            @if($usageLogs->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-3 px-1">
                <small class="text-muted">
                    Showing {{ $usageLogs->firstItem() }}–{{ $usageLogs->lastItem() }}
                    of {{ $usageLogs->total() }} records
                </small>
                {{ $usageLogs->links('pagination::bootstrap-4') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $('.select2-branch').select2({
        minimumResultsForSearch: Infinity,
        dropdownAutoWidth: true
    });

    $('#performanceTable').DataTable({
        "retrieve": true,
        "paging": true,
        "searching": true,
        "info": false,
        "order": [[3, "desc"]]
    });

    // Charts
    new Chart(document.getElementById('stageChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($stageData)) !!},
            datasets: [{
                data: {!! json_encode(array_values($stageData)) !!},
                backgroundColor: ['#940000', '#17a2b8', '#ffc107', '#28a745', '#dc3545', '#6f42c1', '#007bff', '#adb5bd']
            }]
        }
    });

    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_keys($months)) !!},
            datasets: [{
                label: 'New Leads',
                data: {!! json_encode(array_values($months)) !!},
                backgroundColor: '#940000'
            }]
        }
    });

    new Chart(document.getElementById('pipelineChart'), {
        type: 'bar',
        data: {
            labels: {!! json_encode(array_keys($pipelineByStage)) !!},
            datasets: [{
                label: 'Value',
                data: {!! json_encode(array_values($pipelineByStage)) !!},
                backgroundColor: '#17a2b8'
            }]
        },
        options: { indexAxis: 'y' }
    });

    new Chart(document.getElementById('typeChart'), {
        type: 'pie',
        data: {
            labels: {!! json_encode(array_keys($typeData)) !!},
            datasets: [{
                data: {!! json_encode(array_values($typeData)) !!},
                backgroundColor: ['#940000', '#17a2b8', '#ffc107', '#28a745']
            }]
        }
    });

    // SweetAlert Session Termination Confirmation
    $('.end-session-btn').on('click', function(e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var name = $(this).data('name');
        
        Swal.fire({
            title: 'End Active Session?',
            text: "Are you sure you want to terminate the active session for " + name + "?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, End Session',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endsection
