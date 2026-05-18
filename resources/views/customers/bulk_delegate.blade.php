@extends('layouts.vali')

@section('title', 'Quick Delegation')
@section('page_icon', 'fa-users')
@section('subtitle', 'Bulk transfer leads between sales officers')

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container--default .select2-selection--single {
        height: 38px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding-top: 5px;
    }
    .delegated-row {
        background-color: #f0fff4 !important;
        transition: background-color 0.5s ease;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">Filter & Delegate Leads</h3>
            </div>
            <div class="tile-body">
                <form method="GET" action="{{ route('customers.bulk_delegate') }}" class="row mb-4">
                    <div class="form-group col-md-6">
                        <label class="control-label">Region</label>
                        <select name="region" class="form-control select2" onchange="this.form.submit()">
                            <option value="">All Regions</option>
                            @foreach($regions as $r)
                                <option value="{{ $r }}" {{ request('region') == $r ? 'selected' : '' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6 align-self-end">
                        <a href="{{ route('customers.bulk_delegate') }}" class="btn btn-secondary btn-block"><i class="fa fa-refresh"></i> Reset Filters</a>
                    </div>
                </form>

                <hr>

                <form method="POST" action="{{ route('customers.process_bulk_delegate') }}" id="delegateForm">
                    @csrf
                    <div class="row bg-light p-3 rounded mb-4" style="background-color: #f8f9fa; border: 1px dashed #dee2e6;">
                        <div class="form-group col-md-8">
                            <label class="control-label fw-bold">Select New Sales Officer to Assign To:</label>
                            <select name="target_officer_id" class="form-control select2" required>
                                <option value="">-- Choose Officer --</option>
                                @foreach($officers as $officer)
                                    <option value="{{ $officer->id }}">
                                        {{ $officer->name }} ({{ $officer->branch->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4 align-self-end">
                            <button type="submit" class="btn btn-primary btn-block" id="submitBtn" disabled>
                                <i class="fa fa-share"></i> Delegate Selected Leads
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 40px;">
                                        <div class="animated-checkbox">
                                            <label>
                                                <input type="checkbox" id="checkAll"><span class="label-text"></span>
                                            </label>
                                        </div>
                                    </th>
                                    <th>Customer Name</th>
                                    <th>Current Owner</th>
                                    <th>Region/District</th>
                                    <th>Status</th>
                                    <th>Registered At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customers as $customer)
                                    <tr id="row-{{ $customer->id }}" class="{{ session('delegated_ids') && in_array($customer->id, session('delegated_ids')) ? 'delegated-row' : '' }}">
                                        <td>
                                            <div class="animated-checkbox">
                                                <label>
                                                    <input type="checkbox" class="customer-checkbox" name="customer_ids[]" value="{{ $customer->id }}"><span class="label-text"></span>
                                                </label>
                                            </div>
                                        </td>
                                        <td>{{ $customer->name }}</td>
                                        <td>
                                            @if($customer->salesOfficer)
                                                <span class="badge badge-info">{{ $customer->salesOfficer->name }}</span>
                                            @else
                                                <span class="badge badge-warning">Unassigned</span>
                                            @endif
                                            @if(session('delegated_ids') && in_array($customer->id, session('delegated_ids')))
                                                <br><small class="text-success font-weight-bold"><i class="fa fa-check"></i> Just Delegated</small>
                                            @endif
                                        </td>
                                        <td>{{ $customer->region ?? 'N/A' }} / {{ $customer->district ?? 'N/A' }}</td>
                                        <td><span class="badge badge-primary">{{ $customer->status }}</span></td>
                                        <td>{{ $customer->created_at->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">No leads found matching your filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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

        document.getElementById('checkAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.customer-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
            toggleSubmitButton();
        });

        document.querySelectorAll('.customer-checkbox').forEach(cb => {
            cb.addEventListener('change', toggleSubmitButton);
        });

        function toggleSubmitButton() {
            const checkedCount = document.querySelectorAll('.customer-checkbox:checked').length;
            const btn = document.getElementById('submitBtn');
            btn.disabled = checkedCount === 0;
            btn.innerHTML = checkedCount > 0 
                ? `<i class="fa fa-share"></i> Delegate ${checkedCount} Leads`
                : `<i class="fa fa-share"></i> Delegate Selected Leads`;
        }
    });
</script>
@endsection
