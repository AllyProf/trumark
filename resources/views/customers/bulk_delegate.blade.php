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
    .quick-transfer-box {
        background: linear-gradient(135deg, #f8f9fa 0%, #eef2ff 100%);
        border: 2px solid #940000;
        border-radius: 8px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        @if($inactiveLeadCount > 0)
            <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <i class="fa fa-exclamation-triangle mr-2"></i>
                    <strong>{{ $inactiveLeadCount }} lead(s)</strong> are still assigned to staff who have left or been deactivated.
                </div>
                <a href="{{ route('customers.bulk_delegate', ['current_officer_id' => 'inactive']) }}" class="btn btn-sm btn-warning mt-2 mt-md-0">
                    <i class="fa fa-filter"></i> Show &amp; Reassign
                </a>
            </div>
        @endif

        {{-- Quick Transfer: pick staff → pick new officer → done --}}
        <div class="tile mb-3">
            <div class="tile-body quick-transfer-box p-4">
                <h4 class="mb-1"><i class="fa fa-bolt text-danger"></i> Quick Transfer</h4>
                <p class="text-muted mb-3">Move <strong>all</strong> leads from one staff member to another — no manual selection needed.</p>
                <p class="small text-muted mb-3">
                    <i class="fa fa-info-circle"></i>
                    After transfer, leads appear on the new officer's customer list and dashboard counts immediately.
                    Past KPI points stay with the previous owner; new activity on those leads earns points for the new officer.
                </p>
                <form method="POST" action="{{ route('customers.process_bulk_delegate') }}" id="quickTransferForm">
                    @csrf
                    <div class="row align-items-end">
                        <div class="form-group col-md-4">
                            <label class="control-label font-weight-bold">From (current owner)</label>
                            <select name="transfer_all_from" id="transferFrom" class="form-control select2-quick" required>
                                <option value="">-- Select staff --</option>
                                @if($unassignedCount > 0)
                                    <option value="unassigned" data-count="{{ $unassignedCount }}">Unassigned ({{ $unassignedCount }} leads)</option>
                                @endif
                                @if($inactiveLeadCount > 0)
                                    <option value="inactive" data-count="{{ $inactiveLeadCount }}">Left / inactive staff ({{ $inactiveLeadCount }} leads)</option>
                                @endif
                                @foreach($currentOwners as $owner)
                                    <option value="{{ $owner->id }}" data-count="{{ $leadCounts[$owner->id] ?? 0 }}">
                                        {{ $owner->name }} ({{ $leadCounts[$owner->id] ?? 0 }} leads){{ !$owner->is_active ? ' — Left' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-1 text-center d-none d-md-block pb-2">
                            <i class="fa fa-long-arrow-right fa-2x text-muted"></i>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="control-label font-weight-bold">To (new owner)</label>
                            <select name="target_officer_id" class="form-control select2-quick" required>
                                <option value="">-- Select active officer --</option>
                                @foreach($activeOfficers as $officer)
                                    <option value="{{ $officer->id }}">
                                        {{ $officer->name }} ({{ $officer->branch->name ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <button type="submit" class="btn btn-danger btn-block btn-lg" id="quickTransferBtn" disabled>
                                <i class="fa fa-exchange"></i> <span id="quickTransferLabel">Transfer All Leads</span>
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-4 mb-0">
                            <label class="control-label small text-muted">Optional: limit to region</label>
                            <select name="transfer_region" class="form-control select2-quick">
                                <option value="">All regions</option>
                                @foreach($regions as $r)
                                    <option value="{{ $r }}">{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="tile">
            <div class="tile-title-w-btn">
                <h3 class="title">Review &amp; Select Manually</h3>
                <p class="mb-0 text-muted">{{ $customers->count() }} lead(s) shown</p>
            </div>
            <div class="tile-body">
                <form method="GET" action="{{ route('customers.bulk_delegate') }}" class="row mb-4" id="filterForm">
                    <div class="form-group col-md-4">
                        <label class="control-label">Show leads owned by</label>
                        <select name="current_officer_id" class="form-control select2-filter" onchange="this.form.submit()">
                            <option value="">All owners</option>
                            <option value="unassigned" {{ $currentOfficerId === 'unassigned' ? 'selected' : '' }}>
                                Unassigned ({{ $unassignedCount }})
                            </option>
                            @if($inactiveLeadCount > 0)
                                <option value="inactive" {{ $currentOfficerId === 'inactive' ? 'selected' : '' }}>
                                    Left / inactive staff ({{ $inactiveLeadCount }})
                                </option>
                            @endif
                            @foreach($currentOwners as $owner)
                                <option value="{{ $owner->id }}" {{ (string) $currentOfficerId === (string) $owner->id ? 'selected' : '' }}>
                                    {{ $owner->name }} ({{ $leadCounts[$owner->id] ?? 0 }}){{ !$owner->is_active ? ' — Left' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label class="control-label">Region</label>
                        <select name="region" class="form-control select2-filter" onchange="this.form.submit()">
                            <option value="">All Regions</option>
                            @foreach($regions as $r)
                                <option value="{{ $r }}" {{ $region == $r ? 'selected' : '' }}>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4 align-self-end">
                        <a href="{{ route('customers.bulk_delegate') }}" class="btn btn-secondary btn-block"><i class="fa fa-refresh"></i> Reset Filters</a>
                    </div>
                </form>

                @if($currentOfficerId)
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fa fa-check-square-o mr-1"></i>
                        All <strong>{{ $customers->count() }}</strong> visible lead(s) are auto-selected below.
                        Pick a new owner and click Delegate, or use Quick Transfer above.
                    </div>
                @endif

                <hr>

                <form method="POST" action="{{ route('customers.process_bulk_delegate') }}" id="delegateForm">
                    @csrf
                    <input type="hidden" name="return_region" value="{{ $region }}">
                    <input type="hidden" name="return_current_officer_id" value="{{ $currentOfficerId }}">

                    <div class="row bg-light p-3 rounded mb-4" style="background-color: #f8f9fa; border: 1px dashed #dee2e6;">
                        <div class="form-group col-md-8">
                            <label class="control-label fw-bold">Assign selected leads to:</label>
                            <select name="target_officer_id" class="form-control select2-target" required>
                                <option value="">-- Choose active officer --</option>
                                @foreach($activeOfficers as $officer)
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
                                                <span class="badge {{ $customer->salesOfficer->is_active ? 'badge-info' : 'badge-secondary' }}">
                                                    {{ $customer->salesOfficer->name }}
                                                </span>
                                                @if(!$customer->salesOfficer->is_active)
                                                    <span class="badge badge-warning">Left</span>
                                                @endif
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
        $('.select2-filter, .select2-target, .select2-quick').select2({ width: '100%' });

        const checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                document.querySelectorAll('.customer-checkbox').forEach(cb => cb.checked = this.checked);
                toggleSubmitButton();
            });
        }

        document.querySelectorAll('.customer-checkbox').forEach(cb => {
            cb.addEventListener('change', toggleSubmitButton);
        });

        @if($currentOfficerId && $customers->isNotEmpty())
            if (checkAll) {
                checkAll.checked = true;
                checkAll.dispatchEvent(new Event('change'));
            }
        @endif

        function toggleSubmitButton() {
            const checkedCount = document.querySelectorAll('.customer-checkbox:checked').length;
            const btn = document.getElementById('submitBtn');
            btn.disabled = checkedCount === 0;
            btn.innerHTML = checkedCount > 0
                ? `<i class="fa fa-share"></i> Delegate ${checkedCount} Lead${checkedCount === 1 ? '' : 's'}`
                : `<i class="fa fa-share"></i> Delegate Selected Leads`;
        }

        const transferFrom = document.getElementById('transferFrom');
        const quickBtn = document.getElementById('quickTransferBtn');
        const quickLabel = document.getElementById('quickTransferLabel');

        function updateQuickTransferBtn() {
            const selected = transferFrom.options[transferFrom.selectedIndex];
            const count = selected?.dataset?.count || (selected?.text.match(/\((\d+) leads?\)/)?.[1]) || '';
            const hasFrom = transferFrom.value !== '';
            quickBtn.disabled = !hasFrom;
            if (hasFrom && count) {
                quickLabel.textContent = `Transfer All ${count} Leads`;
            } else if (hasFrom) {
                quickLabel.textContent = 'Transfer All Leads';
            } else {
                quickLabel.textContent = 'Transfer All Leads';
            }
        }

        transferFrom.addEventListener('change', updateQuickTransferBtn);
        $('#transferFrom').on('select2:select select2:clear', updateQuickTransferBtn);
        updateQuickTransferBtn();

        function getSelectedOfficerName(selectEl) {
            const opt = selectEl.options[selectEl.selectedIndex];
            return opt ? opt.text.split(' (')[0] : '';
        }

        document.getElementById('quickTransferForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const fromOpt = transferFrom.options[transferFrom.selectedIndex];
            const fromName = fromOpt.text.split(' (')[0];
            const count = fromOpt.dataset.count || fromOpt.text.match(/\((\d+)/)?.[1] || 'all';
            const toSelect = form.querySelector('[name="target_officer_id"]');
            const toName = getSelectedOfficerName(toSelect) || 'the selected officer';

            Swal.fire({
                title: 'Confirm Transfer',
                html: `Transfer <strong>${count}</strong> lead(s) from<br><strong>${fromName}</strong><br>to<br><strong>${toName}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#940000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-exchange"></i> Yes, Transfer',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Transferring...',
                        text: 'Please wait while leads are reassigned.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    form.submit();
                }
            });
        });

        document.getElementById('delegateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const checkedCount = document.querySelectorAll('.customer-checkbox:checked').length;
            const toSelect = form.querySelector('[name="target_officer_id"]');
            const toName = getSelectedOfficerName(toSelect) || 'the selected officer';

            if (checkedCount === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No leads selected',
                    text: 'Please select at least one customer to delegate.',
                    confirmButtonColor: '#940000'
                });
                return;
            }

            Swal.fire({
                title: 'Confirm Delegation',
                html: `Delegate <strong>${checkedCount}</strong> lead(s) to<br><strong>${toName}</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#940000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-share"></i> Yes, Delegate',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Delegating...',
                        text: 'Please wait while leads are reassigned.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    form.submit();
                }
            });
        });
    });
</script>
@endsection
