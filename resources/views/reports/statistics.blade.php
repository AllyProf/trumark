@extends('layouts.vali')

@section('title', auth()->user()->role === 'sales_officer' ? 'My Market Statistics' : 'Market Demographics & Statistics')

@section('page_icon', 'fa-bar-chart')

@section('subtitle')
{{ auth()->user()->role === 'sales_officer' ? 'Personal breakdown of your assigned leads and growth' : 'Dedicated breakdown of customer types, schools, parents and growth trends' }}
@endsection

@section('styles')
<style>
    .widget-small .info h4 {
        text-transform: none;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 0;
    }
    .widget-small .info p {
        font-size: 20px;
    }
</style>
@endsection

@section('content')

{{-- Filter & Action Bar --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="tile p-3">
            <form action="{{ route('reports.statistics') }}" method="GET" class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-filter text-primary mr-2"></i>
                        <span class="font-weight-bold mr-2">Branch:</span>
                        <select name="branch_id" class="form-control select2-branch" onchange="this.form.submit()">
                            <option value="">🌍 Global (All Branches)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ ($branchId ?? null) == $branch->id ? 'selected' : '' }}>
                                    📍 {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="d-flex align-items-center">
                        <span class="font-weight-bold mr-2">Date Range:</span>
                        <input type="date" name="start_date" class="form-control form-control-sm mr-1" value="{{ $startDate ?? '' }}" onchange="this.form.submit()">
                        <span class="mx-1">to</span>
                        <input type="date" name="end_date" class="form-control form-control-sm ml-1" value="{{ $endDate ?? '' }}" onchange="this.form.submit()">
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="btn-group">
                        <a href="{{ route('reports.statistics', ['branch_id' => request('branch_id'), 'time_filter' => 'all']) }}" class="btn btn-sm {{ $timeFilter == 'all' ? 'btn-primary' : 'btn-outline-primary' }}">Lifetime</a>
                        <a href="{{ route('reports.statistics', ['branch_id' => request('branch_id'), 'time_filter' => 'week']) }}" class="btn btn-sm {{ $timeFilter == 'week' ? 'btn-primary' : 'btn-outline-primary' }}">Week</a>
                        <a href="{{ route('reports.statistics', ['branch_id' => request('branch_id'), 'time_filter' => 'month']) }}" class="btn btn-sm {{ $timeFilter == 'month' ? 'btn-primary' : 'btn-outline-primary' }}">Month</a>
                        <a href="{{ route('reports.statistics', ['branch_id' => request('branch_id'), 'time_filter' => 'year']) }}" class="btn btn-sm {{ $timeFilter == 'year' ? 'btn-primary' : 'btn-outline-primary' }}">Year</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Top Row: Built-in Vali Widgets --}}
<div class="row mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small primary coloured-icon">
            <i class="icon fa fa-users fa-3x"></i>
            <div class="info">
                <h4>Total Clients</h4>
                <p><b>{{ number_format($stats['total']) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small success coloured-icon">
            <i class="icon fa fa-star fa-3x"></i>
            <div class="info">
                <h4>Scoped Records</h4>
                <p><b>{{ number_format($stats['total']) }}</b></p>
                <small class="text-muted">{{ $timeFilter === 'custom' ? 'Custom Range' : ucfirst($timeFilter) }}</small>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small info coloured-icon">
            <i class="icon fa fa-bolt fa-3x"></i>
            <div class="info">
                <h4>Registered Today</h4>
                <p><b>{{ number_format($stats['new_today']) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small warning coloured-icon">
            <i class="icon fa fa-building fa-3x"></i>
            <div class="info">
                <h4>Active Companies</h4>
                <p><b>{{ number_format($stats['companies']) }}</b></p>
            </div>
        </div>
    </div>
</div>

{{-- Main Analytics Row --}}
<div class="row mb-4">
    {{-- Left: Market Segment Breakdown --}}
    <div class="col-md-6">
        <div class="tile h-100">
            <h3 class="tile-title"><i class="fa fa-pie-chart mr-2 text-primary"></i> Market Segment Breakdown</h3>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <tbody>
                        {{-- Grouped Schools --}}
                        <tr class="bg-light">
                            <td colspan="4"><i class="fa fa-university mr-2"></i> <b>EDUCATIONAL INSTITUTIONS (SCHOOLS)</b></td>
                        </tr>
                        <tr>
                            <td style="padding-left: 30px;">Primary Schools</td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($stats['primary_schools'] / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td class="text-right"><b>{{ $stats['primary_schools'] }}</b></td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline-primary py-0 type-filter-btn" data-type="Primary" onclick="filterByType(this,'Primary')">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-left: 30px;">Secondary Schools</td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($stats['secondary_schools'] / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td class="text-right"><b>{{ $stats['secondary_schools'] }}</b></td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline-primary py-0 type-filter-btn" data-type="Secondary" onclick="filterByType(this,'Secondary')">
                                    <i class="fa fa-eye"></i> View
                                </button>
                            </td>
                        </tr>

                        {{-- Other Segments --}}
                        <tr class="bg-light">
                            <td colspan="4"><i class="fa fa-tags mr-2"></i> <b>OTHER MARKET SEGMENTS</b></td>
                        </tr>
                        @foreach($typeData as $type => $count)
                            @if(stripos($type, 'Primary') === false && stripos($type, 'Secondary') === false)
                            <tr>
                                <td>{{ $type }}</td>
                                <td class="align-middle">
                                    @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-info" style="width: {{ $pct }}%"></div>
                                    </div>
                                </td>
                                <td class="text-right"><b>{{ $count }}</b></td>
                                <td class="text-right">
                                    <button class="btn btn-xs btn-outline-info py-0 type-filter-btn" data-type="{{ $type }}" onclick="filterByType(this,'{{ $type }}')">
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

    {{-- Right: Status & Pipeline --}}
    <div class="col-md-6">
        <div class="tile h-100">
            <h3 class="tile-title"><i class="fa fa-check-square mr-2 text-success"></i> Lead Status & Pipeline</h3>
            <div class="table-responsive">
                <table class="table table-sm table-borderless table-hover">
                    <tbody>
                        @foreach($statusData as $status => $count)
                        <tr>
                            <td style="width: 35%;"><b>{{ $status }}</b></td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td style="width: 15%;" class="text-right"><b>{{ $count }}</b></td>
                            <td style="width: 15%;" class="text-right">
                                <button class="btn btn-xs btn-outline-success py-0 status-filter-btn" data-status="{{ $status }}" onclick="filterByStatus(this,'{{ $status }}')">
                                    <i class="fa fa-eye mr-1"></i> View
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

<div class="row mb-4">
    <div class="col-md-6">
        <div class="tile h-100">
            <h3 class="tile-title"><i class="fa fa-level-up mr-2 text-info"></i> Pipeline Stage Distribution</h3>
            <div class="table-responsive">
                <table class="table table-sm table-borderless table-hover">
                    <tbody>
                        @foreach($stageData as $stage => $count)
                        <tr>
                            <td style="width: 35%;"><b>{{ $stage }}</b></td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td style="width: 15%;" class="text-right"><b>{{ $count }}</b></td>
                            <td style="width: 15%;" class="text-right">
                                <button class="btn btn-xs btn-outline-info py-0 stage-filter-btn" data-stage="{{ $stage }}" onclick="filterByStage(this, '{{ $stage }}')">
                                    <i class="fa fa-eye mr-1"></i> View
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="tile h-100">
            <h3 class="tile-title"><i class="fa fa-bullhorn mr-2 text-primary"></i> Lead Source Analysis</h3>
            <div class="table-responsive">
                <table class="table table-sm table-borderless table-hover">
                    <tbody>
                        @foreach($sourceData as $source => $count)
                        <tr>
                            <td style="width: 35%;"><b>{{ $source }}</b></td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td style="width: 15%;" class="text-right"><b>{{ $count }}</b></td>
                            <td style="width: 15%;" class="text-right">
                                <button class="btn btn-xs btn-outline-primary py-0" onclick="filterTable('{{ $source }}')">
                                    <i class="fa fa-eye mr-1"></i> View
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

{{-- Quick Insights: Regions & Branches --}}
<div class="row mb-4">
    <div class="col-md-6">
        <div class="tile h-100">
            <h3 class="tile-title"><i class="fa fa-map mr-2 text-primary"></i> Top Performing Regions</h3>
            <div class="table-responsive">
                <table class="table table-sm table-borderless">
                    <tbody>
                        @foreach($regionData as $region => $count)
                        <tr>
                            <td style="width: 30%;"><b>{{ $region }}</b></td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td style="width: 15%;" class="text-right"><b>{{ $count }}</b></td>
                            <td style="width: 10%;" class="text-right">
                                <button class="btn btn-sm btn-link text-primary p-0 region-filter-btn" data-region="{{ $region }}" onclick="filterByRegion(this, '{{ $region }}')">
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
    <div class="col-md-6">
        <div class="tile h-100">
            <h3 class="tile-title"><i class="fa fa-sitemap mr-2 text-warning"></i> Branch Distribution</h3>
            <div class="table-responsive">
                <table class="table table-sm table-borderless">
                    <tbody>
                        @foreach($branchData as $branch => $count)
                        <tr>
                            <td style="width: 30%;"><b>{{ $branch }}</b></td>
                            <td class="align-middle">
                                @php $pct = $stats['total'] > 0 ? ($count / $stats['total']) * 100 : 0; @endphp
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                            <td style="width: 15%;" class="text-right"><b>{{ $count }}</b></td>
                            <td style="width: 10%;" class="text-right">
                                <button class="btn btn-sm btn-link text-primary p-0" onclick="filterTable('{{ $branch }}')">
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

{{-- Detailed Data Table --}}
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="tile-title mb-0"><i class="fa fa-list mr-2 text-primary"></i> Detailed Market Segment Ledger</h3>
                <div id="activeFilters" style="display:none;"></div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="statsTable">
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
                        <tr data-region="{{ $customer->region ?? '' }}" data-stage="{{ $customer->buying_stage ?? '' }}" data-status="{{ $customer->status ?? '' }}" data-type="{{ $customer->school_level ?: ($customer->type ?? '') }}">
                            <td>
                                <b>{{ strtoupper($customer->name) }}</b><br>
                                @if($customer->school_level)
                                    <span class="badge badge-light text-primary border px-2 py-0"><i class="fa fa-graduation-cap"></i> {{ $customer->school_level }}</span><br>
                                @endif
                                <small class="text-muted"><i class="fa fa-phone mr-1"></i> {{ $customer->phone }}</small>
                            </td>
                            <td>
                                <span class="badge badge-light border">
                                    {{ $customer->type ?? 'Unspecified' }}
                                </span>
                            </td>
                            <td>{{ $customer->contact_person ?? 'N/A' }}</td>
                            <td>
                                <b>{{ $customer->salesOfficer->name ?? 'Unassigned' }}</b><br>
                                <small class="text-muted">{{ $customer->branch->name ?? 'No Branch' }}</small>
                            </td>
                            <td>{{ $customer->region ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-light border text-dark">
                                    {{ $customer->source ?? 'Direct' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @php 
                                    $statusColor = 'secondary';
                                    if($customer->status == 'VIP') $statusColor = 'warning';
                                    if($customer->status == 'Existing Customer') $statusColor = 'success';
                                    if($customer->status == 'Potential Customer') $statusColor = 'info';
                                @endphp
                                <span class="badge badge-{{ $statusColor }}">{{ $customer->status }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-sm btn-info">
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
            '<span class="text-muted small mr-2"><i class="fa fa-filter mr-1"></i>Active filters:</span>'
            + parts.join('')
            + '<a href="#" class="btn btn-xs btn-outline-danger ml-2" onclick="clearAllFilters();return false;"><i class="fa fa-times mr-1"></i>Clear all</a>'
        ).show();
    } else {
        $bar.hide();
    }
}

// ── Generic dimension toggle helper ──────────────────────────────────────────
function toggleDim(varName, val, btn, btnClass, activeClass, icon) {
    var cur = window['active' + varName.charAt(0).toUpperCase() + varName.slice(1)];
    var selector = '.' + varName + '-filter-btn';
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
    toggleDim('Region', region, btn, 'btn-link text-primary', 'btn-primary', 'fa-eye');
}
function filterByStage(btn, stage) {
    toggleDim('Stage', stage, btn, 'btn-outline-info', 'btn-info', 'fa-eye');
}
function filterByStatus(btn, status) {
    toggleDim('Status', status, btn, 'btn-outline-success', 'btn-success', 'fa-eye');
}
function filterByType(btn, type) {
    toggleDim('Type', type, btn, 'btn-outline-primary', 'btn-primary', 'fa-eye');
}

function filterTable(val) {
    statsDataTable.search(val).draw();
    scrollToTable();
}

// ── Individual clear helpers (called from badge × links) ──────────────────────
function clearRegion() {
    activeRegion = null;
    $('.Region-filter-btn, .region-filter-btn').html('<i class="fa fa-eye"></i> View').removeClass('btn-primary').addClass('btn-link text-primary');
    statsDataTable.draw(); renderFilterBadge();
}
function clearStage() {
    activeStage = null;
    $('.stage-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-info').addClass('btn-outline-info');
    statsDataTable.draw(); renderFilterBadge();
}
function clearStatus() {
    activeStatus = null;
    $('.status-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-success').addClass('btn-outline-success');
    statsDataTable.draw(); renderFilterBadge();
}
function clearType() {
    activeType = null;
    $('.type-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-primary').addClass('btn-outline-primary').addClass('btn-outline-info');
    statsDataTable.draw(); renderFilterBadge();
}
function clearAllFilters(redraw) {
    activeRegion = activeStage = activeStatus = activeType = null;
    $('.region-filter-btn').html('<i class="fa fa-eye"></i> View').removeClass('btn-primary').addClass('btn-link text-primary');
    $('.stage-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-info').addClass('btn-outline-info');
    $('.status-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-success').addClass('btn-outline-success');
    $('.type-filter-btn').html('<i class="fa fa-eye mr-1"></i> View').removeClass('btn-primary');
    if (redraw !== false) { statsDataTable.search('').draw(); }
    renderFilterBadge();
}

$(document).ready(function() {
    $('.select2-branch').select2({
        minimumResultsForSearch: Infinity,
        dropdownAutoWidth: true
    });
    statsDataTable = $('#statsTable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25
    });
});
</script>
@endsection
