@extends('layouts.vali')

@section('title', 'Security Audit Logs')
@section('page_icon', 'fa-shield')
@section('subtitle', 'Real-time track of administrative operations — who did what, when, where, and from which network.')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">

            <!-- ─── Real-Time Filter Bar ─────────────────────────────────────── -->
            <div class="row align-items-end mb-4" id="audit-filters">
                <!-- Search -->
                <div class="col-lg-4 col-md-6 col-12 mb-3 mb-lg-0">
                    <label class="form-label font-weight-bold text-muted small">SEARCH ACTIONS / IP / LOCATION / ISP</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-search text-primary"></i></span>
                        </div>
                        <input type="text" id="filter-search" class="form-control" placeholder="Type to search in real time...">
                        <div class="input-group-append" id="clear-search-btn" style="display:none; cursor:pointer;">
                            <span class="input-group-text bg-white border-left-0">
                                <i class="fa fa-times text-muted"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Category -->
                <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
                    <label class="form-label font-weight-bold text-muted small">CATEGORY</label>
                    <select id="filter-category" class="form-control">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Staff Member -->
                <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
                    <label class="form-label font-weight-bold text-muted small">STAFF MEMBER</label>
                    <select id="filter-user" class="form-control">
                        <option value="">All Staff</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Reset -->
                <div class="col-lg-2 col-md-6 col-12 text-right">
                    <button type="button" id="reset-filters" class="btn btn-outline-secondary w-100">
                        <i class="fa fa-refresh mr-1"></i> Reset All
                    </button>
                </div>
            </div>

            <!-- Live Result Count -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <span id="visible-count" class="font-weight-bold text-dark" style="font-size: 14px;">{{ $logs->count() }}</span>
                    <span class="text-muted" style="font-size: 13px;"> of {{ $logs->total() }} audit entries</span>
                    <span id="filter-active-badge" class="badge badge-primary badge-pill ml-2" style="display:none; font-size: 10px;">Filtered</span>
                </div>
                <div id="no-results-msg" class="text-muted font-italic" style="display:none; font-size: 13px;">
                    <i class="fa fa-search mr-1"></i> No results match your filters.
                </div>
            </div>

            <!-- ─── Desktop Table ───────────────────────────────────────────── -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-striped table-bordered text-center" id="audit-table">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:14%;">Timestamp</th>
                            <th style="width:17%;">Staff Member</th>
                            <th style="width:12%;">Category</th>
                            <th style="width:27%;">Action Performed</th>
                            <th style="width:15%;">IP / Network</th>
                            <th style="width:10%;">Location</th>
                            <th style="width:5%;" class="d-print-none">Details</th>
                        </tr>
                    </thead>
                    <tbody id="audit-tbody">
                        @forelse($logs as $log)
                            @php
                                $badgeClass = 'badge-secondary';
                                if($log->category === 'Authentication')    $badgeClass = 'badge-purple';
                                elseif($log->category === 'Customers')     $badgeClass = 'badge-info';
                                elseif($log->category === 'Staff Management') $badgeClass = 'badge-indigo';
                                elseif($log->category === 'KPI')           $badgeClass = 'badge-success';
                                elseif($log->category === 'Settings')      $badgeClass = 'badge-warning';
                            @endphp
                            <tr class="audit-row"
                                data-search="{{ strtolower(($log->user ? $log->user->name : '') . ' ' . $log->action . ' ' . $log->ip_address . ' ' . $log->location . ' ' . $log->isp) }}"
                                data-category="{{ $log->category }}"
                                data-user="{{ $log->user_id }}">
                                <td>
                                    <small class="d-block font-weight-bold text-dark">{{ $log->created_at->format('d M, Y') }}</small>
                                    <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                </td>
                                <td class="text-left">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border mr-2" style="width:32px;height:32px;overflow:hidden;flex-shrink:0;">
                                            @if($log->user && $log->user->avatar)
                                                <img src="{{ asset('storage/' . $log->user->avatar) }}" class="img-fluid" alt="Avatar">
                                            @else
                                                <span class="font-weight-bold text-primary" style="font-size:11px;">{{ strtoupper(substr($log->user ? $log->user->name : 'U', 0, 2)) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="d-block font-weight-bold" style="font-size:13px;">{{ $log->user ? $log->user->name : 'System / Deleted User' }}</span>
                                            <span class="badge badge-light border text-muted py-0 px-1" style="font-size:9px;">{{ ucwords(str_replace('_', ' ', $log->user ? $log->user->role : 'system')) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-pill {{ $badgeClass }}" style="font-size:10px;font-weight:600;letter-spacing:0.3px;padding:4px 10px;">{{ $log->category }}</span>
                                </td>
                                <td class="text-left" style="font-size:13px;font-weight:500;color:#333;">{{ $log->action }}</td>
                                <td class="text-left">
                                    <small class="text-muted d-block font-weight-bold">{{ $log->ip_address }}</small>
                                    @if($log->isp && $log->isp !== 'Unknown')
                                        <span class="badge badge-light border text-secondary mt-1" style="font-size:10px;font-weight:600;">
                                            <i class="fa fa-wifi text-primary mr-1" style="font-size:9px;"></i> {{ $log->isp }}
                                        </span>
                                    @else
                                        <span class="badge badge-light border text-muted mt-1" style="font-size:10px;">
                                            <i class="fa fa-wifi mr-1" style="font-size:9px;"></i> Local / Unknown
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->location && $log->location !== 'Unknown' && $log->location !== 'Localhost')
                                        <span class="badge badge-pill badge-info"><i class="fa fa-map-marker mr-1"></i>{{ $log->location }}</span>
                                    @else
                                        <span class="badge badge-pill badge-light border text-muted font-weight-bold" style="font-size:10px;"><i class="fa fa-map-marker mr-1"></i>Local</span>
                                    @endif
                                </td>
                                <td class="d-print-none">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 btn-detail"
                                            data-action="{{ $log->action }}"
                                            data-ip="{{ $log->ip_address }}"
                                            data-location="{{ $log->location }}"
                                            data-isp="{{ $log->isp }}"
                                            data-agent="{{ $log->user_agent }}"
                                            data-user="{{ $log->user ? $log->user->name : 'System' }}"
                                            data-time="{{ $log->created_at->format('d M, Y - H:i:s') }}"
                                            title="Technical Details">
                                        <i class="fa fa-info-circle" style="font-size:13px;"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-5 text-muted"><i class="fa fa-shield fa-3x d-block mb-3 text-light"></i>No audit logs found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- ─── Mobile Cards ──────────────────────────────────────────────── -->
            <div class="d-block d-md-none" id="audit-mobile-cards">
                @forelse($logs as $log)
                    @php
                        $borderClass = 'border-secondary';
                        if($log->category === 'Authentication')       $borderClass = 'border-purple';
                        elseif($log->category === 'Customers')        $borderClass = 'border-info';
                        elseif($log->category === 'Staff Management') $borderClass = 'border-indigo';
                        elseif($log->category === 'KPI')              $borderClass = 'border-success';
                        elseif($log->category === 'Settings')         $borderClass = 'border-warning';
                    @endphp
                    <div class="audit-card card mb-3 shadow-sm border-0 border-left-highlight {{ $borderClass }}"
                         style="border-radius:8px;"
                         data-search="{{ strtolower(($log->user ? $log->user->name : '') . ' ' . $log->action . ' ' . $log->ip_address . ' ' . $log->location . ' ' . $log->isp) }}"
                         data-category="{{ $log->category }}"
                         data-user="{{ $log->user_id }}">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge badge-light border text-muted small font-weight-bold">{{ $log->category }}</span>
                                <small class="text-muted font-weight-bold"><i class="fa fa-clock-o mr-1"></i>{{ $log->created_at->format('H:i:s') }}</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border mr-2" style="width:28px;height:28px;overflow:hidden;flex-shrink:0;">
                                    @if($log->user && $log->user->avatar)
                                        <img src="{{ asset('storage/' . $log->user->avatar) }}" class="img-fluid" alt="Avatar">
                                    @else
                                        <span class="font-weight-bold text-primary" style="font-size:10px;">{{ strtoupper(substr($log->user ? $log->user->name : 'U', 0, 2)) }}</span>
                                    @endif
                                </div>
                                <div>
                                    <strong style="font-size:13px;color:#333;">{{ $log->user ? $log->user->name : 'System' }}</strong>
                                    <small class="text-muted d-block" style="font-size:10px;">{{ $log->created_at->format('d M, Y') }}</small>
                                </div>
                            </div>
                            <p class="mb-2 text-dark font-weight-bold" style="font-size:13px;line-height:1.4;">{{ $log->action }}</p>
                            <div class="row pt-2 mt-2 border-top no-gutters">
                                <div class="col-6 pr-1">
                                    <small class="text-muted d-block">IP Address</small>
                                    <strong class="text-dark small d-block">{{ $log->ip_address }}</strong>
                                </div>
                                <div class="col-6 pl-1">
                                    <small class="text-muted d-block">Location</small>
                                    <strong class="text-dark small d-block"><i class="fa fa-map-marker text-danger mr-1"></i>{{ $log->location ?: 'Local' }}</strong>
                                </div>
                                <div class="col-12 mt-2">
                                    <small class="text-muted d-block">Network Operator (ISP)</small>
                                    <strong class="text-secondary small d-block"><i class="fa fa-wifi text-primary mr-1"></i>{{ $log->isp ?: 'Local Network' }}</strong>
                                </div>
                            </div>
                            <button type="button" class="btn btn-block btn-sm btn-light border text-muted mt-3 py-1 btn-detail"
                                    data-action="{{ $log->action }}"
                                    data-ip="{{ $log->ip_address }}"
                                    data-location="{{ $log->location }}"
                                    data-isp="{{ $log->isp }}"
                                    data-agent="{{ $log->user_agent }}"
                                    data-user="{{ $log->user ? $log->user->name : 'System' }}"
                                    data-time="{{ $log->created_at->format('d M, Y - H:i:s') }}">
                                <i class="fa fa-terminal mr-1"></i> Technical Specifications
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="card p-4 text-center border text-muted"><i class="fa fa-shield fa-2x mb-2 text-light"></i>No audit logs available.</div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-4 pt-3 border-top">
                <small class="text-muted font-weight-bold mb-2 mb-md-0">
                    Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}
                    &nbsp;·&nbsp;
                    <span id="visible-count-pg">{{ $logs->count() }}</span> of {{ $logs->total() }} entries shown
                </small>
                <div>{{ $logs->links() }}</div>
            </div>

        </div>
    </div>
</div>

<!-- ─── Technical Specifications Modal ──────────────────────────────────── -->
<div class="modal fade" id="auditDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;">
                <h5 class="modal-title"><i class="fa fa-shield mr-2"></i> Technical Specifications — Security Log Entry</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                <div id="audit-terminal" style="background:#1e1e2e;color:#cdd6f4;font-family:'Courier New',Courier,monospace;font-size:13px;line-height:1.9;padding:24px;">
                    <div class="mb-1"><span style="color:#89dceb;">$</span> <span style="color:#a6e3a1;font-weight:bold;">TIMESTAMP</span><span style="color:#585b70;">  ──</span> <span id="m-time"  style="color:#f9e2af;"></span></div>
                    <div class="mb-1"><span style="color:#89dceb;">$</span> <span style="color:#a6e3a1;font-weight:bold;">OPERATOR</span><span style="color:#585b70;">   ──</span> <span id="m-user"  style="color:#cba6f7;"></span></div>
                    <div class="mb-1"><span style="color:#89dceb;">$</span> <span style="color:#a6e3a1;font-weight:bold;">ACTION</span><span style="color:#585b70;">     ──</span> <span id="m-action" style="color:#fff;"></span></div>
                    <div class="mb-1"><span style="color:#89dceb;">$</span> <span style="color:#a6e3a1;font-weight:bold;">IP_ADDRESS</span><span style="color:#585b70;"> ──</span> <span id="m-ip"    style="color:#89b4fa;"></span></div>
                    <div class="mb-1"><span style="color:#89dceb;">$</span> <span style="color:#a6e3a1;font-weight:bold;">LOCATION</span><span style="color:#585b70;">   ──</span> <span id="m-loc"   style="color:#fab387;"></span></div>
                    <div class="mb-3"><span style="color:#89dceb;">$</span> <span style="color:#a6e3a1;font-weight:bold;">PROVIDER</span><span style="color:#585b70;">   ──</span> <span id="m-isp"   style="color:#a6e3a1;"></span></div>
                    <hr style="border-color:#45475a;margin:12px 0;">
                    <div><span style="color:#f38ba8;font-weight:bold;">[ DEVICE / USER AGENT ]</span><br><span id="m-agent" style="color:#9399b2;font-size:11px;word-break:break-all;line-height:1.6;"></span></div>
                </div>
            </div>
            <div class="modal-footer" style="background:#f8f9fa;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fa fa-times mr-1"></i> Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    .badge-purple  { background-color: #6f42c1; color: #fff; }
    .badge-indigo  { background-color: #3f51b5; color: #fff; }
    .border-left-highlight { border-left: 4px solid !important; }
    .border-purple   { border-left-color: #6f42c1 !important; }
    .border-info     { border-left-color: #17a2b8 !important; }
    .border-indigo   { border-left-color: #3f51b5 !important; }
    .border-success  { border-left-color: #28a745 !important; }
    .border-warning  { border-left-color: #ffc107 !important; }
    .border-secondary{ border-left-color: #6c757d !important; }

    /* Smooth row fade-in/out on filter */
    .audit-row, .audit-card {
        transition: opacity 0.18s ease, transform 0.18s ease;
    }
    .audit-row.hidden-row, .audit-card.hidden-row {
        display: none !important;
    }

    /* Highlight matched search text */
    .hl { background: #fff176; border-radius: 2px; padding: 0 2px; }

    /* Search input active state */
    #filter-search:focus { border-color: #4e73df; box-shadow: 0 0 0 0.15rem rgba(78,115,223,0.2); }

    /* Filter bar labels */
    #audit-filters label { letter-spacing: 0.5px; font-size: 10px; margin-bottom: 4px; }
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function () {

    // ─── Cached references ───────────────────────────────────────────────
    var $searchInput  = $('#filter-search');
    var $catSelect    = $('#filter-category');
    var $userSelect   = $('#filter-user');
    var $rows         = $('.audit-row');
    var $cards        = $('.audit-card');
    var $countBadge   = $('#visible-count');
    var $filterBadge  = $('#filter-active-badge');
    var $noResultsMsg = $('#no-results-msg');
    var $clearBtn     = $('#clear-search-btn');

    // ─── Filter Engine ───────────────────────────────────────────────────
    function applyFilters() {
        // Read values directly from elements (avoids 'this' context issues)
        var search   = $searchInput.val().toLowerCase().trim();
        var category = $catSelect.val();
        var user     = $userSelect.val();
        var hasFilter = (search !== '' || category !== '' || user !== '');
        var matchCount = 0;

        function matches($el) {
            var ok = true;
            if (search   && ($el.attr('data-search')   || '').indexOf(search)   === -1) ok = false;
            if (category && ($el.attr('data-category') || '') !== category)              ok = false;
            if (user     && String($el.attr('data-user') || '') !== user)                ok = false;
            return ok;
        }

        // Desktop rows
        $rows.each(function () {
            var $el = $(this);
            if (matches($el)) { $el.removeClass('hidden-row'); matchCount++; }
            else               { $el.addClass('hidden-row'); }
        });

        // Mobile cards
        $cards.each(function () {
            var $el = $(this);
            if (matches($el)) $el.removeClass('hidden-row');
            else              $el.addClass('hidden-row');
        });

        // UI feedback
        $countBadge.text(matchCount);
        $('#visible-count-pg').text(matchCount);
        $filterBadge.toggle(hasFilter);
        $noResultsMsg.toggle(matchCount === 0 && $rows.length > 0);
        $clearBtn.toggle(search !== '');
    }

    // ─── Search with debounce ────────────────────────────────────────────
    var searchTimer;
    $searchInput.on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyFilters, 180);
    });

    // ─── Dropdowns: instant ──────────────────────────────────────────────
    $catSelect.on('change', applyFilters);
    $userSelect.on('change', applyFilters);

    // ─── Clear X inside search box ───────────────────────────────────────
    $clearBtn.on('click', function () {
        $searchInput.val('').trigger('input').focus();
    });

    // ─── Reset all ───────────────────────────────────────────────────────
    $('#reset-filters').on('click', function () {
        $searchInput.val('');
        $catSelect.val('');
        $userSelect.val('');
        applyFilters();
    });

    // ─── Details Modal ───────────────────────────────────────────────────
    $(document).on('click', '.btn-detail', function () {
        var b = $(this);
        $('#m-time').text(b.data('time')     || 'N/A');
        $('#m-user').text(b.data('user')     || 'System');
        $('#m-action').text(b.data('action') || 'N/A');
        $('#m-ip').text(b.data('ip')         || 'N/A');
        $('#m-loc').text(b.data('location')  || 'Local / Unknown');
        $('#m-isp').text(b.data('isp')       || 'Local Network');
        $('#m-agent').text(b.data('agent')   || 'No device signature available');
        $('#auditDetailModal').modal('show');
    });

    // ─── Init count ──────────────────────────────────────────────────────
    var total = $rows.length > 0 ? $rows.length : $cards.length;
    $countBadge.text(total);
    $('#visible-count-pg').text(total);

});
</script>
@endsection
