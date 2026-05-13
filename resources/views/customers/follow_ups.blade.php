@extends('layouts.vali')

@section('title', 'Pending Follow-ups')

@section('page_icon')
<i class="fa fa-calendar-check-o"></i>
@endsection

@section('subtitle')
Track and manage scheduled follow-ups with potential leads and customers
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .tile { border-top: 3px solid #940000; }
    .table thead th { background-color: #f8f9fa; color: #333; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; }
    .overdue-row { background-color: #f8d7da !important; border-left: 5px solid #dc3545 !important; }
    .overdue-row td { color: #721c24 !important; }
    .overdue-row .text-primary, .overdue-row b { color: #721c24 !important; }
    .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #ced4da; border-radius: 4px; padding-top: 5px; }

    @media (max-width: 767px) {
        /* Header: stack title and actions vertically */
        .followup-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 12px !important;
        }
        .followup-header .d-flex {
            flex-direction: column !important;
            width: 100% !important;
            gap: 8px !important;
        }
        /* Send All Reminders button: full width */
        .followup-header .btn {
            width: 100% !important;
        }
        /* Search box: full width */
        .followup-header .input-group {
            width: 100% !important;
        }
        /* Table text compact */
        #followUpTable th, #followUpTable td {
            font-size: 11px !important;
            padding: 6px 6px !important;
            white-space: nowrap;
        }
        /* Modal full width on mobile */
        .modal-dialog {
            margin: 10px !important;
            max-width: calc(100vw - 20px) !important;
        }
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-4 followup-header">
                <h3 class="tile-title mb-0">Follow-up Schedule 
                    <span class="badge badge-pill badge-primary ml-2" style="font-size: 14px;">
                        {{ $dueCount }} Due
                    </span>
                </h3>
                <div class="d-flex">
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#bulkSmsModal">
                        <i class="fa fa-paper-plane"></i> Send All Reminders
                    </button>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-search"></i></span></div>
                        <input type="text" id="customSearch" class="form-control" placeholder="Search schedule...">
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="followUpTable">
                    <thead>
                        <tr>
                            <th>Next Follow-up</th>
                            <th>Customer / Organization</th>
                            <th>Primary Phone</th>
                            <th>Buying Stage</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                        @php
                            $isPastDue = \Carbon\Carbon::parse($customer->next_follow_up_date)->isPast();
                        @endphp
                        <tr class="{{ $isPastDue ? 'overdue-row' : '' }}">
                            <td>
                                <b>{{ \Carbon\Carbon::parse($customer->next_follow_up_date)->format('d M, Y') }}</b>
                                @if($isPastDue)
                                    <span class="badge badge-danger ml-2">OVERDUE</span>
                                @endif
                            </td>
                            <td>
                                <b>{{ $customer->name }}</b><br>
                                <small class="text-muted">{{ $customer->contact_person }}</small>
                            </td>
                            <td><b class="text-primary">{{ $customer->phone }}</b></td>
                            <td>
                                <span class="badge badge-info">{{ $customer->buying_stage }}</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-primary btn-sm" title="View"><i class="fa fa-eye"></i></a>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#updateModal-{{ $customer->id }}" title="Update"><i class="fa fa-bolt"></i></button>
                                    <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#smsModal-{{ $customer->id }}" title="SMS"><i class="fa fa-envelope"></i></button>
                                </div>
                            </td>
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

{{-- ── BULK SMS MODAL ──────────────────────────────────────────────── --}}
<div class="modal fade" id="bulkSmsModal" tabindex="-1" role="dialog" aria-labelledby="bulkSmsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background:#940000; color: white;">
                <h5 class="modal-title" id="bulkSmsModalLabel"><i class="fa fa-envelope mr-2"></i> Send Bulk Reminders</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('customers.send_all_reminders') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle mr-1"></i> You are about to send an SMS to <b>{{ $dueCount }}</b> customers who are currently due or overdue for follow-up.
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold small">SMS CONTENT (EDITABLE)</label>
                        <textarea name="message" id="bulk-sms-message" class="form-control" rows="6" required>{{ $template }}</textarea>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">Note: <b>{name}</b> will be replaced by each customer's actual name.</small>
                            <small class="text-muted" id="bulk-char-count">0 / 160</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:#940000; border-color:#940000;">
                        <i class="fa fa-paper-plane mr-1"></i> Send Reminders Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($customers as $customer)
<!-- Modals for each customer -->
<div class="modal fade" id="updateModal-{{ $customer->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Follow-up Update: {{ $customer->name }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('customers.quick_update', $customer->id) }}" method="POST">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <div class="form-group">
                        <label>Current Buying Stage</label>
                        <select name="buying_stage" class="form-control select2" required>
                            <option value="Inquiry" {{ $customer->buying_stage == 'Inquiry' ? 'selected' : '' }}>Inquiry</option>
                            <option value="Quotation Sent" {{ $customer->buying_stage == 'Quotation Sent' ? 'selected' : '' }}>Quotation Sent</option>
                            <option value="Negotiation" {{ $customer->buying_stage == 'Negotiation' ? 'selected' : '' }}>Negotiation</option>
                            <option value="Order Confirmed" {{ $customer->buying_stage == 'Order Confirmed' ? 'selected' : '' }}>Order Confirmed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Next Follow-up Date</label>
                        <input type="date" name="next_follow_up_date" class="form-control" value="{{ \Carbon\Carbon::parse($customer->next_follow_up_date)->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="smsModal-{{ $customer->id }}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Send SMS Reminder</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('customers.send_sms', $customer->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted">Recipient: <b>{{ $customer->phone }}</b></p>
                    <div class="form-group">
                        <label>Message Content</label>
                        <textarea name="message" class="form-control" rows="4" required>Hello {{ $customer->name }}, this is a friendly reminder from TRUMARK regarding our previous discussion. Looking forward to hearing from you.</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Send Message</button>
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
        var table = $('#followUpTable').DataTable({
            "paging": false,
            "info": false,
            "dom": 't',
            "retrieve": true,
            "destroy": true
        });

        $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
        
        $('.select2').select2({ width: '100%' });

        // Bulk SMS Char Counter
        $('#bulk-sms-message').on('input', function() {
            var len = $(this).val().length;
            $('#bulk-char-count').text(len + ' / 160').css('color', len > 160 ? '#dc3545' : '');
        }).trigger('input');
    });
</script>
@endsection
