@extends('layouts.vali')

@section('title', 'Supplier Directory')

@section('page_icon', 'fa-truck')

@section('subtitle')
Register and manage all company suppliers and service providers
@endsection

@section('styles')
<style>
    .tile {
        border-radius: 12px !important;
        box-shadow: 0 4px 6px rgba(0,0,0,0.04) !important;
        border: 0 !important;
        margin-bottom: 25px;
    }

    .table td, .table th {
        vertical-align: middle !important;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(148, 0, 0, 0.03) !important;
    }

    .card {
        transition: all 0.2s ease;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.06) !important;
    }

    @media (max-width: 767px) {
        .tile-title {
            font-size: 16px !important;
        }
        .tile .d-flex.justify-content-between {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 12px;
        }
        .tile .d-flex.justify-content-between a {
            width: 100% !important;
            display: block !important;
            text-align: center;
        }
        .mobile-card-container {
            background-color: #f4f6f9 !important;
            padding: 15px 12px !important;
            border-radius: 12px !important;
            margin-left: -15px !important;
            margin-right: -15px !important;
            margin-bottom: -15px !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
        }
        .mobile-card-container .card {
            margin-bottom: 16px !important;
            border: 1px solid rgba(0,0,0,0.05) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03) !important;
        }
        .mobile-card-container .card:last-child {
            margin-bottom: 0 !important;
        }
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12 col-12">
        <div class="tile border-0 shadow-sm p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h3 class="tile-title mb-0 font-weight-bold" style="font-size: 18px;">
                    <i class="fa fa-truck text-primary mr-2"></i> REGISTERED SUPPLIERS
                </h3>
                <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus mr-1"></i> Register New Supplier
                </a>
            </div>

            <div class="p-4 bg-white">
                @if($suppliers->isEmpty())
                    <div class="text-center py-5 text-muted bg-light rounded" style="border: 2px dashed #ddd;">
                        <i class="fa fa-truck fa-2x mb-2 text-muted"></i>
                        <p class="mb-0 font-weight-bold">No suppliers registered yet.</p>
                        <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm mt-2">
                            <i class="fa fa-plus mr-1"></i> Register First Supplier
                        </a>
                    </div>
                @else
                    {{-- Desktop View --}}
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover table-bordered" id="suppliersTable">
                            <thead class="bg-light text-uppercase" style="font-size: 11px; letter-spacing: 1px;">
                                <tr>
                                    <th>Supplier</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Location</th>
                                    <th>Service Offered</th>
                                    <th>Note</th>
                                    <th>Status</th>
                                    <th>Added By</th>
                                    <th class="text-center" style="width: 160px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($suppliers as $supplier)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="mr-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                                                 style="width: 40px; height: 40px; font-weight: bold; font-size: 16px; flex-shrink:0;">
                                                {{ strtoupper(substr($supplier->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <b>{{ $supplier->name }}</b><br>
                                                <small class="text-muted">{{ $supplier->created_at->format('d M Y') }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $supplier->phone ?: '—' }}</td>
                                    <td>{{ $supplier->email ?: '—' }}</td>
                                    <td>{{ $supplier->location ?: '—' }}</td>
                                    <td>
                                        @if($supplier->service_offered)
                                            <span class="badge badge-warning">{{ $supplier->service_offered }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($supplier->note)
                                            <span title="{{ $supplier->note }}" style="cursor:help;">
                                                {{ \Illuminate\Support\Str::limit($supplier->note, 40) }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($supplier->status === 'active')
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td><small class="text-muted">{{ $supplier->creator->name ?? '—' }}</small></td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('suppliers.show', $supplier->id) }}" class="btn btn-info btn-sm" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-primary btn-sm" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn {{ $supplier->status === 'active' ? 'btn-warning' : 'btn-success' }} btn-sm toggle-status"
                                                    data-id="{{ $supplier->id }}"
                                                    data-action="{{ $supplier->status === 'active' ? 'deactivate' : 'activate' }}"
                                                    title="{{ $supplier->status === 'active' ? 'Deactivate' : 'Activate' }}">
                                                <i class="fa {{ $supplier->status === 'active' ? 'fa-ban' : 'fa-play' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm delete-supplier" data-id="{{ $supplier->id }}" data-name="{{ $supplier->name }}" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>

                                        <form id="toggle-form-{{ $supplier->id }}" action="{{ route('suppliers.toggle_status', $supplier->id) }}" method="POST" style="display:none;">@csrf</form>
                                        <form id="delete-form-{{ $supplier->id }}" action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" style="display:none;">@csrf @method('DELETE')</form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile View --}}
                    <div class="d-block d-md-none mobile-card-container">
                        @foreach($suppliers as $supplier)
                        <div class="card shadow-sm mb-3 border-0" style="border-radius: 12px; background: #fff; border-left: 5px solid {{ $supplier->status === 'active' ? '#28a745' : '#6c757d' }};">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="mr-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                                         style="width: 42px; height: 42px; font-weight: bold; font-size: 15px; flex-shrink:0;">
                                        {{ strtoupper(substr($supplier->name, 0, 1)) }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <b class="d-block text-dark" style="font-size: 15px; line-height: 1.2;">{{ $supplier->name }}</b>
                                        <small class="text-muted">{{ $supplier->created_at->format('d M Y') }}</small>
                                    </div>
                                    <div class="text-right">
                                        @if($supplier->status === 'active')
                                            <span class="badge badge-success text-uppercase" style="font-size: 9px; padding: 4px 8px; border-radius: 20px;">Active</span>
                                        @else
                                            <span class="badge badge-secondary text-uppercase" style="font-size: 9px; padding: 4px 8px; border-radius: 20px;">Inactive</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="bg-light p-2 rounded mb-3" style="font-size: 12px; border: 1px solid #eee;">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted"><i class="fa fa-phone mr-1"></i> Phone:</span>
                                        <span class="font-weight-bold text-dark">{{ $supplier->phone ?: '—' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted"><i class="fa fa-envelope mr-1"></i> Email:</span>
                                        <span class="font-weight-bold text-dark text-truncate ml-2" style="max-width: 180px;">{{ $supplier->email ?: '—' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted"><i class="fa fa-map-marker mr-1"></i> Location:</span>
                                        <span class="font-weight-bold text-dark">{{ $supplier->location ?: '—' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted"><i class="fa fa-briefcase mr-1"></i> Service:</span>
                                        <span class="font-weight-bold text-dark">{{ $supplier->service_offered ?: '—' }}</span>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <small class="text-muted">By: {{ $supplier->creator->name ?? '—' }}</small>
                                    <div class="btn-group">
                                        <a href="{{ route('suppliers.show', $supplier->id) }}" class="btn btn-info btn-sm" title="View"><i class="fa fa-eye"></i></a>
                                        <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-primary btn-sm" title="Edit"><i class="fa fa-edit"></i></a>
                                        <button type="button"
                                                class="btn {{ $supplier->status === 'active' ? 'btn-warning' : 'btn-success' }} btn-sm toggle-status"
                                                data-id="{{ $supplier->id }}"
                                                data-action="{{ $supplier->status === 'active' ? 'deactivate' : 'activate' }}">
                                            <i class="fa {{ $supplier->status === 'active' ? 'fa-ban' : 'fa-play' }}"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm delete-supplier" data-id="{{ $supplier->id }}" data-name="{{ $supplier->name }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif

                {{-- Pagination --}}
                @if($suppliers->hasPages())
                <div class="mt-3">{{ $suppliers->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        $('#suppliersTable').DataTable({
            "info": false,
            "pageLength": 15,
            "retrieve": true,
            "destroy": true
        });

        // Toggle Status
        $('.toggle-status').on('click', function() {
            let id = $(this).data('id');
            let action = $(this).data('action');
            Swal.fire({
                title: action.charAt(0).toUpperCase() + action.slice(1) + ' Supplier?',
                text: "Are you sure you want to " + action + " this supplier?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#940000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, ' + action + ' it'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#toggle-form-' + id).submit();
                }
            });
        });

        // Delete Supplier
        $('.delete-supplier').on('click', function() {
            let id = $(this).data('id');
            let name = $(this).data('name');
            Swal.fire({
                title: 'Delete Supplier?',
                html: "<strong>" + name + "</strong> will be permanently removed.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#940000',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#delete-form-' + id).submit();
                }
            });
        });
    });
</script>
@endsection
