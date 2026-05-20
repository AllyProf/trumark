@extends('layouts.vali')

@section('title', 'Customer Database')

@section('page_icon', 'fa-users')

@section('subtitle')
Manage registered customers, leads, and sales pipeline progress
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .tile { border-top: 3px solid #940000; border-radius: 4px; }
    .badge { font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .modal-header { color: white; }
    .table thead th { background-color: #f8f9fa; color: #333; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
    .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #ced4da; border-radius: 4px; padding-top: 5px; }
    .sms-template-btn { cursor: pointer; border: 1px solid #eee; border-radius: 6px; padding: 8px 12px; margin-bottom: 6px; font-size: 12px; transition: all 0.2s; }
    .sms-template-btn:hover { border-color: #940000; background: #fff5f5; }
    .sms-template-btn.active { border-color: #940000; background: #fff5f5; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="tile-title mb-0">Registered Customers</h3>
                <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus mr-1"></i> Add New Lead</a>
            </div>

            <!-- Filter Controls -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <label class="small font-weight-bold">SERVICE</label>
                    <select id="serviceFilter" class="form-control select2">
                        <option value="">All Services</option>
                        <option value="Bookshop">Bookshop</option>
                        <option value="Wakala">Wakala</option>
                        <option value="Stationery">Stationery</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">STATUS</label>
                    <select id="statusFilter" class="form-control select2">
                        <option value="">All Statuses</option>
                        <option value="New Customer">New Customer</option>
                        <option value="Potential Customer">Potential Customer</option>
                        <option value="Existing Customer">Existing Customer</option>
                        <option value="Inactive Customer">Inactive Customer</option>
                        <option value="VIP Customer">VIP Customer</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">CUSTOMER TYPE</label>
                    <select id="typeFilter" class="form-control select2">
                        <option value="">All Types</option>
                        <option value="School">School</option>
                        <option value="Institution">Institution</option>
                        <option value="Company / Organization">Company / Organization</option>
                        <option value="Parent">Parent</option>
                        <option value="Walk in">Walk in</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="small font-weight-bold">BUYING STAGE</label>
                    <select id="stageFilter" class="form-control select2">
                        <option value="">All Stages</option>
                        <option value="Inquiry">Inquiry</option>
                        <option value="Quotation Sent">Quotation Sent</option>
                        <option value="Negotiation">Negotiation</option>
                        <option value="Order Confirmed">Order Confirmed</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Closed Won">Closed Won</option>
                        <option value="Closed Lost">Closed Lost</option>
                    </select>
                </div>
                <div class="col-md-4 pt-4">
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-search"></i></span></div>
                        <input type="text" id="customSearch" class="form-control" placeholder="Search customers...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="customerTable">
                    <thead>
                        <tr>
                            <th>Name / Organization</th>
                            <th>Contact Info</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Buying Stage</th>
                            <th class="text-center">Actions</th>
                            <th style="display:none;">Service</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                        <tr>
                            <td>
                                <b>{{ $customer->name }}</b><br>
                                <small class="text-muted">{{ $customer->contact_person }}</small>
                            </td>
                            <td>
                                <b class="text-primary">{{ $customer->phone }}</b><br>
                                <small class="text-muted">{{ $customer->email }}</small>
                            </td>
                            <td>{{ $customer->service === 'Bookshop' ? ($customer->type ?: 'N/A') : $customer->service }}</td>
                            <td>
                                @if($customer->service === 'Bookshop')
                                    <span class="badge badge-info">{{ $customer->status }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($customer->service === 'Bookshop')
                                    <span class="badge badge-secondary">{{ $customer->buying_stage }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-primary btn-sm" title="View"><i class="fa fa-eye"></i></a>
                                    @if($customer->service === 'Bookshop')
                                        <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#updateModal-{{ $customer->id }}" title="Quick Update"><i class="fa fa-bolt"></i></button>
                                    @endif
                                    <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#smsModal-{{ $customer->id }}" title="Direct Broadcast"><i class="fa fa-paper-plane"></i></button>
                                    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-secondary btn-sm" title="Edit Full Profile"><i class="fa fa-edit"></i></a>
                                </div>
                            </td>
                            <td style="display:none;">{{ $customer->service }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $customers->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>
</div>

@foreach($customers as $customer)

{{-- ── QUICK UPDATE MODAL ──────────────────────────────────────────── --}}
<div class="modal fade" id="updateModal-{{ $customer->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#940000;">
                <h5 class="modal-title"><i class="fa fa-bolt mr-2"></i> Quick Update: {{ $customer->name }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('customers.quick_update', $customer->id) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    @if($customer->service === 'Bookshop')
                        <div class="form-group">
                            <label class="font-weight-bold small">BUYING STAGE</label>
                            <select name="buying_stage" class="form-control select2-modal" required>
                                @foreach(['Inquiry','Quotation Sent','Negotiation','Order Confirmed','Delivered','Payment Pending','Closed Won','Closed Lost'] as $stage)
                                    <option value="{{ $stage }}" {{ $customer->buying_stage == $stage ? 'selected' : '' }}>{{ $stage }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold small">CUSTOMER STATUS</label>
                            <select name="status" class="form-control select2-modal" required>
                                @foreach(['New Customer','Potential Customer','Existing Customer','Inactive Customer','VIP Customer'] as $s)
                                    <option value="{{ $s }}" {{ $customer->status == $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="form-group">
                        <label class="font-weight-bold small">NEXT FOLLOW-UP DATE</label>
                        <input type="date" name="next_follow_up_date" class="form-control"
                               value="{{ $customer->next_follow_up_date ? $customer->next_follow_up_date->format('Y-m-d') : '' }}">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">UPDATE NOTE <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="4" required
                                  placeholder="Add a note about this update...">{{ $customer->notes }}</textarea>
                        <small class="text-muted"><i class="fa fa-info-circle mr-1"></i> This note will be saved to the customer's profile timeline.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:#940000; border-color:#940000;"><i class="fa fa-save mr-1"></i> Save Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── DIRECT SMS MODAL ──────────────────────────────────────────────── --}}
<div class="modal fade" id="smsModal-{{ $customer->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#940000;">
                <h5 class="modal-title"><i class="fa fa-paper-plane mr-2"></i> Direct Broadcast: {{ $customer->name }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('customers.send_sms', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted mb-3">Recipient: <b class="text-dark">{{ $customer->name }}</b> &mdash; <b class="text-primary">{{ $customer->phone }}</b></p>
                    
                    {{-- SMS Templates --}}
                    <label class="font-weight-bold small d-block mb-2">QUICK TEMPLATES</label>
                    <input type="hidden" name="wa_template" id="wa-template-{{ $customer->id }}" value="general_broadcast">
                    
                    <div class="row mb-3" id="sms-templates-{{ $customer->id }}">
                        <div class="col-md-6">
                            <div class="sms-template-btn" data-target="sms-msg-{{ $customer->id }}" data-template-id="wa-template-{{ $customer->id }}" data-template="general_broadcast"
                                data-msg="Dear {{ $customer->name }}, thank you for choosing TRUMARK. We look forward to serving you. Feel free to contact us anytime.">
                                <i class="fa fa-handshake-o mr-1 text-primary"></i> <b>Welcome Message</b><br>
                                <small class="text-muted">Greeting for new customers</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="sms-template-btn" data-target="sms-msg-{{ $customer->id }}" data-template-id="wa-template-{{ $customer->id }}" data-template="follow_up_reminder"
                                data-msg="Dear {{ $customer->name }}, TruMark Co. LTD would like to follow up on our previous discussion. Do you have any questions or need further assistance? We are here to help! 😊">
                                <i class="fa fa-phone mr-1 text-warning"></i> <b>Follow-Up Reminder</b><br>
                                <small class="text-muted">Check-in with existing lead</small>
                            </div>
                        </div>
                        @if($customer->service === 'Bookshop')
                            <div class="col-md-6 mt-2">
                                <div class="sms-template-btn" data-target="sms-msg-{{ $customer->id }}" data-template-id="wa-template-{{ $customer->id }}" data-template="quote_ready"
                                    data-msg="Dear {{ $customer->name }}, your quotation from TruMark Co. LTD is now ready! . Please check your email for the details or let us know if you have any questions. 🤝">
                                    <i class="fa fa-file-text mr-1 text-success"></i> <b>Quotation Ready</b><br>
                                    <small class="text-muted">Notify quotation is ready</small>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="sms-template-btn" data-target="sms-msg-{{ $customer->id }}" data-template-id="wa-template-{{ $customer->id }}" data-template="payment_reminder"
                                    data-msg="Dear {{ $customer->name }}, this is a friendly reminder regarding your pending payment with TruMark Co. LTD. Please reach out if you have any questions or need assistance. We appreciate your business! 😊">
                                    <i class="fa fa-money mr-1 text-danger"></i> <b>Payment Reminder</b><br>
                                    <small class="text-muted">Friendly payment nudge</small>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small">MESSAGE CONTENT <span class="text-danger">*</span></label>
                        <textarea name="message" id="sms-msg-{{ $customer->id }}" class="form-control" rows="5" required
                                  placeholder="Type your message or select a template above..."></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted"><i class="fa fa-info-circle mr-1"></i> Max 160 characters per SMS.</small>
                            <small class="text-muted char-count-{{ $customer->id }}">0 / 160</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" style="background:#940000; color:#fff; font-weight:700;"><i class="fa fa-paper-plane mr-1"></i> Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endforeach
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#customerTable').DataTable({
        "paging": false, "info": false, "dom": 't',
        "retrieve": true, "destroy": true,
        "order": [] // Disable initial sorting to keep server-side latest() order
    });

    // Filters
    $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
    $('#serviceFilter').on('change', function() { table.column(6).search(this.value).draw(); });
    $('#statusFilter').on('change', function() { table.column(3).search(this.value).draw(); });
    $('#typeFilter').on('change', function() { table.column(2).search(this.value).draw(); });
    $('#stageFilter').on('change', function() { table.column(4).search(this.value).draw(); });

    // Select2
    $('.select2').select2({ width: '100%' });
    $('.modal').on('shown.bs.modal', function () {
        $(this).find('.select2-modal').select2({ dropdownParent: $(this), width: '100%' });
    });

    // SMS Templates
    $(document).on('click', '.sms-template-btn', function() {
        const targetId = $(this).data('target');
        const msg = $(this).data('msg');
        const templateId = $(this).data('template-id');
        const templateName = $(this).data('template');

        $('#' + targetId).val(msg).trigger('input');
        if (templateId) {
            $('#' + templateId).val(templateName);
        }
        $(this).closest('.row').find('.sms-template-btn').removeClass('active');
        $(this).addClass('active');
    });

    // Char counter for SMS
    $(document).on('input', 'textarea[name="message"]', function() {
        const len = $(this).val().length;
        const id = $(this).attr('id').replace('sms-msg-', '');
        $('.char-count-' + id).text(len + ' / 160').css('color', len > 160 ? '#940000' : '');
    });
});
</script>
@endsection
