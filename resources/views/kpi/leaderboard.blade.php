@extends('layouts.vali')

@section('title', 'KPI Leaderboard')

@section('page_icon', 'fa-trophy')

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

    /* Custom Trophy Animation */
    @keyframes trophyBounce {
        0%, 100% { transform: translateY(0) scale(1); }
        50% { transform: translateY(-6px) scale(1.05); }
    }
    .animate-bounce-custom {
        display: inline-block;
        animation: trophyBounce 2.5s ease-in-out infinite;
    }
    
    /* ── MOBILE RESPONSIVE ─────────────────────────────────── */
    @media (max-width: 767px) {
        /* Force Select2 elements to full width and beautiful alignment */
        #filterForm .select2-container {
            width: 100% !important;
            max-width: 100% !important;
        }
        #filterForm .select2-selection {
            width: 100% !important;
            height: calc(1.5em + 0.75rem + 2px) !important;
            padding: 0.375rem 0.75rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: inherit !important;
            padding-left: 0 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 50% !important;
            transform: translateY(-50%) !important;
        }
        .select2-dropdown {
            max-width: calc(100vw - 20px) !important;
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
    <div class="col-md-12">
        <div class="tile p-0 shadow-sm border-0 mb-3" style="overflow: hidden;">
            {{-- Unified Header Section --}}
            <div class="p-3 bg-light border-bottom">
                <form action="{{ route('kpi.leaderboard') }}" method="GET" id="filterForm">
                    <div class="row align-items-end">
                        @if(auth()->user()->role === 'super_admin')
                        <div class="col-md-2 col-6 mb-2 mb-md-0">
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
                        <div class="col-md-2 col-6 mb-2 mb-md-0">
                            <label class="font-weight-bold small">OFFICER:</label>
                            <select name="user_id" class="form-control select2" onchange="this.form.submit()">
                                <option value="">All Officers</option>
                                @foreach($allStaff as $s)
                                    <option value="{{ $s->id }}" {{ request('user_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="col-md-2 col-6 mb-2 mb-md-0">
                            <label class="font-weight-bold small">MONTH:</label>
                            <select name="month" class="form-control select2" onchange="this.form.submit()">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 col-6 mb-2 mb-md-0">
                            <label class="font-weight-bold small">YEAR:</label>
                            <select name="year" class="form-control select2" onchange="this.form.submit()">
                                @foreach(range(now()->year, now()->year - 2) as $y)
                                    <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 col-12 text-md-right text-left mt-2 mt-md-0">
                            @if(auth()->user()->role !== 'sales_officer')
                                <a href="{{ route('kpi.leaderboard') }}" class="btn btn-sm btn-outline-secondary w-100 w-md-auto">
                                    <i class="fa fa-refresh mr-1"></i> Clear Filters
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Last Month Champion Spotlight --}}
            @if($lastMonthWinner)
            <div class="p-3 mb-0" style="background: linear-gradient(135deg, #fffcf0 0%, #fff9e6 100%); border-bottom: 2px solid #ffecb3;">
                <div class="d-flex align-items-center flex-column flex-md-row text-center text-md-left">
                    <div class="mr-0 mr-md-4 mb-2 mb-md-0 text-center">
                        <i class="fa fa-trophy fa-3x animate-bounce-custom" style="color: #d4af37; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1 text-uppercase font-weight-bold" style="color: #856404; letter-spacing: 1px;">
                            <i class="fa fa-star mr-1"></i> Champion of {{ now()->subMonth()->format('F Y') }}
                        </h6>
                        <h4 class="mb-0 font-weight-bold" style="color: #333;">
                            {{ strtoupper($lastMonthWinner->name) }} 
                            <span class="badge badge-pill ml-0 ml-md-2 mt-1 mt-md-0" style="background: {{ $lastMonthWinner->kpi_level['color'] }}; color: white; font-size: 12px; vertical-align: middle; display: inline-block;">
                                {{ strtoupper($lastMonthWinner->kpi_level['name']) }}
                            </span>
                        </h4>
                        <p class="mb-2 mb-md-0 text-muted small">Achieved a record <b>{{ number_format($lastMonthWinner->last_month_points) }} points</b> last month.</p>
                    </div>
                    <div class="mt-2 mt-md-0 w-100 w-md-auto">
                        <a href="{{ route('kpi.officer_profile', $lastMonthWinner->id) }}" class="btn btn-sm btn-warning font-weight-bold shadow-sm w-100 w-md-auto">
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
                    
                    @php
                        $settings = \App\Models\SystemSetting::pluck('value', 'key');
                        $champion = (int)($settings['kpi_level_champion'] ?? 35000);
                        $excellent = (int)($settings['kpi_level_excellent'] ?? 25100);
                        $good = (int)($settings['kpi_level_good'] ?? 15100);
                        $fair = (int)($settings['kpi_level_fair'] ?? 8100);
                    @endphp
                    <div class="d-flex align-items-center justify-content-center flex-wrap mt-3">
                        <span class="badge badge-pill mr-2 mb-1 shadow-sm" style="background: #dc3545; color: white; padding: 6px 12px; font-size: 11px;">0-{{ $fair - 1 }}: Imp.</span>
                        <span class="badge badge-pill mr-2 mb-1 shadow-sm" style="background: #17a2b8; color: white; padding: 6px 12px; font-size: 11px;">{{ $fair }}-{{ $good - 1 }}: Fair</span>
                        <span class="badge badge-pill mr-2 mb-1 shadow-sm" style="background: #007bff; color: white; padding: 6px 12px; font-size: 11px;">{{ $good }}-{{ $excellent - 1 }}: Good</span>
                        <span class="badge badge-pill mr-2 mb-1 shadow-sm" style="background: #28a745; color: white; padding: 6px 12px; font-size: 11px;">{{ $excellent }}-{{ $champion - 1 }}: Exc.</span>
                        <span class="badge badge-pill mb-1 shadow-sm" style="background: #d4af37; color: white; padding: 6px 12px; font-size: 11px;">{{ $champion }}+: Champ</span>
                    </div>
                </div>

                {{-- Desktop View (Traditional Table) --}}
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover table-bordered" id="leaderboardTable">
                        <thead class="bg-light text-uppercase" style="font-size: 11px; letter-spacing: 1px; color: #555;">
                            <tr>
                                <th class="text-center" style="width: 80px;">Rank</th>
                                <th>Sales Officer & Branch</th>
                                <th style="width: 35%;">Monthly Progress (Target: {{ number_format((int)($settings['kpi_level_champion'] ?? 35000)) }})</th>
                                <th class="text-center" style="width: 100px;">Leads Registered</th>
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
                                    @php
                                        $roleColor = 'badge-secondary';
                                        if($s->role == 'super_admin') $roleColor = 'badge-danger';
                                        elseif($s->role == 'manager') $roleColor = 'badge-info';
                                        elseif($s->role == 'sales_officer') $roleColor = 'badge-primary';
                                    @endphp
                                    <div class="d-flex align-items-center">
                                        <div class="staff-avatar mr-3 shadow-sm" style="width: 45px; height: 45px; min-width: 45px; border: 2px solid #940000; overflow: hidden; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            @if($s->avatar)
                                                <img src="{{ asset('storage/'.$s->avatar) }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                            @else
                                                <div class="bg-primary text-white d-flex align-items-center justify-content-center w-100 h-100 font-weight-bold" style="font-size: 14px; border-radius: 50%;">
                                                    {{ strtoupper(substr($s->name, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <b class="d-block" style="font-size: 16px; color: #333;">
                                                {{ strtoupper($s->name) }}
                                                <span class="badge {{ $roleColor }} ml-1" style="font-size: 10px; font-weight: normal; vertical-align: middle;">{{ ucwords(str_replace('_', ' ', $s->role)) }}</span>
                                            </b>
                                            <small class="text-muted"><i class="fa fa-map-marker mr-1 text-primary"></i> {{ $s->branch->name ?? 'Global' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    @php 
                                        $target = (int)($settings['kpi_level_champion'] ?? 35000);
                                        $rawPercent = ($s->monthly_points / $target) * 100;
                                        $percent = min(100, ($rawPercent > 0 && $rawPercent < 0.1) ? number_format($rawPercent, 2) : number_format($rawPercent, 1));
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
                                <td class="text-center align-middle bg-light" style="border-right: 2px solid #fff;">
                                    <h3 class="mb-0 font-weight-bold text-success">{{ number_format($s->total_leads) }}</h3>
                                    <small class="text-muted font-weight-bold text-uppercase" style="font-size: 9px;">Leads</small>
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

                {{-- Mobile View (Premium Card List) --}}
                <div class="d-block d-md-none mobile-card-container">
                    @foreach($staff as $i => $s)
                    <div class="card shadow-sm mb-3 border-0" style="border-radius: 12px; background: #fff; border-left: 5px solid {{ $s->kpi_level['color'] }}; box-shadow: 0 4px 6px rgba(0,0,0,0.05) !important;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center mb-3">
                                <div class="rank-number rank-{{ $i + 1 }} mr-2 text-center" style="min-width: 30px;">
                                    #{{ $i + 1 }}
                                </div>
                                @php
                                    $roleColor = 'badge-secondary';
                                    if($s->role == 'super_admin') $roleColor = 'badge-danger';
                                    elseif($s->role == 'manager') $roleColor = 'badge-info';
                                    elseif($s->role == 'sales_officer') $roleColor = 'badge-primary';
                                @endphp
                                <div class="staff-avatar mr-3 shadow-sm" style="width: 40px; height: 40px; min-width: 40px; border: 2px solid #940000; overflow: hidden; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    @if($s->avatar)
                                        <img src="{{ asset('storage/'.$s->avatar) }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                                    @else
                                        <div class="bg-primary text-white d-flex align-items-center justify-content-center w-100 h-100 font-weight-bold" style="font-size: 13px; border-radius: 50%;">
                                            {{ strtoupper(substr($s->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <b class="d-block text-dark" style="font-size: 14px; line-height: 1.2;">
                                        {{ strtoupper($s->name) }}
                                        <span class="badge {{ $roleColor }} ml-1" style="font-size: 9px; font-weight: normal; vertical-align: middle;">{{ ucwords(str_replace('_', ' ', $s->role)) }}</span>
                                    </b>
                                    <small class="text-muted"><i class="fa fa-map-marker text-primary mr-1"></i> {{ $s->branch->name ?? 'Global' }}</small>
                                </div>
                                <div class="ml-auto text-right">
                                    <span class="badge" style="background: {{ $s->kpi_level['color'] }}; color: white; font-size: 9px; padding: 4px 8px; border-radius: 20px;">
                                        {{ strtoupper($s->kpi_level['name']) }}
                                    </span>
                                </div>
                            </div>
                            
                            {{-- Monthly Progress Bar --}}
                            <div class="mb-3">
                                @php 
                                    $target = (int)($settings['kpi_level_champion'] ?? 35000);
                                    $rawPercent = ($s->monthly_points / $target) * 100;
                                    $percent = min(100, ($rawPercent > 0 && $rawPercent < 0.1) ? number_format($rawPercent, 2) : number_format($rawPercent, 1));
                                    $barColor = $s->kpi_level['color'];
                                @endphp
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-muted font-weight-bold">Target Progress</span>
                                    <span class="small font-weight-bold text-dark">{{ $percent }}%</span>
                                </div>
                                <div class="progress shadow-xs" style="height: 8px; border-radius: 4px; background-color: #f0f0f0;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" 
                                         style="width: {{ $percent }}%; background-color: {{ $barColor }};" 
                                         aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                            
                            {{-- Points Breakdown Row --}}
                            <div class="row text-center bg-light py-2 rounded mb-3 mx-0">
                                <div class="col-4 border-right">
                                    <small class="text-muted d-block text-uppercase" style="font-size: 8px; letter-spacing: 0.5px;">Leads</small>
                                    <span class="h6 font-weight-bold text-success mb-0">{{ number_format($s->total_leads) }}</span>
                                </div>
                                <div class="col-4 border-right">
                                    <small class="text-muted d-block text-uppercase" style="font-size: 8px; letter-spacing: 0.5px;">This Month</small>
                                    <span class="h6 font-weight-bold text-primary mb-0">{{ number_format($s->monthly_points) }}</span>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block text-uppercase" style="font-size: 8px; letter-spacing: 0.5px;">Lifetime</small>
                                    <span class="h6 font-weight-bold text-dark mb-0">{{ number_format($s->total_points) }}</span>
                                </div>
                            </div>
                            
                            {{-- Action button --}}
                            <div>
                                <a href="{{ route('kpi.officer_profile', $s->id) }}" class="btn btn-sm btn-block btn-info shadow-xs" style="border-radius: 8px;">
                                    <i class="fa fa-eye mr-1"></i> View Full Performance Profile
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
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
