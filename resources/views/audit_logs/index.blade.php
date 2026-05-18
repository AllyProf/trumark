@extends('layouts.vali')

@section('title', 'Security Audit Logs')
@section('page_icon', 'fa-shield')
@section('subtitle', 'Dedicated track of administrative operations, who did what, when, where, and from which internet provider.')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <!-- Filter & Search Toolbar -->
            <form id="audit-filter-form" action="{{ route('audit_logs.index') }}" method="GET" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-lg-4 col-md-6 col-12 mb-3 mb-lg-0">
                        <label class="form-label font-weight-bold text-muted small">SEARCH ACTIONS / IP / LOCATION / ISP</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-search text-primary"></i></span>
                            </div>
                            <input type="text" name="search" id="search-input" class="form-control" placeholder="Search logs..." value="{{ $search }}">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
                        <label class="form-label font-weight-bold text-muted small">CATEGORY</label>
                        <select name="category" id="category-select" class="form-control">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
                        <label class="form-label font-weight-bold text-muted small">STAFF MEMBER</label>
                        <select name="user_id" id="user-select" class="form-control">
                            <option value="">All Staff</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ ucwords(str_replace('_', ' ', $u->role)) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6 col-12 text-right">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-filter mr-1"></i> Filter</button>
                            <a href="{{ route('audit_logs.index') }}" class="btn btn-secondary" title="Reset Filters"><i class="fa fa-refresh"></i></a>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Desktop View: Elegant Responsive Table -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-striped table-bordered text-center align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 15%;">Timestamp</th>
                            <th style="width: 18%;">Staff Member</th>
                            <th style="width: 12%;">Category</th>
                            <th style="width: 25%;">Action Performed</th>
                            <th style="width: 15%;">IP / Network</th>
                            <th style="width: 10%;">Location</th>
                            <th style="width: 5%;" class="d-print-none">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                $badgeClass = 'badge-secondary';
                                if($log->category === 'Authentication') $badgeClass = 'badge-purple';
                                elseif($log->category === 'Customers') $badgeClass = 'badge-info';
                                elseif($log->category === 'Staff Management') $badgeClass = 'badge-indigo';
                                elseif($log->category === 'KPI') $badgeClass = 'badge-success';
                                elseif($log->category === 'Settings') $badgeClass = 'badge-warning';
                            @endphp
                            <tr>
                                <td>
                                    <small class="d-block font-weight-bold text-dark">{{ $log->created_at->format('d M, Y') }}</small>
                                    <small class="text-muted">{{ $log->created_at->format('H:i:s') }}</small>
                                </td>
                                <td class="text-left">
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border mr-2" style="width: 32px; height: 32px; overflow: hidden; flex-shrink: 0;">
                                            @if($log->user && $log->user->avatar)
                                                <img src="{{ asset('storage/' . $log->user->avatar) }}" class="img-fluid" alt="Avatar">
                                            @else
                                                <span class="font-weight-bold text-primary" style="font-size: 11px;">{{ strtoupper(substr($log->user ? $log->user->name : 'U', 0, 2)) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="d-block font-weight-bold" style="font-size: 13px;">{{ $log->user ? $log->user->name : 'System / Deleted User' }}</span>
                                            <span class="badge badge-light border text-muted py-0 px-1" style="font-size: 9px; font-weight: 500;">
                                                {{ ucwords(str_replace('_', ' ', $log->user ? $log->user->role : 'system')) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-pill {{ $badgeClass }}" style="font-size: 10px; font-weight: 600; letter-spacing: 0.3px; padding: 4px 10px;">
                                        {{ $log->category }}
                                    </span>
                                </td>
                                <td class="text-left" style="font-size: 13px; font-weight: 500; color: #333;">
                                    {{ $log->action }}
                                </td>
                                <td class="text-left">
                                    <small class="text-muted d-block font-weight-bold">{{ $log->ip_address }}</small>
                                    @if($log->isp && $log->isp !== 'Unknown')
                                        <span class="badge badge-light border text-secondary mt-1" style="font-size: 10px; font-weight: 600; letter-spacing: 0.2px;">
                                            <i class="fa fa-wifi text-primary mr-1" style="font-size: 9px;"></i> {{ $log->isp }}
                                        </span>
                                    @else
                                        <span class="badge badge-light border text-muted mt-1" style="font-size: 10px; font-weight: 500;">
                                            <i class="fa fa-wifi mr-1" style="font-size: 9px;"></i> Local / Unknown
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->location && $log->location !== 'Unknown' && $log->location !== 'Localhost')
                                        <span class="badge badge-pill badge-info"><i class="fa fa-map-marker mr-1"></i> {{ $log->location }}</span>
                                    @else
                                        <span class="badge badge-pill badge-light border text-muted font-weight-bold" style="font-size: 10px;"><i class="fa fa-map-marker mr-1"></i> Local</span>
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
                                            title="View Technical Details">
                                        <i class="fa fa-info-circle" style="font-size: 13px;"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-5 text-muted">
                                    <i class="fa fa-shield fa-3x d-block mb-3 text-light"></i>
                                    No audit logs matching filters found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile View: Premium Card-Based Layout -->
            <div class="d-block d-md-none mobile-card-container">
                @forelse($logs as $log)
                    @php
                        $borderClass = 'border-secondary';
                        if($log->category === 'Authentication') $borderClass = 'border-purple';
                        elseif($log->category === 'Customers') $borderClass = 'border-info';
                        elseif($log->category === 'Staff Management') $borderClass = 'border-indigo';
                        elseif($log->category === 'KPI') $borderClass = 'border-success';
                        elseif($log->category === 'Settings') $borderClass = 'border-warning';
                    @endphp
                    <div class="card mb-3 shadow-sm border-0 border-left-highlight {{ $borderClass }}" style="border-radius: 8px;">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge badge-light border text-muted small font-weight-bold">{{ $log->category }}</span>
                                <small class="text-muted font-weight-bold"><i class="fa fa-clock-o mr-1"></i> {{ $log->created_at->format('H:i:s') }}</small>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border mr-2" style="width: 28px; height: 28px; overflow: hidden; flex-shrink: 0;">
                                    @if($log->user && $log->user->avatar)
                                        <img src="{{ asset('storage/' . $log->user->avatar) }}" class="img-fluid" alt="Avatar">
                                    @else
                                        <span class="font-weight-bold text-primary" style="font-size: 10px;">{{ strtoupper(substr($log->user ? $log->user->name : 'U', 0, 2)) }}</span>
                                    @endif
                                </div>
                                <div>
                                    <strong style="font-size: 13px; color: #333;">{{ $log->user ? $log->user->name : 'System' }}</strong>
                                    <small class="text-muted d-block" style="font-size: 10px;">{{ $log->created_at->format('d M, Y') }}</small>
                                </div>
                            </div>
                            <p class="mb-2 text-dark font-weight-bold" style="font-size: 13px; line-height: 1.4;">{{ $log->action }}</p>
                            <div class="row pt-2 mt-2 border-top no-gutters">
                                <div class="col-6 pr-1">
                                    <small class="text-muted d-block">IP Address</small>
                                    <strong class="text-dark small d-block">{{ $log->ip_address }}</strong>
                                </div>
                                <div class="col-6 pl-1">
                                    <small class="text-muted d-block">Location</small>
                                    <strong class="text-dark small d-block"><i class="fa fa-map-marker text-danger mr-1"></i> {{ $log->location ?: 'Local' }}</strong>
                                </div>
                                <div class="col-12 mt-2">
                                    <small class="text-muted d-block">Network Operator (ISP)</small>
                                    <strong class="text-secondary small d-block"><i class="fa fa-wifi text-primary mr-1"></i> {{ $log->isp ?: 'Local Network' }}</strong>
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
                    <div class="card p-4 text-center border text-muted">
                        <i class="fa fa-shield fa-2x mb-2 text-light"></i>
                        No audit logs available.
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-4">
                <small class="text-muted font-weight-bold mb-2 mb-md-0">
                    Showing {{ $logs->firstItem() ?: 0 }} to {{ $logs->lastItem() ?: 0 }} of {{ $logs->total() }} audit transactions.
                </small>
                <div>{{ $logs->links() }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Technical Specifications Modal (Bootstrap — no transparency issues) -->
<div class="modal fade" id="auditDetailModal" tabindex="-1" role="dialog" aria-labelledby="auditDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); color: #fff;">
                <h5 class="modal-title" id="auditDetailModalLabel">
                    <i class="fa fa-shield mr-2"></i> Technical Specifications — Security Log Entry
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div style="background-color: #1e1e2e; color: #cdd6f4; font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.8; padding: 24px; border-radius: 0;">
                    <div class="mb-2">
                        <span style="color: #89dceb;">$</span>
                        <span style="color: #a6e3a1; font-weight: bold;"> TIMESTAMP</span>
                        <span style="color: #cdd6f4;">  : </span>
                        <span id="modal-time" style="color: #f9e2af;"></span>
                    </div>
                    <div class="mb-2">
                        <span style="color: #89dceb;">$</span>
                        <span style="color: #a6e3a1; font-weight: bold;"> OPERATOR</span>
                        <span style="color: #cdd6f4;">   : </span>
                        <span id="modal-user" style="color: #cba6f7;"></span>
                    </div>
                    <div class="mb-2">
                        <span style="color: #89dceb;">$</span>
                        <span style="color: #a6e3a1; font-weight: bold;"> ACTION</span>
                        <span style="color: #cdd6f4;">     : </span>
                        <span id="modal-action" style="color: #ffffff;"></span>
                    </div>
                    <div class="mb-2">
                        <span style="color: #89dceb;">$</span>
                        <span style="color: #a6e3a1; font-weight: bold;"> IP_ADDRESS</span>
                        <span style="color: #cdd6f4;"> : </span>
                        <span id="modal-ip" style="color: #89b4fa;"></span>
                    </div>
                    <div class="mb-2">
                        <span style="color: #89dceb;">$</span>
                        <span style="color: #a6e3a1; font-weight: bold;"> LOCATION</span>
                        <span style="color: #cdd6f4;">   : </span>
                        <span id="modal-location" style="color: #fab387;"></span>
                    </div>
                    <div class="mb-3">
                        <span style="color: #89dceb;">$</span>
                        <span style="color: #a6e3a1; font-weight: bold;"> PROVIDER</span>
                        <span style="color: #cdd6f4;">   : </span>
                        <span id="modal-isp" style="color: #a6e3a1;"></span>
                    </div>
                    <hr style="border-color: #45475a; margin: 12px 0;">
                    <div>
                        <span style="color: #f38ba8; font-weight: bold;">[ USER AGENT SIGNATURE ]</span><br>
                        <span id="modal-agent" style="color: #9399b2; font-size: 11px; word-break: break-all; line-height: 1.6;"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background-color: #f8f9fa;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times mr-1"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .badge-purple { background-color: #6f42c1; color: #ffffff; }
    .badge-indigo { background-color: #3f51b5; color: #ffffff; }
    .border-left-highlight { border-left: 4px solid !important; }
    .border-purple  { border-left-color: #6f42c1 !important; }
    .border-info    { border-left-color: #17a2b8 !important; }
    .border-indigo  { border-left-color: #3f51b5 !important; }
    .border-success { border-left-color: #28a745 !important; }
    .border-warning { border-left-color: #ffc107 !important; }
    .border-secondary { border-left-color: #6c757d !important; }
    #auditDetailModal .modal-content { border: none; border-radius: 8px; overflow: hidden; }
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // ─── Details Button → Bootstrap Modal ──────────────────────────────────────
    $(document).on('click', '.btn-detail', function() {
        var btn = $(this);
        $('#modal-time').text(btn.data('time') || 'N/A');
        $('#modal-user').text(btn.data('user') || 'System');
        $('#modal-action').text(btn.data('action') || 'N/A');
        $('#modal-ip').text(btn.data('ip') || 'N/A');
        $('#modal-location').text(btn.data('location') || 'Local / Unknown');
        $('#modal-isp').text(btn.data('isp') || 'Local Network');
        $('#modal-agent').text(btn.data('agent') || 'No device signature available');
        $('#auditDetailModal').modal('show');
    });

    // ─── Filter Form: ensure native <select> values are submitted ──────────────
    // Do NOT init select2 on these — plain selects submit reliably
    // Only run select2 if explicitly needed elsewhere; keep audit filters as native
    $('#audit-filter-form').on('submit', function(e) {
        // Remove empty params from URL to keep it clean
        var $form = $(this);
        $form.find('select, input').each(function() {
            if ($(this).val() === '' || $(this).val() === null) {
                $(this).prop('disabled', true);
            }
        });
        return true;
    });
});
</script>
@endsection
