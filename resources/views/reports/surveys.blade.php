@extends('layouts.vali')

@section('title', 'Customer Satisfaction & Surveys')

@section('page_icon', 'fa-commenting-o')

@section('subtitle')
Detailed breakdown of customer feedback, ratings, and service quality assessments
@endsection

@section('styles')
<style>
    .star-rating .fa-star { color: #ffc107; }
    .star-rating .fa-star-o { color: #e4e5e9; }
    .feedback-comment {
        font-style: italic;
        color: #555;
        border-left: 3px solid #940000;
        padding-left: 10px;
        margin-top: 5px;
    }
    .rating-badge {
        font-size: 1.2rem;
        font-weight: bold;
        padding: 5px 15px;
        border-radius: 20px;
    }
    .badge-excellent { background-color: #d4edda; color: #155724; }
    .badge-good { background-color: #cce5ff; color: #004085; }
    .badge-neutral { background-color: #fff3cd; color: #856404; }
    .badge-poor { background-color: #f8d7da; color: #721c24; }
</style>
@endsection

@section('content')

{{-- Filter & Action Bar --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="tile p-3 shadow-sm">
            <form action="{{ route('reports.surveys') }}" method="GET" class="row align-items-center">
                @if(auth()->user()->role === 'super_admin')
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-filter text-primary mr-2"></i>
                        <span class="font-weight-bold mr-2">Branch:</span>
                        <select name="branch_id" class="form-control" onchange="this.form.submit()">
                            <option value="">🌍 Global (All Branches)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                    📍 {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif
                
                @if(auth()->user()->role !== 'sales_officer')
                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-user text-primary mr-2"></i>
                        <span class="font-weight-bold mr-2">Staff:</span>
                        <select name="staff_id" class="form-control" onchange="this.form.submit()">
                            <option value="">👥 All Staff Members</option>
                            @foreach($officers as $officer)
                                <option value="{{ $officer->id }}" {{ $staffId == $officer->id ? 'selected' : '' }}>
                                    👤 {{ $officer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif

                <div class="col-md-4 text-right">
                    <a href="{{ route('reports.surveys') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-refresh mr-1"></i> Reset Filters
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row mb-4 d-flex align-items-stretch">
    <div class="col-md-3">
        <div class="tile p-0 mb-0 h-100 d-flex flex-column justify-content-center text-center shadow-sm" style="border-top: 4px solid #940000;">
            <div class="p-3">
                <i class="fa fa-star fa-2x text-warning mb-2"></i>
                <h6 class="text-muted text-uppercase small font-weight-bold">Average Rating</h6>
                <h2 class="mb-1">{{ number_format($avgRating, 1) }} <small class="text-muted">/ 5.0</small></h2>
                <div class="star-rating">
                    @for($i=1; $i<=5; $i++)
                        <i class="fa {{ $i <= round($avgRating) ? 'fa-star' : 'fa-star-o' }}"></i>
                    @endfor
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="tile p-0 mb-0 h-100 d-flex flex-column justify-content-center text-center shadow-sm" style="border-top: 4px solid #17a2b8;">
            <div class="p-3">
                <i class="fa fa-comments fa-2x text-info mb-2"></i>
                <h6 class="text-muted text-uppercase small font-weight-bold">Total Surveys</h6>
                <h2 class="mb-0">{{ number_format($totalFeedbacks) }}</h2>
                <small class="text-muted">Responses Received</small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="tile p-3 mb-0 h-100 shadow-sm">
            <h6 class="text-muted text-uppercase small font-weight-bold mb-3"><i class="fa fa-bar-chart mr-2"></i>Rating Distribution</h6>
            <div class="d-flex align-items-end justify-content-around pb-2" style="height: 100px;">
                @foreach([5, 4, 3, 2, 1] as $star)
                    @php 
                        $count = $ratingBreakdown[$star] ?? 0;
                        $percent = $totalFeedbacks > 0 ? ($count / $totalFeedbacks) * 100 : 0;
                        $color = $star >= 4 ? '#28a745' : ($star >= 3 ? '#17a2b8' : '#dc3545');
                    @endphp
                    <div class="text-center d-flex flex-column align-items-center" style="width: 15%;">
                        <small class="text-muted mb-1">{{ $count }}</small>
                        <div class="progress progress-bar-vertical" style="width: 100%; height: 60px; background-color: #f8f9fa; border-radius: 4px; display: flex; align-items: flex-end; overflow: hidden;">
                            <div class="progress-bar" style="width: 100%; height: {{ $percent }}%; background-color: {{ $color }};"></div>
                        </div>
                        <div class="font-weight-bold small mt-1">{{ $star }}★</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title">Recent Customer Feedback</h3>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Assigned Officer</th>
                            <th>Rating</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($feedbacks as $feedback)
                        <tr>
                            <td>{{ $feedback->created_at->format('d M Y, H:i') }}</td>
                            <td>
                                <strong>{{ $feedback->customer->name ?? 'N/A' }}</strong>
                                <div class="small text-muted">{{ $feedback->customer->branch->name ?? '' }}</div>
                            </td>
                            <td>{{ $feedback->officer->name ?? 'Unassigned' }}</td>
                            <td>
                                <div class="star-rating">
                                    @for($i=1; $i<=5; $i++)
                                        <i class="fa {{ $i <= $feedback->rating ? 'fa-star' : 'fa-star-o' }}"></i>
                                    @endfor
                                </div>
                                <small class="text-muted">({{ $feedback->rating }}/5)</small>
                            </td>
                            <td>
                                @if($feedback->comment)
                                    <div class="feedback-comment">
                                        "{{ $feedback->comment }}"
                                    </div>
                                @else
                                    <span class="text-muted small">No written comment provided</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center p-5">
                                <i class="fa fa-frown-o fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No survey feedback received yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $feedbacks->links() }}
            </div>
        </div>
    </div>
</div>

@endsection
