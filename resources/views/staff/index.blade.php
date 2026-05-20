@extends('layouts.vali')

@section('title', 'Manage Staff')

@section('page_icon', 'fa-users')

@section('subtitle')
Register and manage system users, access roles, and branch assignments
@endsection

@section('styles')
<style>
    .tile {
        border-radius: 12px !important;
        box-shadow: 0 4px 6px rgba(0,0,0,0.04) !important;
        border: 0 !important;
        margin-bottom: 25px;
    }
    
    .table td, .table th {
        vertical-align: middle !important;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(148, 0, 0, 0.03) !important;
    }
    
    /* Card list styling */
    .card {
        transition: all 0.2s ease;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.06) !important;
    }
    
    @media (max-width: 767px) {
        .tile-title {
            font-size: 16px !important;
        }
        /* Mobile header and button layout */
        .tile .d-flex.justify-content-between {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 12px;
        }
        .tile .d-flex.justify-content-between a {
            width: 100% !important;
            display: block !important;
            text-align: center;
        }
        
        /* Premium Native Mobile App Card Spacing Canvas */
        .mobile-card-container {
            background-color: #f4f6f9 !important;
            padding: 15px 12px !important;
            border-radius: 12px !important;
            margin-left: -15px !important;
            margin-right: -15px !important;
            margin-bottom: -15px !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
        }
        
        .mobile-card-container .card {
            margin-bottom: 16px !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03) !important;
        }
        
        .mobile-card-container .card:last-child {
            margin-bottom: 0 !important;
        }
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 col-12">
        <div class="tile border-0 shadow-sm p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h3 class="tile-title mb-0 font-weight-bold" style="font-size: 18px;"><i class="fa fa-users text-primary mr-2"></i> STAFF MEMBERS</h3>
                <a href="{{ route('staff.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus mr-1"></i> Register New Staff</a>
            </div>
            
            <div class="p-4 bg-white">
                {{-- Desktop View (Traditional Table) --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover table-bordered" id="staffTable">
                        <thead class="bg-light text-uppercase" style="font-size: 11px; letter-spacing: 1px;">
                            <tr>
                                <th>Staff Member</th>
                                <th>Email</th>
                                <th>Role & Status</th>
                                <th>Branch</th>
                                <th>Joined Date</th>
                                <th class="text-center" style="width: 180px;">Actions</th>
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



                                    <form id="reset-form-{{ $user->id }}" action="{{ route('staff.reset_password', $user->id) }}" method="POST" style="display: none;">@csrf</form>
                                    <form id="toggle-form-{{ $user->id }}" action="{{ route('staff.toggle_status', $user->id) }}" method="POST" style="display: none;">@csrf</form>
                                    <form id="delete-form-{{ $user->id }}" action="{{ route('staff.destroy', $user->id) }}" method="POST" style="display: none;">@csrf @method('DELETE')</form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Mobile View (Premium Card List) --}}
                <div class="d-block d-md-none mobile-card-container">
                    @forelse($staff as $user)
                    <div class="card shadow-sm mb-3 border-0" style="border-radius: 12px; background: #fff; border-left: 5px solid {{ $user->is_active ? '#28a745' : '#6c757d' }};">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="mr-3">
                                    @if($user->avatar)
                                        <img src="{{ asset('storage/' . $user->avatar) }}" 
                                             alt="{{ $user->name }}" 
                                             class="rounded-circle shadow-sm" 
                                             style="width: 45px; height: 45px; object-fit: cover; border: 2px solid #940000;">
                                    @else
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" 
                                             style="width: 45px; height: 45px; font-weight: bold; border: 2px solid #940000; font-size: 14px;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <b class="d-block text-dark" style="font-size: 15px; line-height: 1.2;">{{ $user->name }}</b>
                                    <small class="text-muted">ID: #{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</small>
                                </div>
                                <div class="text-right">
                                    @php
                                        $roleClass = $user->role == 'super_admin' ? 'badge-danger' : 'badge-info';
                                    @endphp
                                    <span class="badge {{ $roleClass }} text-uppercase mb-1" style="font-size: 8px; padding: 4px 8px; border-radius: 20px;">
                                        {{ str_replace('_', ' ', $user->role) }}
                                    </span>
                                    <span class="d-block text-muted" style="font-size: 10px;">{{ $user->created_at->format('d M Y') }}</span>
                                </div>
                            </div>
                            
                            <div class="bg-light p-2 rounded mb-3" style="font-size: 12px; border: 1px solid #eee;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted"><i class="fa fa-envelope mr-1"></i> Email:</span>
                                    <span class="font-weight-bold text-dark text-truncate ml-2" style="max-width: 180px;">{{ $user->email }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted"><i class="fa fa-map-marker mr-1"></i> Branch:</span>
                                    <span class="font-weight-bold text-dark">{{ $user->branch->name ?? 'Global' }}</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <div>
                                    @if($user->is_active)
                                        <span class="badge badge-success shadow-xs" style="font-size: 9px; padding: 4px 8px;"><i class="fa fa-check-circle mr-1"></i> ACTIVE</span>
                                    @else
                                        <span class="badge badge-secondary shadow-xs" style="font-size: 9px; padding: 4px 8px;"><i class="fa fa-times-circle mr-1"></i> INACTIVE</span>
                                    @endif
                                </div>
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
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted bg-light rounded" style="border: 2px dashed #ddd;">
                        <i class="fa fa-users fa-2x mb-2 text-muted"></i>
                        <p class="mb-0 font-weight-bold">No staff records found.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

{{-- KPI Adjustment Modals rendered cleanly at page-level to avoid stacking context & transform bugs --}}
@foreach($staff as $user)
<div class="modal fade text-left" id="kpiModal-{{ $user->id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-info text-white border-0">
                <h5 class="modal-title font-weight-bold"><i class="fa fa-trophy mr-2"></i> KPI Adjustment: {{ $user->name }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
            </div>
            <form action="{{ route('staff.adjust_kpi', $user->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Select Activity</label>
                        <select name="activity_code" class="form-control" onchange="toggleCustomPoints(this, {{ $user->id }})" required>
                            <option value="">-- Select Activity --</option>
                            <optgroup label="Custom">
                                <option value="CUSTOM">Custom Point Adjustment (Type Points)</option>
                            </optgroup>
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
                    <div class="form-group custom-points-div-{{ $user->id }}" style="display: none;">
                        <label class="font-weight-bold">Custom Points</label>
                        <input type="number" name="points" id="points-{{ $user->id }}" class="form-control" placeholder="e.g. 10 or -5" step="1">
                        <small class="text-muted">Use a negative number to deduct points (e.g. -10)</small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Additional Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info font-weight-bold shadow-xs">Apply Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
    function toggleCustomPoints(select, userId) {
        if (select.value === 'CUSTOM') {
            $('.custom-points-div-' + userId).slideDown();
            $('#points-' + userId).prop('required', true);
        } else {
            $('.custom-points-div-' + userId).slideUp();
            $('#points-' + userId).prop('required', false).val('');
        }
    }

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
