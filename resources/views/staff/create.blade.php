@extends('layouts.vali')

@section('title', 'Register New Staff Member')

@section('styles')
<!-- Select2 CSS -->
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
                <h5>Staff Registration Form</h5>
                <p class="text-muted m-b-0">Fill in the details below. The password will be <strong>automatically generated</strong> and sent to the staff member via SMS.</p>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.store') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Enter Staff Full Name" required>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="staff@trumark.co.tz" required>
                                @error('email')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" name="phone" class="form-control" value="+255" required>
                                <small class="text-muted">Password will be sent to this number.</small>
                            </div>

                             <div class="form-group">
                                <label class="font-weight-bold">Assign Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-control select2" required>
                                    <option value="">Select a Role...</option>
                                    <option value="manager">Manager (Warehouse / Operations)</option>
                                    <option value="sales_officer">Sales Officer (Field Agent)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Assign Branch</label>
                                <select name="branch_id" class="form-control select2">
                                    <option value="">Global (No Branch)</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Admins are usually Global. Managers/Officers should be assigned to a branch.</small>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3">
                        <i class="fa fa-info mr-2"></i> 
                        <strong>Password Rule:</strong> The default password will be the staff member's <strong>LAST NAME in ALL CAPS</strong>. A welcome SMS will be sent automatically.
                    </div>

                    <div class="text-right mt-4">
                        <a href="{{ route('staff.index') }}" class="btn btn-secondary mr-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-5">
                            <i class="fa fa-user-plus mr-2"></i> Register Staff & Send SMS
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });
    });
</script>
@endsection
