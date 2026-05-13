@extends('layouts.vali')

@section('title', 'Bulk SMS Reminders')

@section('page_icon')
<i class="fa fa-paper-plane"></i>
@endsection

@section('subtitle')
Select multiple recipients and broadcast bulk SMS notifications
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .tile { border-top: 3px solid #940000; border-radius: 4px; }
    .composer-tile { position: sticky; top: 10px; }
    .template-btn { cursor: pointer; border: 1px solid #ddd; background: #f9f9f9; padding: 5px 12px; border-radius: 20px; font-size: 11px; margin-bottom: 5px; display: inline-block; transition: all 0.2s; }
    .template-btn:hover { background: #940000; color: #fff; border-color: #940000; }
    .selected-row { background-color: #f1f8e9 !important; }
    .select2-container--default .select2-selection--single { height: 38px; border: 1px solid #ced4da; border-radius: 4px; padding-top: 5px; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="tile-title mb-0">Recipient Selection</h3>
                <div class="d-flex align-items-center">
                    <select id="stageFilter" class="form-control select2 mr-3" style="width: 200px;">
                        <option value="">All Buying Stages</option>
                        <option value="Inquiry">Inquiry</option>
                        <option value="Quotation Sent">Quotation Sent</option>
                        <option value="Negotiation">Negotiation</option>
                        <option value="Order Confirmed">Order Confirmed</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="recipientTable">
                    <thead>
                        <tr>
                            <th width="40">
                                <div class="animated-checkbox">
                                    <label>
                                        <input type="checkbox" id="selectAll">
                                        <span class="label-text"></span>
                                    </label>
                                </div>
                            </th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Stage</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                        <tr>
                            <td>
                                <div class="animated-checkbox">
                                    <label>
                                        <input type="checkbox" class="customer-checkbox" value="{{ $customer->id }}" data-name="{{ $customer->name }}">
                                        <span class="label-text"></span>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <b>{{ $customer->name }}</b><br>
                                <small class="text-muted">{{ $customer->contact_person }}</small>
                            </td>
                            <td><b class="text-primary">{{ $customer->phone }}</b></td>
                            <td><span class="badge badge-info">{{ $customer->buying_stage }}</span></td>
                            <td><span class="badge badge-secondary">{{ $customer->status }}</span></td>
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

    <div class="col-md-4">
        <div class="tile composer-tile">
            <h3 class="tile-title">SMS Composer</h3>
            <div class="tile-body">
                <form action="{{ route('customers.send_bulk_sms') }}" method="POST" id="bulkForm">
                    @csrf
                    <div class="text-center mb-4 py-3 bg-light rounded border">
                        <h2 class="text-primary mb-0" id="selectedCount">0</h2>
                        <small class="text-muted font-weight-bold uppercase">RECIPIENTS SELECTED</small>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Quick Templates</label>
                        <div>
                            <span class="template-btn" data-msg="Hello {name}, thank you for choosing TRUMARK. Your inquiry is being processed.">Follow-up</span>
                            <span class="template-btn" data-msg="Hello {name}, your quotation from TRUMARK is ready. Please check your email.">Quote Ready</span>
                            <span class="template-btn" data-msg="Hello {name}, friendly reminder regarding your pending payment with TRUMARK.">Payment Reminder</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Message Content</label>
                        <textarea name="message" id="msgText" class="form-control" rows="6" placeholder="Type your message here..." required></textarea>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">Supports {name} tag</small>
                            <small id="charCount" class="font-weight-bold">0 / 160</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Delivery Channels</label>
                        <div class="d-flex justify-content-around p-2 bg-light border rounded mb-3">
                            <div class="animated-checkbox">
                                <label>
                                    <input type="checkbox" name="channels[]" value="sms" checked>
                                    <span class="label-text font-weight-bold">SMS</span>
                                </label>
                            </div>
                            <div class="animated-checkbox">
                                <label>
                                    <input type="checkbox" name="channels[]" value="whatsapp">
                                    <span class="label-text font-weight-bold text-success">WhatsApp</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="hiddenInputs"></div>

                    <button class="btn btn-primary btn-block py-2" type="submit" id="sendBtn" disabled>
                        <i class="fa fa-paper-plane mr-1"></i> SEND BROADCAST
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

    var table = $('#recipientTable').DataTable({
        "paging": false,
        "info": false,
        "dom": 't',
        "retrieve": true,
        "destroy": true
    });

    $('#selectAll').on('change', function() {
        $('.customer-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
    });

    $(document).on('change', '.customer-checkbox', function() {
        let selected = [];
        let html = '';
        $('.customer-checkbox:checked').each(function() {
            selected.push($(this).val());
            html += '<input type="hidden" name="customer_ids[]" value="'+$(this).val()+'">';
            $(this).closest('tr').addClass('selected-row');
        });
        $('.customer-checkbox:not(:checked)').closest('tr').removeClass('selected-row');
        
        $('#selectedCount').text(selected.length);
        $('#hiddenInputs').html(html);
        $('#sendBtn').prop('disabled', selected.length === 0);
    });

    $('#stageFilter').on('change', function() {
        table.column(3).search($(this).val()).draw();
    });

    $('.template-btn').on('click', function() {
        let msg = $(this).data('msg');
        $('#msgText').val(msg).trigger('input');
    });

    $('#msgText').on('input', function() {
        $('#charCount').text($(this).val().length + ' / 160');
    });
});
</script>
@endsection
