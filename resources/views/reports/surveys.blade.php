@extends('layouts.vali')

@section('title', 'Customer Satisfaction & Surveys')
@section('page_icon', 'fa-commenting-o')
@section('subtitle', 'Detailed breakdown of customer feedback, ratings, and service quality assessments')

@section('styles')
<style>
    /* ── Stars ─────────────────────────────────────────────────────── */
    .star-rating .fa-star  { color: #ffc107; }
    .star-rating .fa-star-o{ color: #e4e5e9; }

    /* ── Comment style ─────────────────────────────────────────────── */
    .feedback-comment {
        font-style: italic;
        color: #555;
        border-left: 3px solid #940000;
        padding-left: 10px;
        margin-top: 4px;
        font-size: 13px;
    }

    /* ── KPI stat cards ─────────────────────────────────────────────── */
    .kpi-stat-card {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.07);
        transition: transform .2s;
    }
    .kpi-stat-card:hover { transform: translateY(-3px); }

    /* ── Mobile survey cards ────────────────────────────────────────── */
    .survey-card {
        border-radius: 10px;
        border: none;
        border-left: 4px solid #940000;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        margin-bottom: 14px;
        transition: transform .15s;
    }
    .survey-card:hover { transform: translateY(-2px); }

    /* ── Rating colour helpers ──────────────────────────────────────── */
    .badge-excellent { background-color: #d4edda; color: #155724; }
    .badge-good      { background-color: #cce5ff; color: #004085; }
    .badge-neutral   { background-color: #fff3cd; color: #856404; }
    .badge-poor      { background-color: #f8d7da; color: #721c24; }

    /* ── Mobile canvas wrapper ──────────────────────────────────────── */
    @media (max-width: 767px) {
        .mobile-canvas {
            background: #f4f6f9;
            padding: 10px 4px;
            border-radius: 8px;
        }
        .filter-bar .form-control { font-size: 13px; }
        .filter-bar label { font-size: 11px; font-weight: 600; color: #555; }
    }
</style>
@endsection

@section('content')

{{-- ── Filter & Action Bar ──────────────────────────────────────────── --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="tile p-3 shadow-sm">
            <form action="{{ route('reports.surveys') }}" method="GET" class="filter-bar">
                <div class="row align-items-end">

                    @if(auth()->user()->role === 'super_admin')
                    <div class="col-12 col-md-4 mb-3 mb-md-0">
                        <label><i class="fa fa-map-marker text-primary mr-1"></i> Branch</label>
                        <select name="branch_id" class="form-control" onchange="this.form.submit()">
                            <option value="">🌍 Global (All Branches)</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>
                                    📍 {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if(auth()->user()->role !== 'sales_officer')
                    <div class="col-12 col-md-4 mb-3 mb-md-0">
                        <label><i class="fa fa-user text-primary mr-1"></i> Staff Member</label>
                        <select name="staff_id" class="form-control" onchange="this.form.submit()">
                            <option value="">👥 All Staff Members</option>
                            @foreach($officers as $officer)
                                <option value="{{ $officer->id }}" {{ $staffId == $officer->id ? 'selected' : '' }}>
                                    👤 {{ $officer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-12 col-md-4 text-md-right mt-2 mt-md-0" style="align-self: flex-end;">
                        <a href="{{ route('reports.surveys') }}" class="btn btn-outline-secondary btn-sm w-100 w-md-auto" style="max-width: 180px;">
                            <i class="fa fa-refresh mr-1"></i> Reset Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── KPI Summary Cards ─────────────────────────────────────────────── --}}
<div class="row mb-4">
    {{-- Average Rating --}}
    <div class="col-6 col-md-3 mb-3 mb-md-0">
        <div class="tile kpi-stat-card p-3 text-center h-100" style="border-top: 4px solid #940000;">
            <i class="fa fa-star fa-2x text-warning mb-2"></i>
            <h6 class="text-muted text-uppercase small font-weight-bold">Avg Rating</h6>
            <h2 class="mb-1" style="font-size: 1.8rem;">{{ number_format($avgRating, 1) }}<small class="text-muted" style="font-size:.9rem;"> /5</small></h2>
            <div class="star-rating" style="font-size: 13px;">
                @for($i=1; $i<=5; $i++)
                    <i class="fa {{ $i <= round($avgRating) ? 'fa-star' : 'fa-star-o' }}"></i>
                @endfor
            </div>
        </div>
    </div>

    {{-- Total Surveys --}}
    <div class="col-6 col-md-3 mb-3 mb-md-0">
        <div class="tile kpi-stat-card p-3 text-center h-100" style="border-top: 4px solid #17a2b8;">
            <i class="fa fa-comments fa-2x text-info mb-2"></i>
            <h6 class="text-muted text-uppercase small font-weight-bold">Total Surveys</h6>
            <h2 class="mb-0" style="font-size: 1.8rem;">{{ number_format($totalFeedbacks) }}</h2>
            <small class="text-muted">Responses Received</small>
        </div>
    </div>

    {{-- Rating Distribution --}}
    <div class="col-12 col-md-6 mt-0 mt-md-0">
        <div class="tile kpi-stat-card p-3 h-100">
            <h6 class="text-muted text-uppercase small font-weight-bold mb-2">
                <i class="fa fa-bar-chart mr-1"></i> Rating Distribution
            </h6>
            <div class="d-flex align-items-end justify-content-around pb-1" style="height: 90px;">
                @foreach([5, 4, 3, 2, 1] as $star)
                    @php
                        $count   = $ratingBreakdown[$star] ?? 0;
                        $percent = $totalFeedbacks > 0 ? ($count / $totalFeedbacks) * 100 : 0;
                        $color   = $star >= 4 ? '#28a745' : ($star >= 3 ? '#17a2b8' : '#dc3545');
                    @endphp
                    <div class="text-center d-flex flex-column align-items-center" style="width:18%;">
                        <small class="text-muted mb-1" style="font-size:10px;">{{ $count }}</small>
                        <div style="width:100%;height:60px;background:#f0f0f0;border-radius:4px;display:flex;align-items:flex-end;overflow:hidden;">
                            <div style="width:100%;height:{{ $percent }}%;background:{{ $color }};transition:height .4s;"></div>
                        </div>
                        <div class="font-weight-bold mt-1" style="font-size:11px;">{{ $star }}★</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ── Feedback Table (Desktop) + Cards (Mobile) ───────────────────── --}}
<div class="row">
    <div class="col-12">
        <div class="tile">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <h3 class="tile-title mb-0">Recent Customer Feedback</h3>
                <small class="text-muted">{{ $feedbacks->total() }} total responses</small>
            </div>

            {{-- Desktop table --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover table-bordered" style="font-size: 13px;">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:13%;">Date</th>
                            <th style="width:20%;">Customer</th>
                            <th style="width:18%;">Assigned Officer</th>
                            <th style="width:16%;">Rating</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($feedbacks as $feedback)
                        @php
                            $r = $feedback->rating;
                            $rClass = $r >= 5 ? 'badge-excellent' : ($r >= 4 ? 'badge-good' : ($r >= 3 ? 'badge-neutral' : 'badge-poor'));
                        @endphp
                        <tr>
                            <td>
                                <small class="d-block font-weight-bold">{{ $feedback->created_at->format('d M Y') }}</small>
                                <small class="text-muted">{{ $feedback->created_at->format('H:i') }}</small>
                            </td>
                            <td>
                                <strong>{{ $feedback->customer->name ?? 'N/A' }}</strong>
                                <div class="small text-muted">{{ $feedback->customer->branch->name ?? '' }}</div>
                            </td>
                            <td>{{ $feedback->officer->name ?? 'Unassigned' }}</td>
                            <td>
                                <div class="star-rating mb-1">
                                    @for($i=1; $i<=5; $i++)
                                        <i class="fa {{ $i <= $feedback->rating ? 'fa-star' : 'fa-star-o' }}"></i>
                                    @endfor
                                </div>
                                <span class="badge {{ $rClass }}" style="font-size:10px;border-radius:10px;padding:2px 8px;">
                                    {{ $r }}/5
                                </span>
                            </td>
                            <td>
                                @if($feedback->comment)
                                    <div class="feedback-comment">"{{ $feedback->comment }}"</div>
                                @else
                                    <span class="text-muted small font-italic">No written comment provided</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center p-5">
                                <i class="fa fa-frown-o fa-3x text-muted d-block mb-2"></i>
                                <p class="text-muted mb-0">No survey feedback received yet.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="d-block d-md-none mobile-canvas">
                @forelse($feedbacks as $feedback)
                @php
                    $r = $feedback->rating;
                    $rClass = $r >= 5 ? 'badge-excellent' : ($r >= 4 ? 'badge-good' : ($r >= 3 ? 'badge-neutral' : 'badge-poor'));
                    $borderColor = $r >= 4 ? '#28a745' : ($r >= 3 ? '#ffc107' : '#dc3545');
                @endphp
                <div class="card survey-card" style="border-left-color: {{ $borderColor }};">
                    <div class="card-body p-3">
                        {{-- Header: customer name + date --}}
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong style="font-size:14px; color:#333;">{{ $feedback->customer->name ?? 'N/A' }}</strong>
                                @if($feedback->customer->branch->name ?? false)
                                    <span class="badge badge-light border text-muted d-inline-block ml-1" style="font-size:9px;">
                                        {{ $feedback->customer->branch->name }}
                                    </span>
                                @endif
                            </div>
                            <small class="text-muted font-weight-bold" style="white-space:nowrap;font-size:10px;">
                                {{ $feedback->created_at->format('d M Y') }}
                            </small>
                        </div>

                        {{-- Stars + Score --}}
                        <div class="d-flex align-items-center mb-2">
                            <div class="star-rating mr-2" style="font-size:14px;">
                                @for($i=1; $i<=5; $i++)
                                    <i class="fa {{ $i <= $feedback->rating ? 'fa-star' : 'fa-star-o' }}"></i>
                                @endfor
                            </div>
                            <span class="badge {{ $rClass }}" style="font-size:10px;border-radius:10px;padding:3px 10px;font-weight:700;">
                                {{ $r }}/5
                            </span>
                        </div>

                        {{-- Comment --}}
                        @if($feedback->comment)
                            <div class="feedback-comment mb-2">"{{ $feedback->comment }}"</div>
                        @else
                            <small class="text-muted font-italic">No written comment.</small>
                        @endif

                        {{-- Footer: officer --}}
                        <div class="pt-2 mt-2 border-top">
                            <small class="text-muted"><i class="fa fa-user mr-1"></i> Officer: <strong>{{ $feedback->officer->name ?? 'Unassigned' }}</strong></small>
                            <small class="text-muted d-block"><i class="fa fa-clock-o mr-1"></i> {{ $feedback->created_at->format('H:i') }}</small>
                        </div>
                    </div>
                </div>
                @empty
                <div class="card p-4 text-center border text-muted">
                    <i class="fa fa-frown-o fa-2x mb-2 text-light"></i>
                    No survey feedback received yet.
                </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            <div class="d-flex justify-content-between align-items-center flex-wrap mt-4 pt-3 border-top">
                <small class="text-muted font-weight-bold mb-2 mb-md-0">
                    Showing {{ $feedbacks->firstItem() ?: 0 }} – {{ $feedbacks->lastItem() ?: 0 }} of {{ $feedbacks->total() }} responses
                </small>
                <div>{{ $feedbacks->links() }}</div>
            </div>
        </div>
    </div>
</div>

@endsection
