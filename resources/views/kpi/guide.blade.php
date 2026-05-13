@extends('layouts.vali')

@section('title', 'KPI Points Guide')

@section('page_icon')
<i class="fa fa-info-circle"></i>
@endsection

@section('subtitle')
Official TRUMARK CRM points structure and performance rules
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        
        <div class="alert alert-info">
            <h5 class="mb-0"><i class="fa fa-trophy mr-2"></i> How to earn points</h5>
            <p class="mb-0 mt-2">Your performance is tracked automatically based on the actions you take in the CRM. The system calculates your total points to determine your monthly performance level.</p>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="tile">
                    <h3 class="tile-title text-success"><i class="fa fa-plus-circle"></i> Earning Points</h3>
                    <table class="table table-hover table-bordered table-sm mt-3">
                        <thead class="bg-light">
                            <tr>
                                <th>Action</th>
                                <th class="text-center">Points</th>
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

            <div class="col-md-6">
                <div class="tile">
                    <h3 class="tile-title text-danger"><i class="fa fa-minus-circle"></i> Losing Points (Discipline)</h3>
                    <table class="table table-hover table-bordered table-sm mt-3">
                        <thead class="bg-light">
                            <tr>
                                <th>Action</th>
                                <th class="text-center">Points</th>
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

                <div class="tile mt-4">
                    <h3 class="tile-title text-primary"><i class="fa fa-star"></i> Monthly Performance Levels</h3>
                    <table class="table table-hover table-sm mt-3">
                        <tbody>
                            <tr><td><i class="fa fa-exclamation-triangle" style="color:#dc3545;"></i> 0 – 8000</td><td><b>Needs Improvement</b></td></tr>
                            <tr><td><i class="fa fa-smile-o" style="color:#17a2b8;"></i> 8100 – 15000</td><td><b>Fair Performance</b></td></tr>
                            <tr><td><i class="fa fa-thumbs-up" style="color:#007bff;"></i> 15100 – 25000</td><td><b>Good Performer</b></td></tr>
                            <tr><td><i class="fa fa-star" style="color:#28a745;"></i> 25100 – 35000</td><td><b>Excellent Performer</b></td></tr>
                            <tr><td><i class="fa fa-trophy" style="color:#d4af37;"></i> 35000+</td><td><b style="color:#d4af37;">Sales Champion</b></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
    </div>
</div>
@endsection
