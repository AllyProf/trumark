@extends('layouts.vali')

@section('title', 'Sales Records')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sales Records (Won/Confirmed Orders)</h5>
            </div>
            <div class="card-body p-0">
                <!-- Unified Search Control Bar -->
                <div class="d-flex flex-wrap justify-content-end align-items-center px-3 py-2 bg-light border-bottom">
                    <div class="d-flex align-items-center">
                        <div class="input-group input-group-sm m-0">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-secondary text-muted"><i class="fa fa-search"></i></span>
                            </div>
                            <input type="text" id="customSearch" class="form-control border-secondary shadow-none" placeholder="Search sales records..." style="width: 250px;">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover custom-data-table w-100 mb-0">
                        <thead>
                            <tr>
                                <th class="text-center">ORDER DATE</th>
                                <th>CUSTOMER</th>
                                <th class="text-center">STAGE</th>
                                <th class="text-center">EST. VALUE</th>
                                <th class="text-center">OFFICER</th>
                                <th class="text-center">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $customer)
                            @php
                                $stageMap = [
                                    'Order Confirmed' => 'badge-success',
                                    'Delivered'       => 'badge-dark',
                                    'Closed Won'      => 'badge-primary',
                                ];
                                $stageClass = $stageMap[$customer->buying_stage] ?? 'badge-secondary';
                            @endphp
                            <tr>
                                <td class="align-middle text-center">{{ $customer->updated_at->format('d M, Y') }}</td>
                                <td class="align-middle">
                                    <h6 class="m-b-0 font-weight-bold text-dark">{{ $customer->name }}</h6>
                                    <small class="text-muted">{{ $customer->phone }}</small>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge {{ $stageClass }} px-3 py-2 text-uppercase" style="font-size: 10px; letter-spacing: 0.5px; border-radius: 20px;">
                                        {{ $customer->buying_stage }}
                                    </span>
                                </td>
                                <td class="align-middle text-center font-weight-bold text-success">{{ number_format($customer->estimated_monthly_value) }}</td>
                                <td class="align-middle text-center text-muted">{{ $customer->salesOfficer->name ?? 'N/A' }}</td>
                                <td class="align-middle text-center">
                                    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-icon btn-outline-info btn-sm rounded-circle mx-1" title="View"><i class="fa fa-eye"></i></a>
                                    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-icon btn-outline-primary btn-sm rounded-circle mx-1" title="Edit"><i class="fa fa-edit"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Bar Removed in favor of DataTables pagination -->

                <!-- SMS Modals -->
                @foreach($customers as $customer)
                    <div class="modal fade" id="smsModal-{{ $customer->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header" style="background: linear-gradient(135deg, #940000, #7a0000); color: #fff;">
                                    <h5 class="modal-title"><i class="fa fa-message-circle mr-2"></i> Send SMS to: {{ $customer->name }}</h5>
                                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                                </div>
                                <form action="{{ route('customers.send_sms', $customer->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-body text-left">
                                        <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 13px;">
                                            <strong>Phone:</strong> {{ $customer->phone }}
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold small text-uppercase">Message</label>
                                            <textarea name="message" class="form-control" rows="4" maxlength="500" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Send SMS</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
<style>
    .custom-data-table { border: 1px solid #dee2e6; margin-bottom: 0 !important; }
    .custom-data-table thead th {
        background-color: #940000 !important;
        color: #ffffff;
        border: 1px solid #7a0000;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 15px 10px;
    }
    .custom-data-table tbody td { border: 1px solid #dee2e6; vertical-align: middle; padding: 10px; }
    .custom-data-table tbody tr:hover { background-color: #f8f9fa; }
</style>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script>
    $(document).ready(function() {
        var table = $('.custom-data-table').DataTable({
            "paging": true,
            "info": true,
            "pageLength": 20,
            "ordering": true,
            "dom": "lBfrtip",
            "buttons": [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fa fa-file-text mr-1"></i> Excel',
                    className: 'btn-sm btn-success border-0 px-3 mr-2',
                    exportOptions: { columns: ':not(:last-child)' }
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-printer mr-1"></i> Print',
                    className: 'btn-sm btn-info border-0 px-3',
                    exportOptions: { columns: ':not(:last-child)' }
                }
            ]
        });
        
        // Hide default search box to use custom one
        $('.dataTables_filter').hide();
        $('#customSearch').on('keyup', function() { table.search(this.value).draw(); });
    });
</script>
@endsection
