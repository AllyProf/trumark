@extends('layouts.vali')

@section('title', 'KPI Leaderboard')

@section('page_icon')
<i class="fa fa-trophy"></i>
@endsection

@section('subtitle')
Monthly and all-time staff performance rankings
@endsection

@section('styles')
<style>
    .table td, .table th { vertical-align: middle !important; }
    
    /* Animated Progress Bar */
    .progress-bar-animated {
        animation: progress-bar-stripes 1s linear infinite !important;
    }
    
    .rank-number {
        font-size: 20px;
        font-weight: 900;
        color: #555;
    }
    .rank-1 { color: #d4af37; font-size: 24px; }
    .rank-2 { color: #9da1a4; font-size: 22px; }
    .rank-3 { color: #cd7f32; font-size: 20px; }
    
    .staff-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #f8f9fa;
        border: 2px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        color: #940000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    /* ── MOBILE RESPONSIVE ─────────────────────────────────── */
    @media (max-width: 767px) {
        /* Filter bar: all dropdowns full width, stacked */
        #filterForm .row > [class*="col-md-"] {
            flex: 0 0 100% !important;
            max-width: 100% !important;
            margin-bottom: 8px;
        }
        /* Force Select2 elements to full width */
        #filterForm .select2-container {
            width: 100% !important;
            max-width: 100% !important;
        }
        #filterForm .select2-selection {
            width: 100% !important;
        }
        /* Select2 dropdown popup clamped to viewport */
        .select2-dropdown {
            max-width: calc(100vw - 20px) !important;
        }
        /* Clear Filters + badges: stack left-aligned */
        #filterForm .col-md-4.text-right {
            text-align: left !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
        #filterForm .d-flex.justify-content-end {
            justify-content: flex-start !important;
            flex-wrap: wrap;
            gap: 4px;
        }

        /* Champion spotlight: stack vertically */
        .p-3 .d-flex.align-items-center {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px;
        }
        .p-3 .ml-auto { margin-left: 0 !important; }
        .p-3 .mr-4 { margin-right: 0 !important; }

        /* Table heading and section */
        .p-4 h4 { font-size: 14px !important; }

        /* Table: compact */
        #leaderboardTable th, #leaderboardTable td {
            font-size: 11px !important;
            padding: 6px 5px !important;
            white-space: nowrap;
        }
        /* Staff name in table */
        #leaderboardTable b[style] { font-size: 13px !important; }

        /* Rank number smaller */
        .rank-number { font-size: 15px !important; }
        .rank-1 { font-size: 18px !important; }
        .rank-2 { font-size: 16px !important; }
        .rank-3 { font-size: 15px !important; }

        /* Staff avatar smaller */
        .staff-avatar { width: 35px !important; height: 35px !important; }

        /* Progress bar section: hide "points to next level" */
        #leaderboardTable .font-italic { display: none !important; }

        /* Monthly/lifetime points: smaller */
        #leaderboardTable h3 { font-size: 16px !important; }
        #leaderboardTable h4 { font-size: 14px !important; }
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="tile p-0 shadow-sm border-0 mb-3" style="overflow: hidden;">
            {{-- Unified Header Section --}}
            <div class="p-3 bg-light border-bottom">
                <form action="{{ route('kpi.leaderboard') }}" method="GET" id="filterForm">
                    <div class="row align-items-end">
                        @if(auth()->user()->role === 'super_admin')
                        <div class="col-md-2">
                            <label class="font-weight-bold small">BRANCH:</label>
                            <select name="branch_id" class="form-control select2" onchange="this.form.submit()">
                                <option value="">Global (All)</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        
                        @if(auth()->user()->role !== 'sales_officer')
                        <div class="col-md-2">
                            <label class="font-weight-bold small">OFFICER:</label>
                            <select name="user_id" class="form-control select2" onchange="this.form.submit()">
                                <option value="">All Officers</option>
                                @foreach($allStaff as $s)
                                    <option value="{{ $s->id }}" {{ request('user_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="col-md-2">
                            <label class="font-weight-bold small">MONTH:</label>
                            <select name="month" class="form-control select2" onchange="this.form.submit()">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="font-weight-bold small">YEAR:</label>
                            <select name="year" class="form-control select2" onchange="this.form.submit()">
                                @foreach(range(now()->year, now()->year - 2) as $y)
                                    <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 text-right">
                            @if(auth()->user()->role !== 'sales_officer')
                                <a href="{{ route('kpi.leaderboard') }}" class="btn btn-sm btn-outline-secondary mb-2 mr-2">
                                    <i class="fa fa-refresh"></i> Clear Filters
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Last Month Champion Spotlight --}}
            @if($lastMonthWinner)
            <div class="p-3 mb-0" style="background: linear-gradient(135deg, #fffcf0 0%, #fff9e6 100%); border-bottom: 2px solid #ffecb3;">
                <div class="d-flex align-items-center">
                    <div class="mr-4 text-center">
                        <i class="fa fa-trophy fa-3x" style="color: #d4af37; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-uppercase font-weight-bold" style="color: #856404; letter-spacing: 1px;">
                            <i class="fa fa-star mr-1"></i> Champion of {{ now()->subMonth()->format('F Y') }}
                        </h6>
                        <h4 class="mb-0 font-weight-bold" style="color: #333;">
                            {{ strtoupper($lastMonthWinner->name) }} 
                            <span class="badge badge-pill ml-2" style="background: {{ $lastMonthWinner->kpi_level['color'] }}; color: white; font-size: 12px; vertical-align: middle;">
                                {{ strtoupper($lastMonthWinner->kpi_level['name']) }}
                            </span>
                        </h4>
                        <p class="mb-0 text-muted small">Achieved a record <b>{{ number_format($lastMonthWinner->last_month_points) }} points</b> last month.</p>
                    </div>
                    <div class="ml-auto">
                        <a href="{{ route('kpi.officer_profile', $lastMonthWinner->id) }}" class="btn btn-sm btn-warning font-weight-bold shadow-sm">
                            View Champion Profile
                        </a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Main Ranking Section --}}
            <div class="p-4 bg-white">
                <div class="text-center mb-4">
                    <h4 class="font-weight-bold text-uppercase"><i class="fa fa-line-chart text-primary mr-2"></i> {{ auth()->user()->role === 'sales_officer' ? 'My KPI Ranking Status' : 'Staff Performance Ranking' }}: {{ date('F', mktime(0, 0, 0, request('month', now()->month), 1)) }} {{ request('year', now()->year) }}</h4>
                    <hr style="width: 100px; border-top: 3px solid #940000; margin-top: 5px;">
                    
                    <div class="d-flex align-items-center justify-content-center flex-wrap mt-3">
                        <span class="badge badge-pill mr-2 mb-1 shadow-sm" style="background: #dc3545; color: white; padding: 6px 12px; font-size: 11px;">0-8000: Imp.</span>
                        <span class="badge badge-pill mr-2 mb-1 shadow-sm" style="background: #17a2b8; color: white; padding: 6px 12px; font-size: 11px;">8100-15000: Fair</span>
                        <span class="badge badge-pill mr-2 mb-1 shadow-sm" style="background: #007bff; color: white; padding: 6px 12px; font-size: 11px;">15100-25000: Good</span>
                        <span class="badge badge-pill mr-2 mb-1 shadow-sm" style="background: #28a745; color: white; padding: 6px 12px; font-size: 11px;">25100-35000: Exc.</span>
                        <span class="badge badge-pill mb-1 shadow-sm" style="background: #d4af37; color: white; padding: 6px 12px; font-size: 11px;">35000+: Champ</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered" id="leaderboardTable">
                        <thead class="bg-light text-uppercase" style="font-size: 11px; letter-spacing: 1px; color: #555;">
                            <tr>
                                <th class="text-center" style="width: 80px;">Rank</th>
                                <th>Sales Officer & Branch</th>
                                <th style="width: 35%;">Monthly Progress (Target: 35,000)</th>
                                <th class="text-center" style="width: 120px;">This Month's Points</th>
                                <th class="text-center" style="width: 120px;">Lifetime</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($staff as $i => $s)
                            <tr>
                                <td class="text-center align-middle">
                                    <span class="rank-number rank-{{ $i + 1 }}">#{{ $i + 1 }}</span>
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <div class="staff-avatar mr-3">
                                            @if($s->profile_picture)
                                                <img src="{{ asset('storage/'.$s->profile_picture) }}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                                            @else
                                                <i class="fa fa-user-circle-o fa-2x"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <b class="d-block" style="font-size: 16px; color: #333;">{{ strtoupper($s->name) }}</b>
                                            <small class="text-muted"><i class="fa fa-map-marker mr-1 text-primary"></i> {{ $s->branch->name ?? 'Global' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    @php 
                                        $target = 35000;
                                        $rawPercent = ($s->monthly_points / $target) * 100;
                                        $percent = min(100, number_format($rawPercent, 1));
                                        $barColor = $s->kpi_level['color'];
                                    @endphp
                                    <div class="mb-1 d-flex justify-content-between align-items-center">
                                        <span class="badge" style="background: {{ $barColor }}; color: white; font-size: 10px;">{{ strtoupper($s->kpi_level['name']) }}</span>
                                        <small class="font-weight-bold">{{ $percent }}%</small>
                                    </div>
                                    <div class="progress shadow-sm" style="height: 12px; border-radius: 6px; background-color: #f0f0f0;">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                                             style="width: {{ $percent }}%; background-color: {{ $barColor }};" 
                                             aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="mt-1 d-flex justify-content-between">
                                        <small class="text-muted">{{ $s->monthly_points }} points earned</small>
                                        <small class="text-muted font-italic">{{ max(0, $target - $s->monthly_points) }} to next level</small>
                                    </div>
                                </td>
                                <td class="text-center align-middle bg-light">
                                    <h3 class="mb-0 font-weight-bold text-primary">{{ number_format($s->monthly_points) }}</h3>
                                    <small class="text-muted font-weight-bold text-uppercase" style="font-size: 9px;">Points</small>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="p-1">
                                        <h4 class="mb-0 font-weight-bold text-dark">{{ number_format($s->total_points) }}</h4>
                                        <small class="text-muted text-uppercase" style="font-size: 9px;">Lifetime</small>
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    <a href="{{ route('kpi.officer_profile', $s->id) }}" class="btn btn-sm btn-info shadow-sm" title="View Full Performance">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Select",
            allowClear: true,
            width: '100%'
        });

        $('#leaderboardTable').DataTable({
            "paging": false,
            "searching": true,
            "info": false,
            "retrieve": true,
            "order": []
        });
    });
</script>
@endsection
