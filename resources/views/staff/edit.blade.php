@extends('layouts.vali')

@section('title', 'Edit Staff Member')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 45px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding-top: 8px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5>Edit Staff: {{ $staff->name }}</h5>
                <p class="text-muted m-b-0">Update staff details below.</p>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.update', $staff->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ $staff->name }}" required>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ $staff->email }}" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" value="{{ $staff->phone }}" required>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Assign Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-control select2" required>
                                    <option value="super_admin" {{ $staff->role == 'super_admin' ? 'selected' : '' }}>Super Admin (Full Access)</option>
                                    <option value="manager" {{ $staff->role == 'manager' ? 'selected' : '' }}>Manager (Warehouse / Operations)</option>
                                    <option value="sales_officer" {{ $staff->role == 'sales_officer' ? 'selected' : '' }}>Sales Officer (Limited Access)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Assign Branch</label>
                                <select name="branch_id" class="form-control select2">
                                    <option value="">Global (No Branch)</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $staff->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block">Admins are usually Global. Managers/Officers should be assigned to a branch.</small>
                                <small class="text-danger font-weight-bold"><i class="fa fa-info-circle"></i> Note: Changing branch will automatically transfer all assigned customers to the new branch.</small>
                            </div>
                        </div>
                    </div>


                    <div class="text-right mt-4">
                        <a href="{{ route('staff.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="fa fa-save mr-2"></i> Update Staff Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });
    });
</script>
@endsection
