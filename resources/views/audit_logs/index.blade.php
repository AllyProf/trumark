@extends('layouts.vali')

@section('title', 'Security Audit Logs')
@section('page_icon', 'fa-shield')
@section('subtitle', 'Dedicated track of administrative operations, who did what, when, where, and from which internet provider.')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <!-- Filter & Search Toolbar -->
            <form action="{{ route('audit_logs.index') }}" method="GET" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-lg-4 col-md-6 col-12 mb-3 mb-lg-0">
                        <label class="form-label font-weight-bold text-muted small uppercase">Search Actions / IP / Location / ISP</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-search text-primary"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control" placeholder="Search logs..." value="{{ $search }}">
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
                        <label class="form-label font-weight-bold text-muted small uppercase">Category</label>
                        <select name="category" class="form-control select2">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-3 col-md-6 col-6 mb-3 mb-lg-0">
                        <label class="form-label font-weight-bold text-muted small uppercase">Staff Member</label>
                        <select name="user_id" class="form-control select2">
                            <option value="">All Staff</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ ucwords(str_replace('_', ' ', $u->role)) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6 col-12 text-right">
                        <div class="btn-group w-100">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-filter mr-1"></i> Filter</button>
                            <a href="{{ route('audit_logs.index') }}" class="btn btn-secondary"><i class="fa fa-refresh"></i></a>
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
                                        <div class="avatar-sm mr-2 rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 32px; height: 32px; overflow: hidden;">
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
                                    <button class="btn btn-sm btn-outline-primary py-0 px-2 btn-detail" 
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
                            <!-- Category + Time Header -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge badge-light border text-muted small font-weight-bold">
                                    {{ $log->category }}
                                </span>
                                <small class="text-muted font-weight-bold">
                                    <i class="fa fa-clock-o mr-1"></i> {{ $log->created_at->format('H:i:s') }}
                                </small>
                            </div>

                            <!-- User Info -->
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-sm mr-2 rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 28px; height: 28px; overflow: hidden;">
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

                            <!-- Action -->
                            <p class="mb-2 text-dark font-weight-bold" style="font-size: 13px; line-height: 1.4;">
                                {{ $log->action }}
                            </p>

                            <!-- Network & Geolocation Footer Grid -->
                            <div class="row pt-2 mt-2 border-top no-gutters">
                                <div class="col-6 pr-1">
                                    <small class="text-muted d-block small">IP Address</small>
                                    <strong class="text-dark small d-block">{{ $log->ip_address }}</strong>
                                </div>
                                <div class="col-6 pl-1">
                                    <small class="text-muted d-block small">Location</small>
                                    <strong class="text-dark small d-block">
                                        <i class="fa fa-map-marker text-danger mr-1"></i> {{ $log->location ?: 'Local' }}
                                    </strong>
                                </div>
                                <div class="col-12 mt-2">
                                    <small class="text-muted d-block small">Network Operator (ISP)</small>
                                    <strong class="text-secondary small d-block">
                                        <i class="fa fa-wifi text-primary mr-1"></i> {{ $log->isp ?: 'Local Network' }}
                                    </strong>
                                </div>
                            </div>

                            <button class="btn btn-block btn-sm btn-light border text-muted mt-3 py-1 btn-detail"
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

            <!-- Custom Pagination -->
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-4">
                <small class="text-muted font-weight-bold mb-2 mb-md-0">
                    Showing {{ $logs->firstItem() ?: 0 }} to {{ $logs->lastItem() ?: 0 }} of {{ $logs->total() }} audit transactions.
                </small>
                <div>
                    {{ $logs->links() }}
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* Premium category badge styling */
    .badge-purple {
        background-color: #6f42c1;
        color: #ffffff;
    }
    .badge-indigo {
        background-color: #3f51b5;
        color: #ffffff;
    }
    /* Border-left highlighting for card view */
    .border-left-highlight {
        border-left: 4px solid !important;
    }
    .border-purple { border-left-color: #6f42c1 !important; }
    .border-info { border-left-color: #17a2b8 !important; }
    .border-indigo { border-left-color: #3f51b5 !important; }
    .border-success { border-left-color: #28a745 !important; }
    .border-warning { border-left-color: #ffc107 !important; }
    .border-secondary { border-left-color: #6c757d !important; }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Init select2 if present
        if($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }

        // technical specifications button trigger using premium SweetAlert2
        $('.btn-detail').on('click', function() {
            const action = $(this).data('action');
            const ip = $(this).data('ip') || 'N/A';
            const location = $(this).data('location') || 'Local / Unknown';
            const isp = $(this).data('isp') || 'Local Network / Operator';
            const agent = $(this).data('agent') || 'No Device Signature Provided';
            const user = $(this).data('user') || 'System';
            const time = $(this).data('time');

            swal({
                title: "Technical Specifications",
                html: true,
                text: `
                    <div class="text-left" style="font-family: 'Courier New', Courier, monospace; font-size: 12px; line-height: 1.5; max-height: 300px; overflow-y: auto; background-color: #f8f9fa; padding: 12px; border: 1px solid #ddd; border-radius: 4px;">
                        <span class="text-primary font-weight-bold">[TIMESTAMP]</span>: ${time}<br>
                        <span class="text-primary font-weight-bold">[OPERATOR]</span> : ${user}<br>
                        <span class="text-primary font-weight-bold">[ACTION]</span>   : ${action}<br>
                        <span class="text-primary font-weight-bold">[IP ADDRESS]</span>: ${ip}<br>
                        <span class="text-primary font-weight-bold">[LOCATION]</span>  : ${location}<br>
                        <span class="text-primary font-weight-bold">[PROVIDER]</span>  : ${isp}<br><br>
                        <span class="text-danger font-weight-bold">[USER AGENT SIGNATURE]:</span><br>
                        <span class="text-muted" style="word-break: break-all;">${agent}</span>
                    </div>
                `,
                icon: "info",
                button: "Close"
            });
        });
    });
</script>
@endsection
