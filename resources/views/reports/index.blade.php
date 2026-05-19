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
    /* ── KPI Stat Widget Styling ─────────────────────────────────────── */
    .widget-small { 
        min-height: 90px; 
        display: flex; 
        align-items: center; 
        margin-bottom: 20px; 
        border-radius: 6px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .widget-small .icon {
        width: 65px;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .widget-small .info {
        padding: 12px 15px;
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
        font-size: 20px;
    }
    .conv-bar-wrap { background: #e9ecef; border-radius: 20px; height: 12px; overflow: hidden; margin-top: 5px; }
    .conv-bar { background: #940000; height: 100%; border-radius: 20px; transition: width 1.5s ease; }
    
    /* ── Mobile Layout Optimization ─────────────────────────────────── */
    @media (max-width: 767px) {
        .widget-small {
            min-height: 75px;
            margin-bottom: 15px;
        }
        .widget-small .icon {
            width: 48px;
            font-size: 1.2rem;
        }
        .widget-small .info {
            padding: 8px 12px;
        }
        .widget-small .info h4 {
            font-size: 11px;
        }
        .widget-small .info p {
            font-size: 15px;
        }
        .mobile-canvas-ranking, .mobile-canvas-usage {
            background-color: #f8f9fa;
            padding: 10px 4px;
            border-radius: 8px;
        }
        .ranking-card, .usage-card {
            border: 1px solid rgba(0,0,0,0.06) !important;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03) !important;
            transition: transform 0.15s;
        }
        .ranking-card:hover, .usage-card:hover {
            transform: translateY(-1px);
        }
    }

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

{{-- ── Scope Toolbar (Responsive) ────────────────────────────────── --}}
<div class="row mb-4 d-print-none">
    <div class="col-12">
        <div class="tile p-3 shadow-sm mb-0">
            <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                <div class="d-flex align-items-center mb-2 mb-md-0 mr-md-3">
                    <i class="fa fa-filter text-primary mr-2"></i>
                    <span class="font-weight-bold">Report Scope:</span>
                </div>
                @if(auth()->user()->role === 'super_admin')
                <form action="{{ route('reports.index') }}" method="GET" class="w-100 w-md-auto d-inline-block">
                    <select name="branch_id" class="form-control w-100" onchange="this.form.submit()" style="min-width: 240px;">
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
        </div>
    </div>
</div>

{{-- ── 2x2 Grid KPI Widgets on Mobile, 4-col layout on Desktop ────── --}}
<div class="row">
    <div class="col-6 col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon"><i class="icon fa fa-users fa-2x"></i>
            <div class="info">
                <h4>Total Leads</h4>
                <p><b>{{ $totalLeads }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon"><i class="icon fa fa-line-chart fa-2x"></i>
            <div class="info">
                <h4>Pipeline Value</h4>
                <p><b>{{ formatKpiValue($totalPipelineValue) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="widget-small success coloured-icon"><i class="icon fa fa-trophy fa-2x"></i>
            <div class="info">
                <h4>Revenue Won</h4>
                <p><b>{{ formatKpiValue($totalWonValue) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon"><i class="icon fa fa-clock-o fa-2x"></i>
            <div class="info">
                <h4>Pending</h4>
                <p><b>{{ $pendingPayment }}</b></p>
            </div>
        </div>
    </div>
</div>

{{-- ── Charts Section (Responsive Canvas Containers) ──────────────── --}}
<div class="row">
    <div class="col-md-4 col-12 mb-4 mb-md-0">
        <div class="tile h-100 mb-0">
            <h3 class="tile-title">Sales Funnel Distribution</h3>
            <div class="chart-container" style="position: relative; width: 100%; height: 260px;">
                <canvas id="stageChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-8 col-12">
        <div class="tile h-100 mb-0">
            <h3 class="tile-title">Monthly Lead Registrations</h3>
            <div class="chart-container" style="position: relative; width: 100%; height: 260px;">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-8 col-12 mb-4 mb-md-0">
        <div class="tile h-100 mb-0">
            <h3 class="tile-title">Pipeline Value by Stage (TZS)</h3>
            <div class="chart-container" style="position: relative; width: 100%; height: 260px;">
                <canvas id="pipelineChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-12">
        <div class="tile h-100 mb-0">
            <h3 class="tile-title">Market Segment Breakdown</h3>
            <div class="chart-container" style="position: relative; width: 100%; height: 200px;">
                <canvas id="typeChart"></canvas>
            </div>
            <div class="mt-3">
                <ul class="list-group list-group-flush" style="font-size: 13px;">
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 rounded mb-1 p-2">
                        <span><i class="fa fa-university mr-2 text-primary"></i> Primary Schools</span>
                        <span class="badge badge-primary badge-pill">{{ $stats['primary_schools'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 rounded mb-1 p-2">
                        <span><i class="fa fa-graduation-cap mr-2 text-info"></i> Secondary Schools</span>
                        <span class="badge badge-info badge-pill">{{ $stats['secondary_schools'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 rounded mb-1 p-2">
                        <span><i class="fa fa-user-circle mr-2 text-warning"></i> Parents</span>
                        <span class="badge badge-warning badge-pill">{{ $stats['parents'] }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-light border-0 rounded mb-1 p-2">
                        <span><i class="fa fa-street-view mr-2 text-danger"></i> Walk-ins</span>
                        <span class="badge badge-danger badge-pill">{{ $stats['walk_ins'] }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- ── Leaderboard & Usage Logs ────────────────────────────────────── --}}
@if(auth()->user()->role !== 'sales_officer')
<div class="row mt-4">
    {{-- Sales Officer Performance Ranking --}}
    <div class="col-12 mb-4">
        <div class="tile mb-0">
            <h3 class="tile-title">Sales Officer Performance Ranking</h3>
            
            {{-- Desktop performance table --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-bordered" id="performanceTable" style="font-size: 13px;">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 8%;">Rank</th>
                            <th>Officer & Level</th>
                            <th style="width: 15%;">Total Leads</th>
                            <th style="width: 15%;">KPI Score</th>
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

            {{-- Mobile performance cards --}}
            <div class="d-block d-md-none mobile-canvas-ranking">
                @foreach($topOfficers as $i => $officer)
                @php $pct = $totalLeads > 0 ? ($officer->lead_count / $totalLeads) * 100 : 0; @endphp
                <div class="card mb-3 border-0 shadow-sm ranking-card" style="border-radius: 8px; background: #fff; border-left: 4px solid {{ $officer->kpi_level['color'] ?? '#940000' }};">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="font-weight-bold text-dark" style="font-size: 14px;">#{{ $i + 1 }} {{ $officer->salesOfficer->name ?? 'Unassigned' }}</span>
                                <div class="mt-1">
                                    <span class="badge" style="background: {{ $officer->kpi_level['color'] }}; color: white; font-size: 9px; padding: 2px 6px;">
                                        <i class="fa {{ $officer->kpi_level['icon'] }} mr-1"></i> {{ $officer->kpi_level['name'] }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="d-block font-weight-bold text-primary" style="font-size: 14px;">{{ $officer->kpi_points }} <small class="text-muted" style="font-size: 8px;">pts</small></span>
                                <small class="text-muted" style="font-size: 11px;">{{ $officer->lead_count }} leads</small>
                            </div>
                        </div>
                        <div class="pt-2 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted" style="font-size: 10px;">Performance Share</small>
                                <small class="font-weight-bold" style="font-size: 10px;">{{ round($pct, 1) }}%</small>
                            </div>
                            <div class="conv-bar-wrap" style="height: 6px;">
                                <div class="conv-bar" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Staff Attendance & System Usage --}}
    <div class="col-12">
        <div class="tile mb-0">
            <h3 class="tile-title"><i class="fa fa-clock-o mr-2 text-primary"></i> Staff Attendance & System Usage</h3>
            
            {{-- Desktop usage table --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-bordered" style="font-size: 13px;">
                    <thead class="bg-light">
                        <tr>
                            <th>Staff Member</th>
                            <th>Entry Time (Login)</th>
                            <th>Exit Time (Logout)</th>
                            <th>Active Duration</th>
                            <th>IP Address</th>
                            <th>Location</th>
                            @if(in_array(auth()->user()->role, ['super_admin', 'manager']))
                            <th class="d-print-none text-center" style="width: 130px;">Actions</th>
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
                            <td class="d-print-none text-center">
                                @if(!$log->logout_at)
                                    <form id="force-logout-form-{{ $log->id }}" action="{{ route('reports.force_logout', $log->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="button" class="btn btn-danger btn-sm px-2 py-1 end-session-btn" data-id="{{ $log->id }}" data-name="{{ $log->user->name ?? 'Unknown' }}" style="font-size: 11px; border-radius: 20px;">
                                            <i class="fa fa-power-off mr-1"></i> End Session
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small"><i class="fa fa-check-circle text-success mr-1"></i> Closed</span>
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

            {{-- Mobile usage cards --}}
            <div class="d-block d-md-none mobile-canvas-usage">
                @forelse($usageLogs as $log)
                <div class="card mb-3 border-0 shadow-sm usage-card" style="border-radius: 8px; background: #fff; border-left: 4px solid {{ $log->logout_at ? '#6c757d' : '#28a745' }};">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <b class="text-dark" style="font-size: 14px;">{{ $log->user->name ?? 'Unknown' }}</b>
                                <div class="mt-1">
                                    @if($log->logout_at)
                                        <span class="badge badge-secondary" style="font-size: 9px; padding: 2px 6px;">Closed Session</span>
                                    @else
                                        <span class="badge badge-success" style="font-size: 9px; padding: 2px 6px;"><i class="fa fa-spinner fa-spin mr-1"></i> Active Now</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                @if($log->logout_at)
                                    <span class="badge badge-light border" style="font-size: 10px; padding: 2px 6px;">{{ $log->duration_minutes }} mins</span>
                                @else
                                    <span class="text-muted" style="font-size: 10px;">Active...</span>
                                @endif
                            </div>
                        </div>

                        <div class="bg-light p-2 rounded mb-2" style="font-size: 11px; border: 1px solid #eee;">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="fa fa-sign-in mr-1"></i> Login:</span>
                                <span class="font-weight-bold text-dark">{{ $log->login_at->format('d M, H:i') }}</span>
                            </div>
                            @if($log->logout_at)
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="fa fa-sign-out mr-1"></i> Logout:</span>
                                <span class="font-weight-bold text-dark">{{ $log->logout_at->format('d M, H:i') }}</span>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="fa fa-globe mr-1"></i> IP Address:</span>
                                <span class="font-weight-bold text-dark">{{ $log->ip_address }}</span>
                            </div>
                            @if($log->isp && $log->isp !== 'Unknown')
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted"><i class="fa fa-wifi mr-1"></i> ISP:</span>
                                <span class="font-weight-bold text-dark text-truncate ml-2" style="max-width: 150px;" title="{{ $log->isp }}">{{ $log->isp }}</span>
                            </div>
                            @endif
                            <div class="d-flex justify-content-between">
                                <span class="text-muted"><i class="fa fa-map-marker mr-1"></i> Location:</span>
                                <span class="font-weight-bold text-dark">{{ $log->location ?: 'Local/Unknown' }}</span>
                            </div>
                        </div>

                        @if(in_array(auth()->user()->role, ['super_admin', 'manager']))
                        <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                            <span class="text-muted" style="font-size: 11px;">Actions:</span>
                            @if(!$log->logout_at)
                            <form id="force-logout-form-mobile-{{ $log->id }}" action="{{ route('reports.force_logout', $log->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="button" class="btn btn-danger btn-sm px-3 end-session-btn" data-id="{{ $log->id }}" data-name="{{ $log->user->name ?? 'Unknown' }}" style="font-size: 10px; border-radius: 20px; padding: 2px 10px;">
                                    <i class="fa fa-power-off mr-1"></i> End Session
                                </button>
                            </form>
                            @else
                            <small class="text-success font-weight-bold"><i class="fa fa-check-circle"></i> Closed Successfully</small>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="card p-4 text-center border text-muted">
                    No usage logs found for this period.
                </div>
                @endforelse
            </div>

            {{-- Bootstrap Pagination --}}
            @if($usageLogs->hasPages())
            <div class="d-flex justify-content-between align-items-center mt-3 px-1 flex-wrap">
                <small class="text-muted mb-2 mb-md-0">
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

$(document).ready(function() {

    $('#performanceTable').DataTable({
        "retrieve": true,
        "paging": true,
        "searching": true,
        "info": false,
        "order": [[3, "desc"]]
    });

    // Charts with full responsive and aspect-ratio maintenance settings
    new Chart(document.getElementById('stageChart'), {
        type: 'doughnut',
        data: {
            labels: {!! json_encode(array_keys($stageData)) !!},
            datasets: [{
                data: {!! json_encode(array_values($stageData)) !!},
                backgroundColor: ['#940000', '#17a2b8', '#ffc107', '#28a745', '#dc3545', '#6f42c1', '#007bff', '#adb5bd']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
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
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
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
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false
        }
    });

    new Chart(document.getElementById('typeChart'), {
        type: 'pie',
        data: {
            labels: {!! json_encode(array_keys($typeData)) !!},
            datasets: [{
                data: {!! json_encode(array_values($typeData)) !!},
                backgroundColor: ['#940000', '#17a2b8', '#ffc107', '#28a745']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
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
