@extends('layouts.vali')

@section('title', 'KPI Points Guide')

@section('page_icon', 'fa-info-circle')

@section('subtitle')
Official TRUMARK CRM points structure and performance rules
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
    
    /* Glassmorphism alert style */
    .alert-info {
        background: rgba(23, 162, 184, 0.05) !important;
        border-color: rgba(23, 162, 184, 0.2) !important;
        color: #117a8b !important;
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    /* Spinning Trophy / Star Micro-animation */
    @keyframes spinCustom {
        0% { transform: rotate(0deg); }
        10% { transform: rotate(15deg); }
        20% { transform: rotate(-15deg); }
        30% { transform: rotate(0deg); }
        100% { transform: rotate(0deg); }
    }
    .animate-spin-custom {
        display: inline-block;
        animation: spinCustom 3s ease-in-out infinite;
    }
    
    @media (max-width: 767px) {
        .tile-title {
            font-size: 16px !important;
        }
        .table th, .table td {
            font-size: 12px !important;
            padding: 8px 6px !important;
        }
        .alert-info h5 {
            font-size: 14px !important;
        }
        .alert-info p {
            font-size: 12px !important;
        }
    }
</style>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 col-12">
        
        <div class="alert alert-info border p-3 mb-4 shadow-sm">
            <h5 class="mb-0 font-weight-bold"><i class="fa fa-trophy mr-2 animate-spin-custom text-warning"></i> How to earn points</h5>
            <p class="mb-0 mt-2">Your performance is tracked automatically based on the actions you take in the CRM. The system calculates your total points to determine your monthly performance level.</p>
        </div>

        <div class="row">
            <div class="col-md-6 col-12">
                <div class="tile p-3 shadow-sm border-0">
                    <h3 class="tile-title text-success mb-3 font-weight-bold"><i class="fa fa-plus-circle mr-2"></i> Earning Points</h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm mt-2">
                            <thead class="bg-light text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                                <tr>
                                    <th>Action</th>
                                    <th class="text-center" style="width: 80px;">Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Register new potential customer in CRM</td><td class="text-center text-success font-weight-bold">+{{ $points['REG_NEW_POTENTIAL'] ?? 5 }}</td></tr>
                                <tr><td>Complete customer profile with full details</td><td class="text-center text-success font-weight-bold">+{{ $points['COMPLETE_PROFILE'] ?? 3 }}</td></tr>
                                <tr><td>Daily customer follow-up call</td><td class="text-center text-success font-weight-bold">+{{ $points['DAILY_FOLLOWUP'] ?? 2 }}</td></tr>
                                <tr><td>Physical customer visit (Manager Adjusted)</td><td class="text-center text-success font-weight-bold">+{{ $points['PHYSICAL_VISIT'] ?? 5 }}</td></tr>
                                <tr><td>Successful quotation sent</td><td class="text-center text-success font-weight-bold">+{{ $points['QUOTATION_SENT'] ?? 4 }}</td></tr>
                                <tr><td>New customer order/sale closed</td><td class="text-center text-success font-weight-bold">+{{ $points['SALE_CLOSED_NEW'] ?? 15 }}</td></tr>
                                <tr><td>Repeat customer sale</td><td class="text-center text-success font-weight-bold">+{{ $points['SALE_CLOSED_REPEAT'] ?? 10 }}</td></tr>
                                <tr><td>High-value sale above target</td><td class="text-center text-success font-weight-bold">+{{ $points['SALE_HIGH_VALUE'] ?? 20 }}</td></tr>
                                <tr><td>Customer payment collected on time (Delivered)</td><td class="text-center text-success font-weight-bold">+{{ $points['PAYMENT_COLLECTED'] ?? 8 }}</td></tr>
                                <tr><td>Receive positive customer feedback</td><td class="text-center text-success font-weight-bold">+{{ $points['POSITIVE_FEEDBACK'] ?? 5 }}</td></tr>
                                <tr><td>Introduce new school/company lead</td><td class="text-center text-success font-weight-bold">+{{ $points['NEW_BRANCH_LEAD'] ?? 10 }}</td></tr>
                                <tr><td>Update CRM daily before closing time</td><td class="text-center text-success font-weight-bold">+{{ $points['DAILY_UPDATE_BEFORE_6'] ?? 3 }}</td></tr>
                                <tr><td>Attend sales meeting on time (Manager Adjusted)</td><td class="text-center text-success font-weight-bold">+{{ $points['MEETING_ON_TIME'] ?? 2 }}</td></tr>
                                <tr><td>Submit weekly sales report (Manager Adjusted)</td><td class="text-center text-success font-weight-bold">+{{ $points['WEEKLY_REPORT'] ?? 5 }}</td></tr>
                                <tr><td>Recover inactive customer</td><td class="text-center text-success font-weight-bold">+{{ $points['RECOVER_INACTIVE'] ?? 12 }}</td></tr>
                                <tr><td>Up-selling additional products</td><td class="text-center text-success font-weight-bold">+{{ $points['UPSELLING'] ?? 6 }}</td></tr>
                                <tr><td>Referral from existing customer</td><td class="text-center text-success font-weight-bold">+{{ $points['REFERRAL_EXISTING'] ?? 8 }}</td></tr>
                                <tr><td>Social media lead converted to sale</td><td class="text-center text-success font-weight-bold">+{{ $points['SOCIAL_MEDIA_CONV'] ?? 7 }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-12">
                <div class="tile p-3 shadow-sm border-0">
                    <h3 class="tile-title text-danger mb-3 font-weight-bold"><i class="fa fa-minus-circle mr-2"></i> Losing Points (Discipline)</h3>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-sm mt-2">
                            <thead class="bg-light text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                                <tr>
                                    <th>Action</th>
                                    <th class="text-center" style="width: 80px;">Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Late CRM update (After 6 PM)</td><td class="text-center text-danger font-weight-bold">{{ $points['LATE_UPDATE'] ?? -3 }}</td></tr>
                                <tr><td>Missed customer follow-up</td><td class="text-center text-danger font-weight-bold">{{ $points['MISSED_FOLLOWUP'] ?? -5 }}</td></tr>
                                <tr><td>Customer complaint due to poor service</td><td class="text-center text-danger font-weight-bold">{{ $points['CUSTOMER_COMPLAINT'] ?? -10 }}</td></tr>
                                <tr><td>Fake or incomplete data (Manager Adjusted)</td><td class="text-center text-danger font-weight-bold">{{ $points['FAKE_DATA'] ?? -15 }}</td></tr>
                                <tr><td>Missing sales meeting without reason (Manager Adjusted)</td><td class="text-center text-danger font-weight-bold">{{ $points['MISSED_MEETING'] ?? -5 }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tile p-3 mt-4 shadow-sm border-0" style="border-radius: 10px;">
                    <h3 class="tile-title text-primary mb-3 font-weight-bold"><i class="fa fa-star mr-2 text-warning animate-spin-custom"></i> Monthly Performance Levels</h3>
                    <div class="table-responsive">
                        <table class="table table-hover mt-2">
                            <thead class="bg-light text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                                <tr>
                                    <th>Range</th>
                                    <th>Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $settings = \App\Models\SystemSetting::pluck('value', 'key');
                                    $champion = (int)($settings['kpi_level_champion'] ?? 35000);
                                    $excellent = (int)($settings['kpi_level_excellent'] ?? 25100);
                                    $good = (int)($settings['kpi_level_good'] ?? 15100);
                                    $fair = (int)($settings['kpi_level_fair'] ?? 8100);
                                @endphp
                                <tr>
                                    <td><span class="badge badge-pill shadow-xs" style="background: #dc3545; color: white; padding: 6px 12px; font-size: 11px;"><i class="fa fa-exclamation-triangle mr-1"></i> 0 – {{ number_format($fair - 1) }}</span></td>
                                    <td><b class="text-danger" style="font-size: 13px;">Needs Improvement</b></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-pill shadow-xs" style="background: #17a2b8; color: white; padding: 6px 12px; font-size: 11px;"><i class="fa fa-smile-o mr-1"></i> {{ number_format($fair) }} – {{ number_format($good - 1) }}</span></td>
                                    <td><b class="text-info" style="font-size: 13px;">Fair Performance</b></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-pill shadow-xs" style="background: #007bff; color: white; padding: 6px 12px; font-size: 11px;"><i class="fa fa-thumbs-up mr-1"></i> {{ number_format($good) }} – {{ number_format($excellent - 1) }}</span></td>
                                    <td><b class="text-primary" style="font-size: 13px;">Good Performer</b></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-pill shadow-xs" style="background: #28a745; color: white; padding: 6px 12px; font-size: 11px;"><i class="fa fa-star mr-1"></i> {{ number_format($excellent) }} – {{ number_format($champion - 1) }}</span></td>
                                    <td><b class="text-success" style="font-size: 13px;">Excellent Performer</b></td>
                                </tr>
                                <tr>
                                    <td><span class="badge badge-pill shadow-xs" style="background: #d4af37; color: white; padding: 6px 12px; font-size: 11px;"><i class="fa fa-trophy mr-1"></i> {{ number_format($champion) }}+</span></td>
                                    <td><b style="color:#d4af37; font-size: 13px;">Sales Champion</b></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>
@endsection
