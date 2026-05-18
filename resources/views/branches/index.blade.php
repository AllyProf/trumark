@extends('layouts.vali')

@section('title', 'Manage Branches')

@section('page_icon', 'fa-map-marker')

@section('subtitle')
Register and manage company locations, team sizes and performance
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="tile-title mb-0">Registered Branches</h3>
                <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus mr-2"></i>Add New Branch
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Branch Name</th>
                            <th>Location</th>
                            <th>Team Size</th>
                            <th>Total Leads</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
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
        </div>
    </div>
</div>
@endsection
