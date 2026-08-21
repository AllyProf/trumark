@extends('layouts.vali')

@section('title', 'System Usage & Login Tracking')

@section('page_icon', 'fa-sign-in')

@section('subtitle')
Monitor who uses the CRM, session duration, and staff who are not logging in
@endsection

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
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
        min-height: 90px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .widget-small .info { padding: 12px 15px; flex-grow: 1; }
    .widget-small .info h4 {
        text-transform: none;
        font-weight: 700;
        font-size: 13px;
        margin-bottom: 2px;
        color: #666;
    }
    .widget-small .info p { margin-bottom: 0; font-size: 22px; font-weight: 700; }
    .usage-filter-pill.active { background: #940000 !important; color: #fff !important; border-color: #940000 !important; }
</style>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-md-12">
        <form method="GET" action="{{ route('reports.system_usage') }}" class="row align-items-end">
            @if(auth()->user()->role === 'super_admin')
            <div class="form-group col-md-3">
                <label class="control-label">Branch</label>
                <select name="branch_id" class="form-control select2" onchange="this.form.submit()">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="form-group col-md-3">
                <label class="control-label">Period</label>
                <select name="period" class="form-control" onchange="this.form.submit()">
                    <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>This Month</option>
                    <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
                </select>
            </div>
            <div class="form-group col-md-3">
                <label class="control-label">Staff Member (sessions)</label>
                <select name="staff_id" class="form-control select2" onchange="this.form.submit()">
                    <option value="">All Staff</option>
                    @foreach($allStaff as $member)
                        <option value="{{ $member->id }}" {{ (string) $staffFilter === (string) $member->id ? 'selected' : '' }}>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <a href="{{ route('reports.system_usage') }}" class="btn btn-secondary btn-block"><i class="fa fa-refresh"></i> Reset</a>
            </div>
            @if($usageFilter !== 'all')
                <input type="hidden" name="usage_filter" value="{{ $usageFilter }}">
            @endif
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="widget-small primary coloured-icon">
            <i class="icon fa fa-users fa-3x"></i>
            <div class="info">
                <h4>Total Staff</h4>
                <p>{{ $summary['total_staff'] }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="{{ route('reports.system_usage', array_merge(request()->query(), ['usage_filter' => 'online'])) }}" class="text-decoration-none">
            <div class="widget-small success coloured-icon">
                <i class="icon fa fa-circle fa-3x"></i>
                <div class="info">
                    <h4>Online Now</h4>
                    <p>{{ $summary['online_now'] }}</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="{{ route('reports.system_usage', array_merge(request()->query(), ['usage_filter' => 'active'])) }}" class="text-decoration-none">
            <div class="widget-small info coloured-icon">
                <i class="icon fa fa-check-circle fa-3x"></i>
                <div class="info">
                    <h4>Active (7 days)</h4>
                    <p>{{ $summary['active_users'] }}</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="{{ route('reports.system_usage', array_merge(request()->query(), ['usage_filter' => 'inactive'])) }}" class="text-decoration-none">
            <div class="widget-small warning coloured-icon">
                <i class="icon fa fa-exclamation-triangle fa-3x"></i>
                <div class="info">
                    <h4>Not Using (7+ days)</h4>
                    <p>{{ $summary['inactive_users'] }}</p>
                </div>
            </div>
        </a>
    </div>
</div>

@if($summary['never_used'] > 0)
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-danger d-flex justify-content-between align-items-center flex-wrap">
            <span><i class="fa fa-user-times mr-2"></i> <strong>{{ $summary['never_used'] }}</strong> staff member(s) have never logged into the system.</span>
            <a href="{{ route('reports.system_usage', array_merge(request()->query(), ['usage_filter' => 'never'])) }}" class="btn btn-sm btn-danger mt-2 mt-md-0">View List</a>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title"><i class="fa fa-id-badge mr-2"></i> Staff Usage Summary</h3>
                <div>
                    <a href="{{ route('reports.system_usage', request()->except('usage_filter')) }}" class="btn btn-sm btn-outline-secondary usage-filter-pill {{ $usageFilter === 'all' ? 'active' : '' }}">All</a>
                    <a href="{{ route('reports.system_usage', array_merge(request()->query(), ['usage_filter' => 'online'])) }}" class="btn btn-sm btn-outline-success usage-filter-pill {{ $usageFilter === 'online' ? 'active' : '' }}">Online</a>
                    <a href="{{ route('reports.system_usage', array_merge(request()->query(), ['usage_filter' => 'active'])) }}" class="btn btn-sm btn-outline-info usage-filter-pill {{ $usageFilter === 'active' ? 'active' : '' }}">Active</a>
                    <a href="{{ route('reports.system_usage', array_merge(request()->query(), ['usage_filter' => 'inactive'])) }}" class="btn btn-sm btn-outline-warning usage-filter-pill {{ $usageFilter === 'inactive' ? 'active' : '' }}">Inactive</a>
                    <a href="{{ route('reports.system_usage', array_merge(request()->query(), ['usage_filter' => 'never'])) }}" class="btn btn-sm btn-outline-danger usage-filter-pill {{ $usageFilter === 'never' ? 'active' : '' }}">Never Used</a>
                </div>
            </div>
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Staff Member</th>
                                <th>Role</th>
                                <th>Branch</th>
                                <th>Last Login</th>
                                <th>Sessions</th>
                                <th>Time Used</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffUsage as $member)
                            @php
                                $hours = intdiv($member->total_minutes, 60);
                                $mins = $member->total_minutes % 60;
                                $timeLabel = $member->total_minutes < 60
                                    ? $member->total_minutes . ' mins'
                                    : $hours . 'h' . ($mins ? ' ' . $mins . 'm' : '');
                            @endphp
                            <tr>
                                <td><b>{{ $member->name }}</b></td>
                                <td><span class="badge badge-light border text-uppercase">{{ str_replace('_', ' ', $member->role) }}</span></td>
                                <td>{{ $member->branch->name ?? 'Global' }}</td>
                                <td>
                                    @if($member->last_login_at)
                                        <span class="font-weight-bold">{{ $member->last_login_at->format('d M Y') }}</span><br>
                                        <small class="text-muted">{{ $member->last_login_at->format('H:i') }} · {{ $member->last_login_at->diffForHumans() }}</small>
                                    @else
                                        <span class="text-danger font-weight-bold">Never</span>
                                    @endif
                                </td>
                                <td>{{ $member->session_count }}</td>
                                <td><span class="badge badge-light border">{{ $timeLabel }}</span></td>
                                <td>
                                    @if($member->usage_status === 'online')
                                        <span class="badge badge-success"><i class="fa fa-circle mr-1"></i> Online Now</span>
                                    @elseif($member->usage_status === 'active')
                                        <span class="badge badge-info">Active User</span>
                                    @elseif($member->usage_status === 'inactive')
                                        <span class="badge badge-warning">Not Using</span>
                                    @else
                                        <span class="badge badge-danger">Never Logged In</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No staff match the selected filters.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title"><i class="fa fa-history mr-2"></i> Login Session History</h3>
            </div>
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered" style="font-size: 13px;">
                        <thead class="thead-light">
                            <tr>
                                <th>Staff</th>
                                <th>Login</th>
                                <th>Logout</th>
                                <th>Duration</th>
                                <th>IP / Location</th>
                                @if(in_array(auth()->user()->role, ['super_admin', 'manager']))
                                <th class="text-center">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessionLogs as $log)
                            <tr>
                                <td>
                                    <b>{{ $log->user->name ?? 'Unknown' }}</b><br>
                                    <small class="text-muted">{{ $log->user->branch->name ?? '' }}</small>
                                </td>
                                <td><span class="text-success font-weight-bold">{{ $log->login_at->format('d M Y, H:i') }}</span></td>
                                <td>
                                    @if($log->logout_at)
                                        {{ $log->logout_at->format('d M Y, H:i') }}
                                    @else
                                        <span class="badge badge-success">Active Session</span>
                                    @endif
                                </td>
                                <td><span class="badge badge-light border">{{ $log->formatted_duration }}</span></td>
                                <td>
                                    <small class="d-block">{{ $log->ip_address }}</small>
                                    @if($log->location && $log->location !== 'Unknown')
                                        <span class="badge badge-pill badge-info mt-1"><i class="fa fa-map-marker"></i> {{ $log->location }}</span>
                                    @endif
                                </td>
                                @if(in_array(auth()->user()->role, ['super_admin', 'manager']))
                                <td class="text-center">
                                    @if(!$log->logout_at)
                                        <form id="force-logout-form-{{ $log->id }}" action="{{ route('reports.force_logout', $log->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="button" class="btn btn-danger btn-sm end-session-btn" data-id="{{ $log->id }}" data-name="{{ $log->user->name ?? 'Unknown' }}">
                                                <i class="fa fa-power-off"></i> End
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small"><i class="fa fa-check-circle text-success"></i> Closed</span>
                                    @endif
                                </td>
                                @endif
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ in_array(auth()->user()->role, ['super_admin', 'manager']) ? 6 : 5 }}" class="text-center py-4 text-muted">No login sessions found for this period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($sessionLogs->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">Showing {{ $sessionLogs->firstItem() }}–{{ $sessionLogs->lastItem() }} of {{ $sessionLogs->total() }}</small>
                    {{ $sessionLogs->links('pagination::bootstrap-4') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });

        $('.end-session-btn').on('click', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');

            Swal.fire({
                title: 'End Session?',
                html: `Force close the active session for <strong>${name}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#940000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, End Session'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#force-logout-form-' + id).submit();
                }
            });
        });
    });
</script>
@endsection
