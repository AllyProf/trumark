@extends('layouts.vali')

@section('title', 'Bulk Lead Import')

@section('page_icon', 'fa-upload')

@section('subtitle')
Upload leads from CSV files to quickly populate your database
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="tile">
            <h3 class="tile-title border-bottom pb-2">Import Customers / Leads</h3>
            
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <h5 class="font-weight-bold"><i class="fa fa-info-circle mr-2"></i> Instructions:</h5>
                <ol class="mb-0 small">
                    <li>Download the <strong><a href="{{ route('customers.download_template') }}" class="text-primary">Professional Excel Template Here</a></strong>.</li>
                    <li>Open it in Excel and fill in your customer data.</li>
                    <li>Save the file and upload it below.</li>
                </ol>
                <p class="mt-2 mb-0 small text-danger font-weight-bold">* Name and Phone are MANDATORY for every row.</p>
            </div>

            <form action="{{ route('customers.process_import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                <div class="form-group mb-4">
                    <label class="font-weight-bold">Select Excel or CSV File</label>
                    <input type="file" name="import_file" class="form-control-file p-3 border rounded bg-light" accept=".xlsx,.xls,.csv" required>
                    <small class="text-muted d-block mt-2">Maximum file size: 5MB (.xlsx, .xls, .csv)</small>
                </div>

                <div class="tile-footer bg-light p-3 border-top d-flex justify-content-between">
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary shadow-sm">
                        <i class="fa fa-arrow-left"></i> Back to All Leads
                    </a>
                    <button type="submit" class="btn btn-primary shadow-sm font-weight-bold px-4" id="submitBtn">
                        <i class="fa fa-cloud-upload"></i> Start Bulk Upload
                    </button>
                </div>
            </form>

            {{-- Hidden Template Generator --}}
            <a id="downloadAnchor" style="display:none"></a>
        </div>
    </div>

    {{-- Rules Card --}}
    <div class="col-md-4">
        <div class="tile">
            <h5 class="tile-title border-bottom pb-2">KPI Point Rewards</h5>
            <div class="list-group list-group-flush small">
                <div class="list-group-item d-flex justify-content-between align-items-center bg-light">
                    <span>Base Registration</span>
                    <span class="badge badge-success">+5 Pts</span>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center bg-light">
                    <span>Organization Bonus (School/Company)</span>
                    <span class="badge badge-success">+5 Pts</span>
                </div>
            </div>
            <p class="text-muted mt-3 small">Points are awarded automatically per successful row imported.</p>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#importForm').on('submit', function() {
            const btn = $('#submitBtn');
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing Upload...');
        });
    });
</script>
@endsection
