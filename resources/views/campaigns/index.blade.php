@extends('layouts.vali')

@section('title', 'Holiday & Event Campaigns')
@section('page_icon', 'fa-calendar-check-o')
@section('subtitle', 'Manage automated SMS broadcasts for holidays and special events')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between mb-3">
                <h3 class="tile-title">All Campaigns</h3>
                <div>
                    <a href="{{ route('campaigns.calendar') }}" class="btn btn-info mr-2"><i class="fa fa-calendar"></i> View Calendar</a>
                    <a href="{{ route('campaigns.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> New Campaign</a>
                </div>
            </div>
            
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead>
                        <tr>
                            <th>Campaign Name</th>
                            <th>Event Date</th>
                            <th>Target Audience</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $campaign)
                        <tr>
                            <td><b>{{ $campaign->name }}</b></td>
                            <td>{{ $campaign->event_date->format('d M Y') }}</td>
                            <td>
                                Service: <b>{{ $campaign->target_service ?? 'All' }}</b> <br>
                                Location: <b>{{ $campaign->target_location ?? 'All' }}</b>
                            </td>
                            <td>
                                @if($campaign->status == 'Completed')
                                    <span class="badge badge-success">Completed</span>
                                @elseif($campaign->status == 'Processing')
                                    <span class="badge badge-warning">Processing</span>
                                @elseif($campaign->status == 'Cancelled')
                                    <span class="badge badge-danger">Cancelled</span>
                                @else
                                    <span class="badge badge-info">Pending</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('campaigns.edit', $campaign->id) }}" class="btn btn-sm btn-secondary"><i class="fa fa-edit"></i> Edit</a>
                                <form action="{{ route('campaigns.destroy', $campaign->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this campaign?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No campaigns scheduled. Click "New Campaign" to start.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $campaigns->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection
