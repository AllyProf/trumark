@extends('layouts.vali')

@section('title', 'Manage Staff')

@section('page_icon', 'fa-users')

@section('subtitle')
Register and manage system users, access roles, and branch assignments
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="tile-title mb-0">Staff Members</h3>
                <a href="{{ route('staff.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Register New Staff</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="staffTable">
                    <thead>
                        <tr>
                            <th>Staff Member</th>
                            <th>Email</th>
                            <th>Role & Status</th>
                            <th>Branch</th>
                            <th>Joined Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staff as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($user->avatar)
                                        <img src="{{ asset('storage/' . $user->avatar) }}" 
                                             alt="{{ $user->name }}" 
                                             class="mr-3 rounded-circle shadow-sm" 
                                             style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #940000; transition: transform 0.2s;"
                                             onmouseover="this.style.transform='scale(1.2)'" 
                                             onmouseout="this.style.transform='scale(1)'">
                                    @else
                                        <div class="mr-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" 
                                             style="width: 45px; height: 45px; font-weight: bold; border: 2px solid #940000;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <b>{{ $user->name }}</b><br>
                                        <small class="text-muted">ID: #{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @php
                                    $roleClass = $user->role == 'super_admin' ? 'badge-danger' : 'badge-info';
                                @endphp
                                <span class="badge {{ $roleClass }}">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span><br>
                                @if($user->is_active)
                                    <span class="text-success" style="font-size: 11px;"><i class="fa fa-check-circle"></i> Active</span>
                                @else
                                    <span class="text-muted" style="font-size: 11px;"><i class="fa fa-times-circle"></i> Inactive</span>
                                @endif
                            </td>

                             <td><b>{{ $user->branch->name ?? 'Global' }}</b></td>
                             <td>{{ $user->created_at->format('d M Y') }}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('staff.edit', $user->id) }}" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-warning btn-sm reset-password" data-id="{{ $user->id }}" data-name="{{ $user->name }}" title="Reset Password">
                                        <i class="fa fa-lock"></i>
                                    </button>
                                    @if($user->is_active)
                                        <button type="button" class="btn btn-secondary btn-sm toggle-status" data-id="{{ $user->id }}" data-action="deactivate" title="Deactivate">
                                            <i class="fa fa-ban"></i>
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-success btn-sm toggle-status" data-id="{{ $user->id }}" data-action="activate" title="Activate">
                                            <i class="fa fa-play"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-info btn-sm kpi-adjust" data-id="{{ $user->id }}" data-name="{{ $user->name }}" title="Manual KPI Adjustment">
                                        <i class="fa fa-trophy"></i>
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm delete-staff" data-id="{{ $user->id }}" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>

                                {{-- KPI Adjustment Modal --}}
                                <div class="modal fade" id="kpiModal-{{ $user->id }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title"><i class="fa fa-trophy mr-2"></i> KPI Adjustment: {{ $user->name }}</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                                            </div>
                                            <form action="{{ route('staff.adjust_kpi', $user->id) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Select Activity</label>
                                                        <select name="activity_code" class="form-control" required>
                                                            <optgroup label="Positive Achievements">
                                                                <option value="PHYSICAL_VISIT">Physical Customer Visit (+5)</option>
                                                                <option value="POSITIVE_FEEDBACK">Positive Customer Feedback (+5)</option>
                                                                <option value="MEETING_ON_TIME">Attend Sales Meeting on Time (+2)</option>
                                                                <option value="WEEKLY_REPORT">Submit Weekly Sales Report (+5)</option>
                                                                <option value="RECOVER_INACTIVE">Recover Inactive Customer (+12)</option>
                                                                <option value="UPSELLING">Up-selling Additional Products (+6)</option>
                                                                <option value="REFERRAL_EXISTING">Referral from Existing Customer (+8)</option>
                                                            </optgroup>
                                                            <optgroup label="Discipline (Deductions)">
                                                                <option value="MISSED_MEETING">Missing Sales Meeting (-5)</option>
                                                                <option value="CUSTOMER_COMPLAINT">Customer Complaint (-10)</option>
                                                                <option value="FAKE_DATA">Fake or Incomplete Data (-15)</option>
                                                            </optgroup>
                                                        </select>
                                                    </div>
                                                    <div class="form-group">
                                                        <label class="font-weight-bold">Additional Notes</label>
                                                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional details..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-info">Apply Adjustment</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <form id="reset-form-{{ $user->id }}" action="{{ route('staff.reset_password', $user->id) }}" method="POST" style="display: none;">@csrf</form>
                                <form id="toggle-form-{{ $user->id }}" action="{{ route('staff.toggle_status', $user->id) }}" method="POST" style="display: none;">@csrf</form>
                                <form id="delete-form-{{ $user->id }}" action="{{ route('staff.destroy', $user->id) }}" method="POST" style="display: none;">@csrf @method('DELETE')</form>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        $('#staffTable').DataTable({
            "info": false,
            "pageLength": 10,
            "retrieve": true,
            "destroy": true
        });

        // Delete Staff
        $('.delete-staff').on('click', function() {
            let id = $(this).data('id');
            Swal.fire({
                title: 'Are you sure?',
                text: "This staff member will be permanently removed!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#940000',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#delete-form-' + id).submit();
                }
            });
        });

        // Reset Password
        $('.reset-password').on('click', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            Swal.fire({
                title: 'Reset Password?',
                text: "A new random password will be generated and sent to " + name + " via SMS.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reset & Send SMS'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#reset-form-' + id).submit();
                }
            });
        });

        // KPI Adjustment
        $('.kpi-adjust').on('click', function() {
            let id = $(this).data('id');
            $('#kpiModal-' + id).modal('show');
        });

        // Toggle Status
        $('.toggle-status').on('click', function() {
            let id = $(this).data('id');
            let action = $(this).data('action');
            
            Swal.fire({
                title: action.charAt(0).toUpperCase() + action.slice(1) + ' Account?',
                text: "Are you sure you want to " + action + " this staff account?",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#940000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, ' + action + ' it'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#toggle-form-' + id).submit();
                }
            });
        });
    });
</script>
@endsection
