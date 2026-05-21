@extends('layouts.vali')

@section('title', 'Officer Performance Profile')

@section('page_icon', 'fa-user-circle')

@section('subtitle')
Detailed performance analysis for {{ $officer->name }}
@endsection

@section('content')
@php 
    $target = 35000;
    $rawPercent = ($monthlyPoints / $target) * 100;
    $percent = min(100, number_format($rawPercent, 1));
    
    // Predictive "On Track" calculation
    $daysInMonth = now()->daysInMonth;
    $dayOfMonth = now()->day;
    $expectedEndPoints = $monthlyPoints > 0 ? round(($monthlyPoints / $dayOfMonth) * $daysInMonth) : 0;
    $isOnTrack = $expectedEndPoints >= $target;
@endphp

<div class="row">
    {{-- Left Sidebar: Officer Summary --}}
    <div class="col-md-4">
        {{-- Profile Card --}}
        <div class="tile p-0 shadow-sm border-0 mb-4" style="overflow: hidden;">
            <div class="p-4 text-center text-white" style="background: linear-gradient(135deg, #940000, #5a0000);">
                <div class="mb-3 mx-auto shadow" style="width: 100px; height: 100px; border-radius: 50%; background: #fff; display: flex; align-items: center; justify-content: center; border: 4px solid rgba(255,255,255,0.3); overflow: hidden;">
                    @if($officer->avatar)
                        <img src="{{ asset('storage/'.$officer->avatar) }}" style="width: 100%; height: 100%; object-fit: cover;">
                    @else
                        <div class="bg-white text-primary d-flex align-items-center justify-content-center w-100 h-100 font-weight-bold" style="font-size: 32px; color: #940000 !important;">
                            {{ strtoupper(substr($officer->name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <h4 class="font-weight-bold mb-0 text-uppercase">{{ $officer->name }}</h4>
                <p class="mb-2 text-white-50 small">{{ $officer->branch->name ?? 'Global' }} • {{ ucfirst($officer->role) }}</p>
                
                <span class="badge" style="background: {{ $level['color'] }}; color: white; padding: 8px 16px; border-radius: 20px; font-size: 13px;">
                    <i class="fa {{ $level['icon'] }} mr-1"></i> {{ strtoupper($level['name']) }}
                </span>
            </div>
            
            <div class="p-4 bg-white">
                <div class="mb-4 text-center">
                    <h2 class="font-weight-bold mb-0 text-primary">{{ $percent }}%</h2>
                    <small class="text-muted font-weight-bold d-block mb-2 text-uppercase">Monthly Progress</small>
                    <div class="progress" style="height: 12px; border-radius: 6px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: {{ $percent }}%; background-color: {{ $level['color'] }};"></div>
                    </div>
                </div>

                {{-- Predictive Analytics --}}
                <div class="alert {{ $isOnTrack ? 'alert-success' : 'alert-warning' }} p-2 mb-4 text-center border-0 shadow-sm" style="border-radius: 10px;">
                    <i class="fa {{ $isOnTrack ? 'fa-check-circle' : 'fa-exclamation-triangle' }} mr-1"></i>
                    <small class="font-weight-bold">
                        @if($isOnTrack)
                            ON TRACK to reach {{ $expectedEndPoints }} pts!
                        @else
                            AT RISK: Predicted {{ $expectedEndPoints }} pts (Target: {{ $target }})
                        @endif
                    </small>
                </div>

                <div class="row text-center mb-4 border-top pt-3">
                    <div class="col-4 border-right">
                        <h3 class="mb-0 font-weight-bold text-success">{{ number_format($totalLeads) }}</h3>
                        <small class="text-muted text-uppercase font-weight-bold" style="font-size: 9px;">Total Leads</small>
                    </div>
                    <div class="col-4 border-right">
                        <h3 class="mb-0 font-weight-bold text-primary">{{ number_format($monthlyPoints) }}</h3>
                        <small class="text-muted text-uppercase font-weight-bold" style="font-size: 9px;">Month Pts</small>
                    </div>
                    <div class="col-4">
                        <h3 class="mb-0 font-weight-bold text-dark">{{ number_format($totalPoints) }}</h3>
                        <small class="text-muted text-uppercase font-weight-bold" style="font-size: 9px;">Life Pts</small>
                    </div>
                </div>

                {{-- Comparison to Branch Average --}}
                <div class="p-3 bg-light rounded shadow-sm">
                    <small class="font-weight-bold text-muted text-uppercase d-block mb-1">Branch Comparison</small>
                    <div class="d-flex justify-content-between align-items-end">
                        @php $diff = $monthlyPoints - round($branchAvg); @endphp
                        <div>
                            <h4 class="mb-0 font-weight-bold {{ $diff >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $diff >= 0 ? '+' : '' }}{{ $diff }}
                            </h4>
                            <small class="text-muted">vs. Branch Avg ({{ round($branchAvg) }})</small>
                        </div>
                        <i class="fa {{ $diff >= 0 ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger' }} fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Manager Notes Section --}}
        <div class="tile shadow-sm border-0">
            <h5 class="tile-title border-bottom pb-2 mb-3"><i class="fa fa-pencil-square text-danger mr-2"></i> Performance Notes</h5>
            
            <div class="notes-container mb-3" style="max-height: 400px; overflow-y: auto;">
                @forelse($notes as $note)
                    <div class="p-2 border-bottom mb-2 bg-light rounded position-relative" style="border-left: 4px solid {{ $note->type == 'warning' ? '#dc3545' : ($note->type == 'achievement' ? '#28a745' : '#17a2b8') }};">
                        @if(auth()->user()->role != 'sales_officer' && (auth()->id() == $note->manager_id || auth()->user()->role == 'super_admin'))
                            <form action="{{ route('kpi.delete_note', $note->id) }}" method="POST" class="position-absolute" style="top: 5px; right: 5px;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-link text-danger p-0" onclick="return confirm('Delete this note?')"><i class="fa fa-times-circle"></i></button>
                            </form>
                        @endif
                        <small class="d-block font-weight-bold pr-3">{{ $note->manager->name }} <span class="text-muted x-small ml-2">{{ $note->created_at->format('d M, H:i') }}</span></small>
                        <p class="mb-1 small text-dark">{{ $note->note }}</p>
                        <div class="d-flex justify-content-between">
                            <span class="badge badge-pill badge-light border x-small">{{ ucfirst($note->type) }}</span>
                            @if(!$note->is_visible_to_staff)
                                <span class="badge badge-secondary x-small"><i class="fa fa-lock"></i> Private</span>
                            @else
                                <span class="badge badge-info x-small"><i class="fa fa-eye"></i> Visible to Staff</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-3 text-muted small">No manager notes yet.</div>
                @endforelse
            </div>

            @if(auth()->user()->role != 'sales_officer')
            <div class="d-flex gap-2 mb-2">
                <button class="btn btn-sm btn-primary flex-grow-1 shadow-sm mr-1" data-toggle="modal" data-target="#noteModal">
                    <i class="fa fa-pencil"></i> Coaching Note
                </button>
                <button class="btn btn-sm btn-warning flex-grow-1 shadow-sm" data-toggle="modal" data-target="#adjustModal">
                    <i class="fa fa-plus-minus"></i> Give Points
                </button>
            </div>
            @endif
        </div>
    </div>

{{-- Adjustment Modal --}}
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('staff.adjust_kpi', $officer->id) }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title font-weight-bold">Adjust KPI Points</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Activity / Reason</label>
                        <select name="activity_code" class="form-control select2-modal" style="width: 100%" onchange="toggleCustomPointsProfile(this)" required>
                            <option value="">-- Select Activity --</option>
                            <optgroup label="Custom">
                                <option value="CUSTOM">Custom Point Adjustment (Type Points)</option>
                            </optgroup>
                            <optgroup label="Positive Achievements">
                                <option value="PHYSICAL_VISIT">Physical Visit (+5)</option>
                                <option value="POSITIVE_FEEDBACK">Positive Customer Feedback (+5)</option>
                                <option value="WEEKLY_REPORT">Weekly Report Submission (+5)</option>
                                <option value="UPSELLING">Successful Upselling (+6)</option>
                                <option value="REFERRAL_EXISTING">Customer Referral (+8)</option>
                                <option value="PAYMENT_COLLECTED">Payment Collected (+8)</option>
                                <option value="RECOVER_INACTIVE">Recovered Inactive Customer (+12)</option>
                            </optgroup>
                            <optgroup label="Discipline / Deductions">
                                <option value="LATE_UPDATE">Late Data Update (-3)</option>
                                <option value="MISSED_MEETING">Missed Branch Meeting (-5)</option>
                                <option value="MISSED_FOLLOWUP">Missed Customer Follow-up (-5)</option>
                                <option value="CUSTOMER_COMPLAINT">Valid Customer Complaint (-10)</option>
                                <option value="FAKE_DATA">Reporting Fake Data (-15)</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-group custom-points-div-profile" style="display: none;">
                        <label class="font-weight-bold">Custom Points</label>
                        <input type="number" name="points" id="points-profile" class="form-control" placeholder="e.g. 10 or -5" step="1">
                        <small class="text-muted">Use a negative number to deduct points (e.g. -10)</small>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Memo / Internal Note</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Context for this adjustment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning font-weight-bold">Apply Adjustment</button>
                </div>
            </div>
        </form>
    </div>
</div>

    {{-- Right Section: Analytics & History --}}
    <div class="col-md-8">
        {{-- Growth Trend Chart --}}
        <div class="tile shadow-sm border-0 mb-4 p-4">
            <h5 class="tile-title border-bottom pb-2 mb-4"><i class="fa fa-filter text-primary mr-2"></i> Sales Funnel Performance</h5>
            <div id="salesFunnelChart" style="min-height: 280px;"></div>
        </div>

        {{-- Activity Ledger --}}
        <div class="tile shadow-sm border-0 mb-4 p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 font-weight-bold"><i class="fa fa-history text-danger mr-2"></i> RECENT ACTIVITIES</h5>
                <a href="{{ route('kpi.activities') }}?user_id={{ $officer->id }}" class="btn btn-sm btn-link font-weight-bold">Full History</a>
            </div>
            <div class="p-0">
                <table class="table table-hover table-bordered mb-0">
                    <thead class="bg-light small font-weight-bold">
                        <tr>
                            <th>Date</th>
                            <th>Activity</th>
                            <th>Customer</th>
                            <th>By</th>
                            <th class="text-center">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $act)
                        <tr>
                            <td><small class="text-muted">{{ $act->created_at->format('d M, H:i') }}</small></td>
                            <td>
                                <span class="badge {{ $act->points >= 0 ? 'badge-success' : 'badge-danger' }}" style="font-size: 10px;">
                                    {{ strtoupper(str_replace('_', ' ', $act->activity_code)) }}
                                </span>
                            </td>
                            <td>
                                @if($act->customer)
                                    <small class="font-weight-bold text-primary">{{ $act->customer->name }}</small>
                                @else
                                    <small class="text-muted">-</small>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted font-weight-bold">
                                    @if($act->performer)
                                        {{ $act->performer->name }}
                                        <br><span class="x-small text-primary">({{ ucwords(str_replace('_', ' ', $act->performer->role)) }})</span>
                                    @else
                                        System
                                    @endif
                                </small>
                            </td>
                            <td class="text-center font-weight-bold {{ $act->points >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ $act->points > 0 ? '+' : '' }}{{ $act->points }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 bg-white border-top d-flex justify-content-center">
                {{ $activities->links() }}
            </div>
        </div>

        {{-- Assigned Leads --}}
        <div class="tile shadow-sm border-0 p-0" style="overflow: hidden;">
            <div class="p-3 bg-light border-bottom">
                <h5 class="mb-0 font-weight-bold"><i class="fa fa-users text-primary mr-2"></i> ASSIGNED PORTFOLIO ({{ $assignedPortfolio->total() }})</h5>
            </div>
            <div class="p-4">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0" id="customersTable">
                        <thead class="bg-light small font-weight-bold">
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignedPortfolio as $cust)
                            <tr>
                                <td><a href="{{ route('customers.show', $cust->id) }}" class="font-weight-bold text-dark">{{ $cust->name }}</a></td>
                                <td><span class="badge badge-pill badge-light border">{{ $cust->status }}</span></td>
                                <td><small>{{ $cust->location }}</small></td>
                                <td><small class="text-muted">{{ $cust->updated_at->diffForHumans() }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-center">
                    {{ $assignedPortfolio->appends(['portfolio_page' => request('portfolio_page')])->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Note Modal --}}
<div class="modal fade" id="noteModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('kpi.store_note') }}" method="POST">
            @csrf
            <input type="hidden" name="user_id" value="{{ $officer->id }}">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title font-weight-bold">Add Performance Note</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Note Type</label>
                        <select name="type" class="form-control" required>
                            <option value="observation">Observation / Coaching</option>
                            <option value="achievement">Achievement / Commendation</option>
                            <option value="warning">Warning / Corrective Action</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Your Observations</label>
                        <textarea name="note" class="form-control" rows="4" placeholder="Write your feedback here..." required></textarea>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_visible" class="form-check-input" id="isVisible" checked>
                        <label class="form-check-label font-weight-bold" for="isVisible">Visible to Staff Member</label>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary font-weight-bold">Save Performance Note</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script type="text/javascript" src="{{ asset('vali/js/plugins/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('vali/js/plugins/dataTables.bootstrap.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('vali/js/plugins/select2.min.js') }}"></script>
<script>
    function toggleCustomPointsProfile(select) {
        if (select.value === 'CUSTOM') {
            $('.custom-points-div-profile').slideDown();
            $('#points-profile').prop('required', true);
        } else {
            $('.custom-points-div-profile').slideUp();
            $('#points-profile').prop('required', false).val('');
        }
    }

    $(document).ready(function() {
        // Funnel Chart Implementation (Using ApexCharts like Dashboard)
        var funnelOptions = {
            series: [{ name: 'Leads', data: @json($chartData['data']) }],
            chart: { 
                type: 'bar', 
                height: 280, 
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            plotOptions: { bar: { horizontal: true, distributed: true, borderRadius: 4 } },
            colors: ['#940000', '#17a2b8', '#ffc107', '#28a745', '#dc3545', '#6c757d', '#28a745'],
            xaxis: { categories: @json($chartData['labels']) }
        };
        new ApexCharts(document.querySelector("#salesFunnelChart"), funnelOptions).render();

        // Initialize Select2 (After chart to prevent blocking)
        if ($.fn.select2) {
            $('.select2-modal').select2({
                dropdownParent: $('#adjustModal')
            });
        }
    });
</script>
@endsection
