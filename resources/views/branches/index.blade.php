@extends('layouts.vali')

@section('title', 'Manage Branches')

@section('page_icon', 'fa-map-marker')

@section('subtitle')
Register and manage company locations, team sizes and performance
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
    
    /* Card list styling */
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
        /* Mobile header and button layout */
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
        
        /* Premium Native Mobile App Card Spacing Canvas */
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
                <h3 class="tile-title mb-0 font-weight-bold" style="font-size: 18px;"><i class="fa fa-map-marker text-primary mr-2"></i> REGISTERED BRANCHES</h3>
                <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus mr-1"></i>Add New Branch
                </a>
            </div>
            
            <div class="p-4 bg-white">
                {{-- Desktop View (Traditional Table) --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover table-bordered">
                        <thead class="bg-light text-uppercase" style="font-size: 11px; letter-spacing: 1px;">
                            <tr>
                                <th>Branch Name</th>
                                <th>Location</th>
                                <th>Team Size</th>
                                <th>Total Leads</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($branches as $branch)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mr-3 bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                                            <i class="fa fa-map-marker"></i>
                                        </div>
                                        <b>{{ $branch->name }}</b>
                                    </div>
                                </td>
                                <td>{{ $branch->location ?? 'N/A' }}</td>
                                <td><span class="badge badge-info">{{ $branch->users_count }} Staff</span></td>
                                <td><span class="badge badge-success">{{ $branch->customers_count }} Leads</span></td>
                                <td>
                                    @if($branch->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('branches.edit', $branch->id) }}" class="btn btn-primary btn-sm" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        
                                        <form action="{{ route('branches.toggle_status', $branch->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn {{ $branch->is_active ? 'btn-warning' : 'btn-success' }} btn-sm" title="{{ $branch->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="fa {{ $branch->is_active ? 'fa-ban' : 'fa-play' }}"></i>
                                            </button>
                                        </form>

                                        @if($branch->users_count == 0 && $branch->customers_count == 0)
                                        <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this branch?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">No branches found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile View (Premium Card List) --}}
                <div class="d-block d-md-none mobile-card-container">
                    @forelse($branches as $branch)
                    <div class="card shadow-sm mb-3 border-0" style="border-radius: 12px; background: #fff; border-left: 5px solid {{ $branch->is_active ? '#28a745' : '#6c757d' }};">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="mr-3 bg-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; border: 1px solid rgba(0,0,0,0.05);">
                                    <i class="fa fa-map-marker" style="font-size: 16px;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <b class="d-block text-dark" style="font-size: 15px; line-height: 1.2;">{{ $branch->name }}</b>
                                    <small class="text-muted"><i class="fa fa-globe mr-1"></i> {{ $branch->location ?? 'N/A' }}</small>
                                </div>
                                <div class="text-right">
                                    @if($branch->is_active)
                                        <span class="badge badge-success text-uppercase" style="font-size: 9px; padding: 4px 8px; border-radius: 20px;">ACTIVE</span>
                                    @else
                                        <span class="badge badge-secondary text-uppercase" style="font-size: 9px; padding: 4px 8px; border-radius: 20px;">INACTIVE</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="bg-light p-2 rounded mb-3" style="font-size: 12px; border: 1px solid #eee;">
                                <div class="row text-center">
                                    <div class="col-6 border-right">
                                        <small class="text-muted d-block text-uppercase" style="font-size: 8px; letter-spacing: 0.5px; margin-bottom: 2px;">Team Size</small>
                                        <span class="font-weight-bold text-info" style="font-size: 13px;"><i class="fa fa-users mr-1"></i> {{ $branch->users_count }} Staff</span>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block text-uppercase" style="font-size: 8px; letter-spacing: 0.5px; margin-bottom: 2px;">Total Leads</small>
                                        <span class="font-weight-bold text-success" style="font-size: 13px;"><i class="fa fa-user mr-1"></i> {{ $branch->customers_count }} Leads</span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end align-items-center pt-2 border-top">
                                <div class="btn-group">
                                    <a href="{{ route('branches.edit', $branch->id) }}" class="btn btn-primary btn-sm" title="Edit">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                    
                                    <form action="{{ route('branches.toggle_status', $branch->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn {{ $branch->is_active ? 'btn-warning' : 'btn-success' }} btn-sm" title="{{ $branch->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="fa {{ $branch->is_active ? 'fa-ban' : 'fa-play' }}"></i> {{ $branch->is_active ? 'Block' : 'Run' }}
                                        </button>
                                    </form>

                                    @if($branch->users_count == 0 && $branch->customers_count == 0)
                                    <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this branch?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-5 text-muted bg-light rounded" style="border: 2px dashed #ddd;">
                        <i class="fa fa-map-marker fa-2x mb-2 text-muted"></i>
                        <p class="mb-0 font-weight-bold">No branches found.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
