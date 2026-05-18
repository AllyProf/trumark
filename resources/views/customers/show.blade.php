@extends('layouts.vali')

@section('title', 'Lead Summary: ' . $customer->name)

@section('page_icon', 'fa-user')

@section('subtitle')
Detailed profile and sales history for {{ $customer->name }}
@endsection

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="tile p-0">
            <div class="text-center p-4 bg-primary text-white" style="border-radius: 4px 4px 0 0;">
                <div class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; font-size: 32px; font-weight: bold;">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <h4 class="mb-1">{{ $customer->name }}</h4>
                <p class="mb-0 small text-white-50">{{ $customer->type }}</p>
            </div>
            <div class="p-3">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Status</span>
                        <span class="badge badge-primary">{{ $customer->status }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Stage</span>
                        <span class="badge badge-info">{{ $customer->buying_stage }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Pipeline Value</span>
                        <b class="text-primary">{{ number_format($customer->estimated_monthly_value) }} TZS</b>
                    </li>
                </ul>
                <div class="mt-3">
                    @if($customer->buying_stage === 'Closed Won')
                        <button class="btn btn-success btn-block btn-sm mb-3" data-toggle="modal" data-target="#newOrderModal">
                            <i class="fa fa-refresh mr-1"></i> Start New Order
                        </button>
                    @endif
                    
                    <button class="btn btn-primary btn-block btn-sm mb-3" data-toggle="modal" data-target="#updateModal">
                        <i class="fa fa-pencil-square-o mr-1"></i> Update Stage
                    </button>
                    
                    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-secondary btn-block btn-sm mb-3">
                        <i class="fa fa-edit mr-1"></i> Edit Profile
                    </a>

                    @if(!$customer->survey_uuid)
                        @php
                            $customer->survey_uuid = (string) \Illuminate\Support\Str::uuid();
                            $customer->save();
                        @endphp
                    @endif

                    @php
                        $surveyUrl = 'https://trumark.mauzolink.co.tz/feedback/' . $customer->survey_uuid;
                        $shareText = "Habari " . $customer->name . ", asante kwa kuchagua TRUMARK. Tafadhali tufahamishe jinsi ulivyohudumiwa hapa: " . $surveyUrl . " . Asante!";
                        $whatsappUrl = "https://api.whatsapp.com/send?phone=" . preg_replace('/[^0-9]/', '', $customer->phone) . "&text=" . rawurlencode($shareText);
                    @endphp

                    <div class="card p-2 bg-light border mt-4 mb-2">
                        <div class="text-center font-weight-bold text-uppercase small mb-2 text-secondary" style="letter-spacing: 0.5px; font-size: 11px;">
                            <i class="fa fa-check-square-o mr-1"></i> Customer Feedback Survey
                        </div>
                        
                        <form action="{{ route('customers.send_survey', $customer->id) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="btn btn-info btn-block btn-sm">
                                <i class="fa fa-paper-plane mr-1"></i> Send Automated Link
                            </button>
                        </form>

                        <div class="row no-gutters">
                            <div class="col-6 pr-1">
                                <button type="button" class="btn btn-outline-info btn-block btn-sm" onclick="copySurveyLink('{{ $surveyUrl }}')">
                                    <i class="fa fa-copy mr-1"></i> Copy Link
                                </button>
                            </div>
                            <div class="col-6 pl-1">
                                <a href="{{ $whatsappUrl }}" target="_blank" class="btn btn-outline-success btn-block btn-sm">
                                    <i class="fa fa-whatsapp mr-1"></i> WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="tile">
            <h3 class="tile-title">Contact Information</h3>
            <div class="row">
                <div class="col-md-6">
                    <p><b>Contact Person:</b> {{ $customer->contact_person ?? 'N/A' }}</p>
                    <p><b>Position:</b> {{ $customer->position ?? 'N/A' }}</p>
                    <p><b>Phone:</b> {{ $customer->phone }}</p>
                </div>
                <div class="col-md-6">
                    <p><b>Alt. Phone:</b> {{ $customer->alternative_phone ?? 'N/A' }}</p>
                    <p><b>Email:</b> {{ $customer->email ?? 'N/A' }}</p>
                    <p><b>Source:</b> {{ $customer->source ?? 'N/A' }}</p>
                </div>
            </div>
            <hr>
            <h3 class="tile-title">Location Details</h3>
            <div class="row">
                <div class="col-md-6">
                    <p><b>Region/District:</b> {{ $customer->region ?? 'N/A' }}, {{ $customer->district ?? 'N/A' }}</p>
                    <p><b>Ward/Area:</b> {{ $customer->ward ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <p><b>Physical Address:</b> {{ $customer->address ?? 'N/A' }}</p>
                    <p><b>Landmark:</b> {{ $customer->landmark ?? 'N/A' }}</p>
                </div>
            </div>
        </div>

        <div class="tile">
            <h3 class="tile-title">Internal Notes & Requirements</h3>
            <div class="row">
                <div class="col-md-12">
                    <p><b>Products / Services Required:</b></p>
                    <div class="mb-3">
                        @if(!empty($customer->requirements) && is_array($customer->requirements))
                            @foreach($customer->requirements as $req)
                                <span class="badge badge-info p-2 mr-1 mb-1" style="font-size: 12px;"><i class="fa fa-check mr-1"></i> {{ $req }}</span>
                            @endforeach
                        @else
                            <span class="text-muted small">No specific products selected.</span>
                        @endif
                    </div>
                    
                    <p><b>Additional Details:</b></p>
                    <div class="p-3 bg-light rounded border mb-3">
                        {{ $customer->detailed_requirement ?? 'No specific details provided.' }}
                    </div>
                    
                    <p><b>Internal Remarks:</b></p>
                    <div class="p-3 bg-light rounded border" style="border-left: 4px solid #940000 !important;">
                        {{ $customer->notes ?? 'No internal notes found.' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="tile">
            <h3 class="tile-title">Communication Log</h3>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Sent By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->smsLogs()->latest()->get() as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d M Y, H:i') }}</td>
                            <td>{{ $log->message }}</td>
                            <td><span class="badge badge-success">Sent</span></td>
                            <td>{{ $log->sender->name ?? 'System' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">No communication recorded.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tile">
            <h3 class="tile-title">Sales History</h3>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount (TZS)</th>
                            <th>Status</th>
                            <th>Officer</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->sales()->latest()->get() as $sale)
                        <tr>
                            <td>{{ $sale->created_at->format('d M Y') }}</td>
                            <td><b class="text-success">{{ number_format($sale->total_amount) }}</b></td>
                            <td><span class="badge badge-success">{{ $sale->payment_status }}</span></td>
                            <td>{{ $sale->user->name ?? 'Unknown' }}</td>
                            <td><small>{{ $sale->notes }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No past sales recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="updateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Update Stage</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('customers.quick_update', $customer->id) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Buying Stage</label>
                        <select name="buying_stage" class="form-control" required>
                            <option value="Inquiry" {{ $customer->buying_stage == 'Inquiry' ? 'selected' : '' }}>Inquiry</option>
                            <option value="Quotation Sent" {{ $customer->buying_stage == 'Quotation Sent' ? 'selected' : '' }}>Quotation Sent</option>
                            <option value="Negotiation" {{ $customer->buying_stage == 'Negotiation' ? 'selected' : '' }}>Negotiation</option>
                            <option value="Order Confirmed" {{ $customer->buying_stage == 'Order Confirmed' ? 'selected' : '' }}>Order Confirmed</option>
                            <option value="Delivered" {{ $customer->buying_stage == 'Delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="Payment Pending" {{ $customer->buying_stage == 'Payment Pending' ? 'selected' : '' }}>Payment Pending</option>
                            <option value="Closed Won" {{ $customer->buying_stage == 'Closed Won' ? 'selected' : '' }}>Closed Won</option>
                            <option value="Closed Lost" {{ $customer->buying_stage == 'Closed Lost' ? 'selected' : '' }}>Closed Lost</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Order Modal -->
<div class="modal fade" id="newOrderModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fa fa-refresh"></i> Start Repeat Sale</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('customers.new_transaction', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning small">
                        <i class="fa fa-info-circle"></i> This will archive the current Pipeline Value ({{ number_format($customer->estimated_monthly_value) }} TZS) into the <b>Sales History</b> and reset the customer's stage to <b>Inquiry</b> so you can start tracking the new order.
                    </div>
                    <div class="form-group">
                        <label>New Estimated Value (TZS) <span class="text-danger">*</span></label>
                        <input type="number" name="new_value" class="form-control" required min="0" placeholder="e.g. 5000000">
                    </div>
                    <div class="form-group">
                        <label>Select Products / Services <span class="text-danger">*</span></label>
                        <select name="requirements[]" class="form-control select2" multiple="multiple" required style="width: 100%;">
                            <option value="School Books">School Books</option>
                            <option value="Exercise Books">Exercise Books</option>
                            <option value="Office Stationery">Office Stationery</option>
                            <option value="Printing Services">Printing Services</option>
                            <option value="School Supply">School Supply</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Additional Details (Optional)</label>
                        <textarea name="detailed_requirement" class="form-control" rows="2" placeholder="Any specific details?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Start New Order</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Select requirements...",
            allowClear: true
        });
    });

    function copySurveyLink(url) {
        navigator.clipboard.writeText(url).then(function() {
            if (typeof swal === 'function') {
                swal({
                    title: "Link Copied!",
                    text: "Survey link has been copied to your clipboard.",
                    type: "success",
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert("Survey link copied to clipboard successfully!");
            }
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
        });
    }
</script>
@endsection
