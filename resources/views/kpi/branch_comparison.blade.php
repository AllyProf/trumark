@extends('layouts.vali')

@section('title', 'Branch Performance Comparison')

@section('page_icon', 'fa-balance-scale')

@section('subtitle')
Comparative analysis of KPI performance across all branches
@endsection

@section('content')
<div class="row">
    {{-- Branch Performance Overview Cards --}}
    @foreach($branchData as $data)
    <div class="col-md-6 col-lg-4">
        <div class="tile shadow-sm border-0 p-0 mb-4" style="overflow: hidden; border-radius: 12px;">
            <div class="p-4 text-white" style="background: linear-gradient(135deg, {{ $loop->first ? '#940000, #5a0000' : '#4e54c8, #8f94fb' }});">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="font-weight-bold mb-0 text-uppercase">{{ $data['branch']->name }}</h4>
                        <small class="text-white-50">{{ $data['officer_count'] }} Active Officers</small>
                    </div>
                    <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 40px; height: 40px; font-weight: 900;">
                        #{{ $loop->iteration }}
                    </div>
                </div>
                
                <div class="row text-center mt-4">
                    <div class="col-6 border-right">
                        <h3 class="mb-0 font-weight-bold">{{ number_format($data['total_points']) }}</h3>
                        <small class="text-white-50 x-small text-uppercase">Total Points</small>
                    </div>
                    <div class="col-6">
                        <h3 class="mb-0 font-weight-bold">{{ $data['avg_points'] }}</h3>
                        <small class="text-white-50 x-small text-uppercase">Avg per Officer</small>
                    </div>
                </div>
            </div>
            
            <div class="p-3 bg-white">
                <div class="d-flex align-items-center mb-3 p-2 bg-light rounded shadow-sm">
                    <div class="mr-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 35px; height: 35px;">
                        <i class="fa fa-trophy"></i>
                    </div>
                    <div>
                        <small class="text-muted d-block x-small font-weight-bold text-uppercase">Top Performer</small>
                        <span class="font-weight-bold text-dark">{{ $data['top_performer']->name ?? 'N/A' }}</span>
                        <span class="badge badge-success ml-1">{{ number_format($data['top_points']) }} pts</span>
                    </div>
                </div>

                {{-- Trend Sparkline Placeholder --}}
                <div id="trendChart_{{ $data['branch']->id }}" style="height: 100px;"></div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile shadow-sm border-0">
            <h3 class="tile-title border-bottom pb-2 mb-4"><i class="fa fa-table text-primary mr-2"></i> Comparative Metrics Table</h3>
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="comparisonTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Branch Name</th>
                            <th class="text-center">Staff Count</th>
                            <th class="text-center">Total Activities</th>
                            <th class="text-center">Total Points</th>
                            <th class="text-center">Efficiency (Pts/Officer)</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($branchData as $data)
                        <tr>
                            <td class="font-weight-bold">{{ $data['branch']->name }}</td>
                            <td class="text-center">{{ $data['officer_count'] }}</td>
                            <td class="text-center">{{ number_format($data['activity_count']) }}</td>
                            <td class="text-center">
                                <span class="badge badge-pill badge-primary px-3">{{ number_format($data['total_points']) }}</span>
                            </td>
                            <td class="text-center font-weight-bold text-success">{{ $data['avg_points'] }}</td>
                            <td class="text-center">
                                <a href="{{ route('kpi.leaderboard') }}?branch_id={{ $data['branch']->id }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-eye"></i> View Branch Leaders
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
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    $(document).ready(function() {
        $('#comparisonTable').DataTable({
            "order": [[ 3, "desc" ]],
            "pageLength": 10
        });

        // Initialize sparkline charts for each branch
        @foreach($branchData as $data)
        var options_{{ $data['branch']->id }} = {
            series: [{
                name: 'Growth',
                data: @json($data['trend'])
            }],
            chart: {
                type: 'area',
                height: 100,
                sparkline: { enabled: true }
            },
            stroke: { curve: 'smooth', width: 2 },
            fill: {
                opacity: 0.3,
                gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 }
            },
            colors: ['{{ $loop->first ? "#940000" : "#4e54c8" }}'],
            tooltip: {
                fixed: { enabled: false },
                x: { show: false },
                y: { title: { formatter: function() { return 'Points:'; } } },
                marker: { show: false }
            },
            xaxis: { categories: @json($months) }
        };
        new ApexCharts(document.querySelector("#trendChart_{{ $data['branch']->id }}"), options_{{ $data['branch']->id }}).render();
        @endforeach
    });
</script>
@endsection
