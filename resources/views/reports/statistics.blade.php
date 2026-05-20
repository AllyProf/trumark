@extends('layouts.vali')

@section('title', auth()->user()->role === 'sales_officer' ? 'My Market Statistics' : 'Market Demographics & Statistics')

@section('page_icon', 'fa-bar-chart')

@section('subtitle')
{{ auth()->user()->role === 'sales_officer' ? 'Personal breakdown of your assigned leads and growth' : 'Dedicated breakdown of customer types, schools, parents and growth trends' }}
@endsection

@section('styles')
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

    /* Extra Small / Tiny Button style */
    .btn-xs {
        padding: 2px 6px !important;
        font-size: 10px !important;
        line-height: 1.4 !important;
        border-radius: 4px !important;
        font-weight: 600;
    }

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

        /* Responsive Datatable Transformation (Convert Table Rows to Cards) */
        #statsTable thead { 
            display: none !important; 
        }
        #statsTable, #statsTable tbody, #statsTable tr, #statsTable td {
            display: block !important;
            width: 100% !important;
        }
        #statsTable tbody {
            background: transparent !important;
        }
        #statsTable tr {
            background: #ffffff !important;
            margin-bottom: 15px !important;
            border: 1px solid rgba(0,0,0,0.08) !important;
            border-radius: 10px !important;
            box-shadow: 0 3px 8px rgba(0,0,0,0.03) !important;
            padding: 12px 15px !important;
            transition: transform 0.15s;
        }
        #statsTable tr:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
        }
        #statsTable td {
            border: none !important;
            padding: 6px 0 !important;
            font-size: 13px !important;
            border-bottom: 1px dashed #f0f0f0 !important;
            display: flex !important;
            justify-content: flex-start;
            align-items: center;
        }
        #statsTable td:last-child {
            border-bottom: none !important;
            justify-content: center !important;
            padding-top: 10px !important;
        }
    }
</style>
@endsection

@section('content')

{{-- ── Filter & Action Bar (Fully Responsive Grid) ───────────────── --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="tile p-3 shadow-sm mb-0">
            <form action="{{ route('reports.statistics') }}" method="GET">
                <div class="row align-items-end">
                    
                    {{-- Branch --}}
                    <div class="col-12 col-md-3 mb-3 mb-md-0">
                        <label class="font-weight-bold small"><i class="fa fa-filter text-primary mr-1"></i> Branch</label>
                        <select name="branch_id" class="form-control" onchange="this.form.submit()">
                            <option value="">🌍 Global (All Branches)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ ($branchId ?? null) == $branch->id ? 'selected' : '' }}>
                                    📍 {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date Range --}}
                    <div class="col-12 col-md-5 mb-3 mb-md-0">
                        <label class="font-weight-bold small"><i class="fa fa-calendar text-primary mr-1"></i> Date Range</label>
                        <div class="row no-gutters align-items-center">
                            <div class="col">
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate ?? '' }}" onchange="this.form.submit()">
                            </div>
                            <div class="col-auto px-2">
                                <small class="text-muted">to</small>
                            </div>
                            <div class="col">
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate ?? '' }}" onchange="this.form.submit()">
                            </div>
                        </div>
                    </div>

                    {{-- Presets (Lifetime, Week, etc) --}}
                    <div class="col-12 col-md-4 text-md-right mt-2 mt-md-0" style="align-self: flex-end;">
                        <label class="d-block d-md-none font-weight-bold small"><i class="fa fa-clock-o text-primary mr-1"></i> Quick Presets</label>
                        <div class="btn-group d-flex w-100 w-md-auto">
                            <a href="{{ route('reports.statistics', ['branch_id' => request('branch_id'), 'time_filter' => 'all']) }}" class="btn btn-sm flex-fill {{ $timeFilter == 'all' ? 'btn-primary' : 'btn-outline-primary' }}">Lifetime</a>
                            <a href="{{ route('reports.statistics', ['branch_id' => request('branch_id'), 'time_filter' => 'week']) }}" class="btn btn-sm flex-fill {{ $timeFilter == 'week' ? 'btn-primary' : 'btn-outline-primary' }}">Week</a>
                            <a href="{{ route('reports.statistics', ['branch_id' => request('branch_id'), 'time_filter' => 'month']) }}" class="btn btn-sm flex-fill {{ $timeFilter == 'month' ? 'btn-primary' : 'btn-outline-primary' }}">Month</a>
                            <a href="{{ route('reports.statistics', ['branch_id' => request('branch_id'), 'time_filter' => 'year']) }}" class="btn btn-sm flex-fill {{ $timeFilter == 'year' ? 'btn-primary' : 'btn-outline-primary' }}">Year</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── 2x2 Grid KPI Widgets on Mobile ─────────────────────────────── --}}
<div class="row">
    <div class="col-6 col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon">
            <i class="icon fa fa-users fa-3x"></i>
            <div class="info">
                <h4>Total Clients</h4>
                <p><b>{{ number_format($stats['total']) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="widget-small success coloured-icon">
            <i class="icon fa fa-star fa-3x"></i>
            <div class="info">
                <h4>Scoped Records</h4>
                <p><b>{{ number_format($stats['total']) }}</b></p>
                <small class="text-muted" style="font-size: 10px;">{{ $timeFilter === 'custom' ? 'Custom Range' : ucfirst($timeFilter) }}</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon">
            <i class="icon fa fa-bolt fa-3x"></i>
            <div class="info">
                <h4>Registered Today</h4>
                <p><b>{{ number_format($stats['new_today']) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon">
            <i class="icon fa fa-building fa-3x"></i>
            <div class="info">
                <h4>Active Companies</h4>
                <p><b>{{ number_format($stats['companies']) }}</b></p>
            </div>
        </div>
    </div>
</div>

{{-- ── Segment Breakdown & Lead Status Row ──────────────────────── --}}
<div class="row">
    {{-- Market Segment Breakdown --}}
    <div class="col-md-6 col-12 mb-4">
        <div class="tile h-100 mb-0">
            <h3 class="tile-title"><i class="fa fa-pie-chart mr-2 text-primary"></i> Market Segment Breakdown</h3>
            <div class="table-responsive">
                <table class="table table-sm table-hover" style="font-size: 13px;">
                    <tbody>
                        <tr class="bg-light">
                            <td colspan="4"><i class="fa fa-university mr-2"></i> <b>EDUCATIONAL INSTITUTIONS</b></td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px; width: 35%;">Primary Schools</td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($stats['primary_schools'] / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td class="text-right" style="width: 15%;"><b>{{ $stats['primary_schools'] }}</b></td>
                            <td class="text-right" style="width: 15%;">
                                <button class="btn btn-xs btn-outline-primary type-filter-btn" data-type="Primary" onclick="filterByType(this,'Primary')">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 20px; width: 35%;">Secondary Schools</td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($stats['secondary_schools'] / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td class="text-right" style="width: 15%;"><b>{{ $stats['secondary_schools'] }}</b></td>
                            <td class="text-right" style="width: 15%;">
                                <button class="btn btn-xs btn-outline-primary type-filter-btn" data-type="Secondary" onclick="filterByType(this,'Secondary')">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>

                        <tr class="bg-light">
                            <td colspan="4"><i class="fa fa-tags mr-2"></i> <b>OTHER MARKET SEGMENTS</b></td>
                        </tr>
                        @foreach($typeData as $type => $count)
                            @if(stripos($type, 'Primary') === false && stripos($type, 'Secondary') === false)
                            <tr>
                                <td style="width: 35%;">{{ $type }}</td>
                                <td class="align-middle">
                                    @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-info" style="width: {{ $pct }}%"></div>
                                    </div>
                                </td>
                                <td class="text-right" style="width: 15%;"><b>{{ $count }}</b></td>
                                <td class="text-right" style="width: 15%;">
                                    <button class="btn btn-xs btn-outline-info type-filter-btn" data-type="{{ $type }}" onclick="filterByType(this,'{{ $type }}')">
                                        <i class="fa fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Status & Pipeline --}}
    <div class="col-md-6 col-12 mb-4">
        <div class="tile h-100 mb-0">
            <h3 class="tile-title"><i class="fa fa-check-square mr-2 text-success"></i> Lead Status & Pipeline</h3>
            <div class="table-responsive">
                <table class="table table-sm table-hover" style="font-size: 13px;">
                    <tbody>
                        @foreach($statusData as $status => $count)
                        <tr>
                            <td style="width: 35%;"><b>{{ $status }}</b></td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td style="width: 15%;" class="text-right"><b>{{ $count }}</b></td>
                            <td style="width: 15%;" class="text-right">
                                <button class="btn btn-xs btn-outline-success status-filter-btn" data-status="{{ $status }}" onclick="filterByStatus(this,'{{ $status }}')">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Stage & Source Row ────────────────────────────────────────── --}}
<div class="row">
    {{-- Stage --}}
    <div class="col-md-6 col-12 mb-4">
        <div class="tile h-100 mb-0">
            <h3 class="tile-title"><i class="fa fa-level-up mr-2 text-info"></i> Pipeline Stage Distribution</h3>
            <div class="table-responsive">
                <table class="table table-sm table-hover" style="font-size: 13px;">
                    <tbody>
                        @foreach($stageData as $stage => $data)
                        <tr>
                            <td style="width: 30%;">
                                <b>{{ $stage }}</b><br>
                                <small class="text-muted">TZS {{ number_format($data['value'] ?? 0) }}</small>
                            </td>
                            <td class="align-middle" style="width: 40%;">
                                @php $pct = $stats['total'] > 0 ? ($data['total'] / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-info" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td style="width: 15%;" class="text-right"><b>{{ $data['total'] }}</b></td>
                            <td style="width: 15%;" class="text-right">
                                <button class="btn btn-xs btn-outline-info stage-filter-btn" data-stage="{{ $stage }}" onclick="filterByStage(this, '{{ $stage }}')">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Source --}}
    <div class="col-md-6 col-12 mb-4">
        <div class="tile h-100 mb-0">
            <h3 class="tile-title"><i class="fa fa-bullhorn mr-2 text-primary"></i> Lead Source Analysis</h3>
            <div class="table-responsive">
                <table class="table table-sm table-hover" style="font-size: 13px;">
                    <tbody>
                        @foreach($sourceData as $source => $count)
                        <tr>
                            <td style="width: 35%;"><b>{{ $source }}</b></td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td style="width: 15%;" class="text-right"><b>{{ $count }}</b></td>
                            <td style="width: 15%;" class="text-right">
                                <button class="btn btn-xs btn-outline-primary" onclick="filterTable('{{ $source }}')">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Region & Branch Distribution Row ───────────────────────────── --}}
<div class="row mb-4">
    {{-- Regions --}}
    <div class="col-md-6 col-12 mb-4 mb-md-0">
        <div class="tile h-100 mb-0">
            <h3 class="tile-title"><i class="fa fa-map mr-2 text-primary"></i> Top Performing Regions</h3>
            <div class="table-responsive">
                <table class="table table-sm table-hover" style="font-size: 13px;">
                    <tbody>
                        @foreach($regionData as $region => $count)
                        <tr>
                            <td style="width: 30%;"><b>{{ $region }}</b></td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-info" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td style="width: 15%;" class="text-right"><b>{{ $count }}</b></td>
                            <td style="width: 15%;" class="text-right">
                                <button class="btn btn-xs btn-outline-primary region-filter-btn" data-region="{{ $region }}" onclick="filterByRegion(this, '{{ $region }}')">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Branches --}}
    <div class="col-md-6 col-12">
        <div class="tile h-100 mb-0">
            <h3 class="tile-title"><i class="fa fa-sitemap mr-2 text-warning"></i> Branch Distribution</h3>
            <div class="table-responsive">
                <table class="table table-sm table-hover" style="font-size: 13px;">
                    <tbody>
                        @foreach($branchData as $branch => $count)
                        <tr>
                            <td style="width: 30%;"><b>{{ $branch }}</b></td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td style="width: 15%;" class="text-right"><b>{{ $count }}</b></td>
                            <td style="width: 15%;" class="text-right">
                                <button class="btn btn-xs btn-outline-warning" onclick="filterTable('{{ $branch }}')">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ── Detailed Ledger Table (Transforming to Cards on Mobile) ────── --}}
<div class="row">
    <div class="col-12">
        <div class="tile mb-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                <h3 class="tile-title mb-0"><i class="fa fa-list mr-2 text-primary"></i> Detailed Market Segment Ledger</h3>
                <div id="activeFilters" class="mt-2 mt-md-0" style="display:none;"></div>
            </div>

            <div class="table-responsive" style="border: none;">
                <table class="table table-hover table-bordered" id="statsTable" style="font-size: 13px; width: 100%;">
                    <thead class="bg-light">
                        <tr>
                            <th>Company / Organization Name</th>
                            <th>Segment Type</th>
                            <th>Contact Person</th>
                            <th>Assigned Officer</th>
                            <th>Location (Region)</th>
                            <th>Source</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                        <tr data-region="{{ $customer->region ?? '' }}" data-stage="{{ $customer->buying_stage ?? '' }}" data-status="{{ $customer->status ?? '' }}" data-type="{{ (is_array($customer->school_level) ? implode(', ', $customer->school_level) : $customer->school_level) ?: ($customer->type ?? '') }}">
                            <td>
                                <span class="d-inline-block d-md-none text-muted font-weight-bold mr-1" style="width: 110px;">Company Name:</span>
                                <div class="d-inline-block align-middle">
                                    <b class="text-dark">{{ strtoupper($customer->name) }}</b><br>
                                    @if($customer->school_level)
                                        <span class="badge badge-light text-primary border px-2 py-0"><i class="fa fa-graduation-cap"></i> {{ is_array($customer->school_level) ? implode(', ', $customer->school_level) : $customer->school_level }}</span><br>
                                    @endif
                                    <small class="text-muted"><i class="fa fa-phone mr-1"></i> {{ $customer->phone }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="d-inline-block d-md-none text-muted font-weight-bold mr-1" style="width: 110px;">Segment:</span>
                                <span class="badge badge-light border">
                                    {{ $customer->type ?? 'Unspecified' }}
                                </span>
                            </td>
                            <td>
                                <span class="d-inline-block d-md-none text-muted font-weight-bold mr-1" style="width: 110px;">Contact Person:</span>
                                <span>{{ $customer->contact_person ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="d-inline-block d-md-none text-muted font-weight-bold mr-1" style="width: 110px;">Assigned Officer:</span>
                                <div class="d-inline-block align-middle">
                                    <b>{{ $customer->salesOfficer->name ?? 'Unassigned' }}</b><br>
                                    <small class="text-muted">{{ $customer->branch->name ?? 'No Branch' }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="d-inline-block d-md-none text-muted font-weight-bold mr-1" style="width: 110px;">Location:</span>
                                <span>{{ $customer->region ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="d-inline-block d-md-none text-muted font-weight-bold mr-1" style="width: 110px;">Lead Source:</span>
                                <span class="badge badge-light border text-dark">
                                    {{ $customer->source ?? 'Direct' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="d-inline-block d-md-none text-muted font-weight-bold mr-1" style="width: 110px; text-align: left;">Lead Status:</span>
                                @php 
                                    $statusColor = 'secondary';
                                    if($customer->status == 'VIP') $statusColor = 'warning';
                                    if($customer->status == 'Existing Customer') $statusColor = 'success';
                                    if($customer->status == 'Potential Customer') $statusColor = 'info';
                                @endphp
                                <span class="badge badge-{{ $statusColor }}">{{ $customer->status }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-sm btn-info w-100 w-md-auto">
                                    <i class="fa fa-eye mr-1"></i> View Details
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
var statsDataTable;
var activeRegion = null;
var activeStage  = null;
var activeStatus = null;
var activeType   = null;

// ── Custom multi-dimension filter (all 4 dimensions combined) ────────────────
$.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    if (settings.nTable.id !== 'statsTable') return true;
    var api       = new $.fn.dataTable.Api(settings);
    var rowNode   = api.row(dataIndex).node();
    var $row      = $(rowNode);
    var regionOk  = !activeRegion || ($row.data('region') || '').toString().trim() === activeRegion;
    var stageOk   = !activeStage  || ($row.data('stage')  || '').toString().trim() === activeStage;
    var statusOk  = !activeStatus || ($row.data('status') || '').toString().trim() === activeStatus;
    var typeOk    = !activeType   || ($row.data('type')   || '').toString().trim() === activeType;
    return regionOk && stageOk && statusOk && typeOk;
});

// ── Scroll helper ────────────────────────────────────────────────────────────
function scrollToTable() {
    $('html, body').animate({ scrollTop: $('#statsTable').offset().top - 100 }, 400);
}

// ── Badge colours per dimension ───────────────────────────────────────────────
var dimMeta = {
    region: { icon: 'fa-map-marker', cls: 'badge-primary',   clear: 'clearRegion' },
    stage:  { icon: 'fa-level-up',   cls: 'badge-info',      clear: 'clearStage'  },
    status: { icon: 'fa-check',      cls: 'badge-success',   clear: 'clearStatus' },
    type:   { icon: 'fa-tag',        cls: 'badge-warning',   clear: 'clearType'   }
};

// ── Filter badge renderer ─────────────────────────────────────────────────────
function renderFilterBadge() {
    var parts = [];
    var active = { region: activeRegion, stage: activeStage, status: activeStatus, type: activeType };
    $.each(active, function(dim, val) {
        if (!val) return;
        var m = dimMeta[dim];
        parts.push(
            '<span class="badge ' + m.cls + ' mr-1 py-1 px-2">'
            + '<i class="fa ' + m.icon + ' mr-1"></i>' + val
            + ' <a href="#" class="text-white ml-1" onclick="' + m.clear + '();return false;"><i class="fa fa-times"></i></a>'
            + '</span>'
        );
    });
    var $bar = $('#activeFilters');
    if (parts.length) {
        $bar.html(
            '<span class="text-muted small mr-2"><i class="fa fa-filter mr-1"></i>Active:</span>'
            + parts.join('')
            + '<a href="#" class="btn btn-xs btn-outline-danger ml-2 font-weight-bold" onclick="clearAllFilters();return false;"><i class="fa fa-times mr-1"></i>Clear all</a>'
        ).show();
    } else {
        $bar.hide();
    }
}

// ── Generic dimension toggle helper ──────────────────────────────────────────
function toggleDim(varName, val, btn, btnClass, activeClass, icon) {
    var cur = window['active' + varName.charAt(0).toUpperCase() + varName.slice(1)];
    var selector = '.' + varName.toLowerCase() + '-filter-btn';
    var off = '<i class="fa ' + icon + ' mr-1"></i> View';
    var on  = '<i class="fa fa-check mr-1"></i> Active';
    if (cur === val) {
        window['active' + varName.charAt(0).toUpperCase() + varName.slice(1)] = null;
        $(btn).html(off).removeClass(activeClass).addClass(btnClass);
    } else {
        $(selector).html(off).removeClass(activeClass).addClass(btnClass);
        window['active' + varName.charAt(0).toUpperCase() + varName.slice(1)] = val;
        $(btn).html(on).removeClass(btnClass).addClass(activeClass);
    }
    statsDataTable.draw();
    renderFilterBadge();
    scrollToTable();
}

// ── Public filter functions ───────────────────────────────────────────────────
function filterByRegion(btn, region) {
    toggleDim('Region', region, btn, 'btn-xs btn-outline-primary', 'btn-primary', 'fa-eye');
}
function filterByStage(btn, stage) {
    toggleDim('Stage', stage, btn, 'btn-xs btn-outline-info', 'btn-info', 'fa-eye');
}
function filterByStatus(btn, status) {
    toggleDim('Status', status, btn, 'btn-xs btn-outline-success', 'btn-success', 'fa-eye');
}
function filterByType(btn, type) {
    toggleDim('Type', type, btn, 'btn-xs btn-outline-primary', 'btn-primary', 'fa-eye');
}

function filterTable(val) {
    statsDataTable.search(val).draw();
    scrollToTable();
}

// ── Individual clear helpers (called from badge × links) ──────────────────────
function clearRegion() {
    activeRegion = null;
    $('.region-filter-btn').html('<i class="fa fa-eye"></i> View').removeClass('btn-primary').addClass('btn-xs').addClass('btn-outline-primary');
    statsDataTable.draw(); renderFilterBadge();
}
function clearStage() {
    activeStage = null;
    $('.stage-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-info').addClass('btn-xs').addClass('btn-outline-info');
    statsDataTable.draw(); renderFilterBadge();
}
function clearStatus() {
    activeStatus = null;
    $('.status-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-success').addClass('btn-xs').addClass('btn-outline-success');
    statsDataTable.draw(); renderFilterBadge();
}
function clearType() {
    activeType = null;
    $('.type-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-primary').addClass('btn-xs').addClass('btn-outline-primary');
    statsDataTable.draw(); renderFilterBadge();
}
function clearAllFilters(redraw) {
    activeRegion = activeStage = activeStatus = activeType = null;
    $('.region-filter-btn').html('<i class="fa fa-eye"></i> View').removeClass('btn-primary').addClass('btn-xs').addClass('btn-outline-primary');
    $('.stage-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-info').addClass('btn-xs').addClass('btn-outline-info');
    $('.status-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-success').addClass('btn-xs').addClass('btn-outline-success');
    $('.type-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-primary').addClass('btn-xs').addClass('btn-outline-primary');
    if (redraw !== false) { statsDataTable.search('').draw(); }
    renderFilterBadge();
}

$(document).ready(function() {
    statsDataTable = $('#statsTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25,
        "retrieve": true
    });
});
</script>
@endsection
