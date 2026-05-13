@extends('layouts.vali')

@section('title', 'Staff Attendance & Usage')

@section('page_icon')
<i class="fa fa-clock-o"></i>
@endsection

@section('subtitle')
Detailed logs of system entry, exit, and session duration
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile border-0 shadow-sm p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom">
                <h5 class="mb-0 font-weight-bold"><i class="fa fa-calendar-check-o text-primary mr-2"></i> ATTENDANCE & USAGE LOG</h5>
            </div>
            
            <div class="p-4 bg-white">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="bg-light text-uppercase" style="font-size: 11px; letter-spacing: 1px;">
                            <tr>
                                <th>Staff Member</th>
                                <th>Login Time</th>
                                <th>Logout Time</th>
                                <th>Duration</th>
                                <th>IP Address</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>
                                    <b>{{ $log->user->name ?? 'Unknown' }}</b><br>
                                    <small class="text-muted text-uppercase">{{ $log->user->role ?? '' }}</small>
                                </td>
                                <td>
                                    <span class="text-success font-weight-bold">{{ $log->login_at->format('d M, Y') }}</span><br>
                                    <span class="badge badge-light border"><i class="fa fa-sign-in"></i> {{ $log->login_at->format('H:i:s') }}</span>
                                </td>
                                <td>
                                    @if($log->logout_at)
                                        <span class="text-muted"><i class="fa fa-sign-out"></i> {{ $log->logout_at->format('H:i:s') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->logout_at)
                                        <span class="badge badge-info p-2">{{ $log->duration_minutes }} mins</span>
                                    @else
                                        <span class="text-muted small">In Session...</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $log->ip_address }}</small></td>
                                <td>
                                    @if(!$log->logout_at)
                                        <span class="badge badge-success"><i class="fa fa-circle mr-1"></i> Online Now</span>
                                    @else
                                        <span class="badge badge-secondary">Session Ended</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">No attendance logs available.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Bootstrap Pagination --}}
                @if($logs->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3 px-1">
                    <small class="text-muted">
                        Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }}
                        of {{ $logs->total() }} records
                    </small>
                    {{ $logs->links('pagination::bootstrap-4') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
