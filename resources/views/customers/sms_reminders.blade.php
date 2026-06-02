@extends('layouts.vali')

@section('title', 'Bulk SMS Reminders')

@section('page_icon', 'fa-paper-plane')

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
            </div>
            
            <!-- Filter Controls -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="small font-weight-bold">SERVICE</label>
                    <select id="serviceFilter" class="form-control select2">
                        <option value="">All Services</option>
                        <option value="Bookshop">Bookshop</option>
                        <option value="Wakala">Wakala</option>
                        <option value="Stationery">Stationery</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold">LOCATION</label>
                    <select id="locationFilter" class="form-control select2">
                        <option value="">All Locations</option>
                        @php
                            $regions = [
                                'Arusha', 'Dar es Salaam', 'Dodoma', 'Geita', 'Iringa', 'Kagera', 'Katavi', 'Kigoma', 
                                'Kilimanjaro', 'Lindi', 'Manyara', 'Mara', 'Mbeya', 'Morogoro', 'Mtwara', 'Mwanza', 
                                'Njombe', 'Pemba North', 'Pemba South', 'Pwani', 'Rukwa', 'Ruvuma', 'Shinyanga', 
                                'Simiyu', 'Singida', 'Songwe', 'Tabora', 'Tanga', 'Zanzibar Central/South', 
                                'Zanzibar North', 'Zanzibar Urban/West'
                            ];
                        @endphp
                        @foreach($regions as $region)
                            <option value="{{ $region }}">{{ $region }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small font-weight-bold">BUYING STAGE</label>
                    <select id="stageFilter" class="form-control select2">
                        <option value="">All Stages</option>
                        <option value="Inquiry">Inquiry</option>
                        <option value="Quotation Sent">Quotation Sent</option>
                        <option value="Negotiation">Negotiation</option>
                        <option value="Order Confirmed">Order Confirmed</option>
                    </select>
                </div>
                <div class="col-md-3 pt-4">
                    <button type="button" class="btn btn-outline-danger btn-block" id="clearFiltersBtn">
                        <i class="fa fa-times"></i> Clear Filters
                    </button>
                </div>
            </div>
            <div id="selectAllNotice" class="alert alert-info py-2 mb-3 d-none" style="border-left: 5px solid #940000;">
                <span id="noticeText">All recipients on this page are selected.</span>
                <a href="javascript:void(0)" id="selectAllInDb" class="font-weight-bold ml-2 text-dark" style="text-decoration: underline;">Select all customers in the database</a>
                <a href="javascript:void(0)" id="clearAllInDb" class="font-weight-bold ml-2 text-danger d-none">Clear selection</a>
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
                            <th>Service</th>
                            <th>Location</th>
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
                            <td>{{ $customer->service }}</td>
                            <td>{{ $customer->region }} {{ $customer->district }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="tile composer-tile">
            <h3 class="tile-title">Broadcast Composer</h3>
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
                        <div class="d-flex justify-content-between p-2 bg-light border rounded mb-3">
                            <div class="animated-checkbox">
                                <label>
                                    <input type="checkbox" name="channels[]" value="sms" checked>
                                    <span class="label-text font-weight-bold text-primary">SMS</span>
                                </label>
                            </div>
                            <div class="animated-checkbox">
                                <label>
                                    <input type="checkbox" name="channels[]" value="whatsapp" checked>
                                    <span class="label-text font-weight-bold text-success">WhatsApp</span>
                                </label>
                            </div>
                            <div class="animated-checkbox">
                                <label>
                                    <input type="checkbox" name="channels[]" value="email" checked>
                                    <span class="label-text font-weight-bold text-info">Email</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="hiddenInputs"></div>
                    <input type="hidden" name="select_all_in_db" id="selectAllInDbInput" value="0">

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
        "paging": true,
        "pageLength": 25,
        "info": true,
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "retrieve": true,
        "destroy": true,
        "columnDefs": [
            { "visible": false, "targets": [5, 6] }
        ]
    });

    $('#selectAll').on('change', function() {
        let isChecked = $(this).is(':checked');
        $('.customer-checkbox').prop('checked', isChecked).trigger('change');
        
        if (isChecked) {
            $('#selectAllNotice').removeClass('d-none');
        } else {
            $('#selectAllNotice').addClass('d-none');
            $('#selectAllInDbInput').val(0);
            $('#clearAllInDb').addClass('d-none');
            $('#selectAllInDb').removeClass('d-none');
        }
    });

    $('#selectAllInDb').on('click', function() {
        $('#selectAllInDbInput').val(1);
        $('#noticeText').text('Success! Entire customer database selected.');
        $(this).addClass('d-none');
        $('#clearAllInDb').removeClass('d-none');
        $('#selectedCount').text('ALL');
    });

    $('#clearAllInDb').on('click', function() {
        $('#selectAll').prop('checked', false).trigger('change');
    });

    function syncSelectedRecipients() {
        let selected = [];
        let html = '';
        $('.customer-checkbox:checked').each(function() {
            selected.push($(this).val());
            html += '<input type="hidden" name="customer_ids[]" value="'+$(this).val()+'">';
            $(this).closest('tr').addClass('selected-row');
        });
        $('.customer-checkbox:not(:checked)').closest('tr').removeClass('selected-row');
        $('#hiddenInputs').html(html);
        return selected;
    }

    $(document).on('change', '.customer-checkbox', function() {
        let selected = syncSelectedRecipients();

        if ($('#selectAllInDbInput').val() == 0) {
            $('#selectedCount').text(selected.length);
        }
        $('#sendBtn').prop('disabled', selected.length === 0 && $('#selectAllInDbInput').val() == 0);
    });

    // Filters
    $('#stageFilter').on('change', function() {
        table.column(3).search($(this).val()).draw();
    });
    $('#serviceFilter').on('change', function() {
        table.column(5).search($(this).val()).draw();
    });
    $('#locationFilter').on('change', function() {
        table.column(6).search($(this).val()).draw();
    });

    $('#clearFiltersBtn').on('click', function() {
        $('#serviceFilter').val('').trigger('change');
        $('#locationFilter').val('').trigger('change');
        $('#stageFilter').val('').trigger('change');
    });

    $('.template-btn').on('click', function() {
        let msg = $(this).data('msg');
        $('#msgText').val(msg).trigger('input');
    });

    $('#msgText').on('input', function() {
        $('#charCount').text($(this).val().length + ' / 160');
    });

    $('#bulkForm').on('submit', function(e) {
        const selectAllDb = $('#selectAllInDbInput').val() == 1;
        const selected = syncSelectedRecipients();
        const channels = $('input[name="channels[]"]:checked').length;

        if (!selectAllDb && selected.length === 0) {
            e.preventDefault();
            alert('Please select at least one recipient.');
            return false;
        }

        if (channels === 0) {
            e.preventDefault();
            alert('Please select at least one delivery channel (SMS, WhatsApp, or Email).');
            return false;
        }

        $('#sendBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin mr-1"></i> Sending...');
    });
});
</script>
@endsection
