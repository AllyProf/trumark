@extends('layouts.vali')

@section('title', 'Lead Registration')

@section('page_icon', 'fa-user-plus')

@section('subtitle')
TRUMARK Official Customer Registration - Advanced Tile Selection & Categorized Review
@endsection

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    :root { --primary-brand: #940000; --brand-light: #fff5f5; }
    .wizard-steps { display: flex; justify-content: space-between; margin-bottom: 40px; position: relative; }
    .wizard-steps::before { content: ''; position: absolute; top: 20px; left: 0; width: 100%; height: 2px; background: #eee; z-index: 1; }
    .step { width: 45px; height: 45px; border-radius: 50%; background: #eee; line-height: 45px; text-align: center; z-index: 2; font-weight: bold; color: #999; border: 3px solid #fff; transition: all 0.3s; }
    .step.active { background: var(--primary-brand); color: #fff; transform: scale(1.1); box-shadow: 0 0 15px rgba(148, 0, 0, 0.2); }
    .step.completed { background: var(--primary-brand); color: #fff; }
    .step-label { position: absolute; top: 50px; font-size: 9px; width: 80px; margin-left: -18px; text-align: center; color: #666; font-weight: 700; text-transform: uppercase; }
    .form-section { display: none; }
    .form-section.active { display: block; animation: slideUp 0.4s ease-out; }
    @keyframes slideUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
    .select2-container--default .select2-selection--single { height: 40px; border: 1px solid #ced4da; border-radius: 4px; padding-top: 5px; }
    
    /* Requirement Tiles */
    .req-tile { border: 2px solid #eee; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s; height: 100%; position: relative; background: #fff; }
    .req-tile:hover { border-color: #ddd; transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .req-tile.active { border-color: var(--primary-brand); background: var(--brand-light); box-shadow: 0 0 10px rgba(148, 0, 0, 0.1); }
    .req-tile i { font-size: 28px; margin-bottom: 12px; color: var(--primary-brand); transition: all 0.3s; }
    .req-tile span { display: block; font-weight: 700; font-size: 11px; color: #333; text-transform: uppercase; letter-spacing: 0.5px; }
    .req-check { position: absolute; top: 10px; right: 10px; color: var(--primary-brand); display: none; font-size: 18px !important; }
    .req-tile.active .req-check { display: block; }

    .req-check { position: absolute; top: 10px; right: 10px; color: var(--primary-brand); display: none; font-size: 18px !important; }
    .req-tile.active .req-check { display: block; }
    
    .mandatory-label::after { content: " *"; color: var(--primary-brand); }
    .btn-success { background-color: var(--primary-brand); border-color: var(--primary-brand); }
    .btn-success:hover { background-color: #7a0000; border-color: #7a0000; }

    /* ── MOBILE RESPONSIVE ─────────────────────────────────── */
    @media (max-width: 767px) {
        /* Wizard step bar: smaller circles */
        .wizard-steps { margin-bottom: 55px; padding: 0 5px !important; }
        .wizard-steps::before { top: 17px; }
        .step {
            width: 34px; height: 34px;
            line-height: 34px; font-size: 12px;
            border-width: 2px;
        }
        .step-label {
            font-size: 7.5px;
            width: 55px;
            margin-left: -10px;
            top: 40px;
        }

        /* Tile padding */
        .tile { padding: 14px 10px !important; }

        /* Section headings */
        .tile-title { font-size: 14px !important; }

        /* Requirement tiles: 2 per row */
        #step3 .row > [class*="col-md-4"] {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }
        .req-tile { padding: 12px 8px !important; }
        .req-tile i { font-size: 22px !important; margin-bottom: 8px !important; }
        .req-tile span { font-size: 10px !important; }

        /* Form columns: stack full width */
        [class*="col-md-4"],
        [class*="col-md-6"],
        [class*="col-xl-11"] {
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        /* Navigation buttons — keep side by side */
        .next-step, .prev-step, .btn-success {
            padding: 8px 16px !important;
            font-size: 13px !important;
        }
        .form-section .d-flex.justify-content-between {
            flex-direction: row !important;
            align-items: center !important;
            gap: 8px;
        }
        .form-section .d-flex.justify-content-between .btn {
            flex: 1 1 0;
            text-align: center;
        }

        /* Preview table */
        #preview-table th, #preview-table td {
            font-size: 12px !important;
            padding: 6px 8px !important;
        }

        /* Duplicate error box */
        .alert { padding: 14px !important; }
        .alert h5 { font-size: 13px !important; }
    }
</style>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-11">

        @if(session('duplicate_error'))
        @php $dup = session('duplicate_error'); @endphp
        <div class="alert" role="alert" style="background: #fff0f0; border: 2px solid #940000; border-radius: 10px; padding: 20px 25px; margin-bottom: 20px;">
            <div class="d-flex align-items-center mb-2">
                <i class="fa fa-ban fa-2x mr-3" style="color: #940000;"></i>
                <h5 class="mb-0 font-weight-bold" style="color: #940000;">⛔ Registration Blocked — Duplicate Contact Detected</h5>
            </div>
            <p class="mb-2">{{ $dup['message'] }}</p>
            <hr style="border-color: #940000; opacity: 0.2;">
            <div class="row">
                <div class="col-md-4"><small class="text-muted">Registered By</small><br><strong>{{ $dup['owner'] }}</strong></div>
                <div class="col-md-4"><small class="text-muted">Branch</small><br><strong>{{ $dup['branch'] }}</strong></div>
                <div class="col-md-4"><small class="text-muted">Matched Via</small><br><strong>{{ $dup['matched_by'] }}</strong></div>
            </div>
            <div class="mt-3">
                <a href="{{ route('customers.show', $dup['customer_id']) }}" class="btn btn-sm" style="background: #940000; color: #fff; font-weight: 700;">
                    <i class="fa fa-eye mr-1"></i> View Existing Record
                </a>
            </div>
        </div>
        @endif

        <div class="tile">
            <!-- Progress bar -->
            <div class="wizard-steps px-4">
                <div class="step active" data-step="1">1 <span class="step-label">Classification</span></div>
                <div class="step" data-step="2">2 <span class="step-label">Location</span></div>
                <div class="step" data-step="3">3 <span class="step-label">Opportunity</span></div>
                <div class="step" data-step="4">4 <span class="step-label">Tracking</span></div>
                <div class="step" data-step="5">5 <span class="step-label">Review</span></div>
            </div>

            <form id="regForm" action="{{ route('customers.store') }}" method="POST">
                @csrf
                
                <!-- STEP 1: IDENTITY -->
                <div class="form-section active" id="step1">
                    <h3 class="tile-title" style="color: var(--primary-brand)"><i class="fa fa-id-card mr-2"></i> Section 1: Identity & Classification</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">CUSTOMER STATUS</label>
                                <select name="status" class="form-control select2" data-preview="Status" required>
                                    <option value="">Select Status</option>
                                    <option value="New Customer">New Customer</option>
                                    <option value="Potential Customer">Potential Customer</option>
                                    <option value="Existing Customer">Existing Customer</option>
                                    <option value="Inactive Customer">Inactive Customer</option>
                                    <option value="VIP Customer">VIP Customer</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">CUSTOMER TYPE</label>
                                <select name="type" id="customerType" class="form-control select2" data-preview="Customer Type" required>
                                    <option value="">Select Type</option>
                                    <option value="School">School</option>
                                    <option value="Institution">Institution</option>
                                    <option value="Company / Organization">Company / Organization</option>
                                    <option value="Parent">Parent</option>
                                    <option value="Walk in">Walk in</option>
                                </select>
                            </div>
                        </div>
                        <div id="schoolTypeRow" class="col-md-4" style="display:none;">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">SCHOOL LEVEL</label>
                                <select name="school_level" class="form-control select2" data-preview="School Level">
                                    <option value="">Select Level</option>
                                    <option value="Nursery">Nursery</option>
                                    <option value="Primary">Primary</option>
                                    <option value="O-level Secondary">O-level Secondary</option>
                                    <option value="A-level Secondary">A-level Secondary</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">CUSTOMER SOURCE</label>
                                <select name="source" class="form-control select2" data-preview="Source" required>
                                    <option value="">Select Source</option>
                                    <option value="Walk-in">Walk-in Customer</option>
                                    <option value="Referral">Referral</option>
                                    <option value="WhatsApp">WhatsApp</option>
                                    <option value="Instagram">Instagram</option>
                                    <option value="Facebook">Facebook</option>
                                    <option value="Website">Website</option>
                                    <option value="Phone Call">Phone Call</option>
                                    <option value="Seminar">Seminar</option>
                                    <option value="Conference">Conference</option>
                                    <option value="Exhibitions">Exhibitions</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">CUSTOMER / ORGANIZATION NAME</label>
                                <input type="text" name="name" class="form-control" data-preview="Name" placeholder="Full official name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">CONTACT PERSON</label>
                                <input type="text" name="contact_person" class="form-control" data-preview="Contact Person" placeholder="Full name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">POSITION</label>
                                <input type="text" name="position" class="form-control" data-preview="Position" placeholder="Job title" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small">TIN / REG NO.</label>
                                <input type="text" name="tin_no" class="form-control" data-preview="TIN No" placeholder="TIN Number">
                            </div>
                        </div>
                    </div>
                    <div class="text-right mt-4">
                        <button type="button" class="btn btn-primary next-step px-5">Next: Location <i class="fa fa-arrow-right ml-2"></i></button>
                    </div>
                </div>

                <!-- STEP 2: LOCATION -->
                <div class="form-section" id="step2">
                    <h3 class="tile-title" style="color: var(--primary-brand)"><i class="fa fa-map-marker mr-2"></i> Section 2: Contact & Location</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">COUNTRY</label>
                                <select name="country" id="countrySelect" class="form-control select2" data-preview="Country" required>
                                    <!-- Populated by JS -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">PRIMARY PHONE</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text font-weight-bold" id="countryCodeDisplay" style="background: #f8f9fa;">+255</span>
                                    </div>
                                    <input type="text" id="phoneInput" name="phone_number" class="form-control" placeholder="e.g. 712345678" maxlength="9" required>
                                    <input type="hidden" name="phone" id="fullPhone">
                                </div>
                                <small class="text-muted">Enter 9 digits after country code.</small>
                                <div id="phone-feedback" class="mt-1" style="display:none;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small">ALT PHONE</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text font-weight-bold altCountryCodeDisplay" style="background: #f8f9fa;">+255</span>
                                    </div>
                                    <input type="text" id="altPhoneInput" name="alternative_phone_number" class="form-control" placeholder="Optional phone" maxlength="9">
                                    <input type="hidden" name="alternative_phone" id="fullAltPhone">
                                </div>
                                <small class="text-muted">Enter 9 digits after country code.</small>
                                <div id="alt-phone-feedback" class="mt-1" style="display:none;"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small">EMAIL</label>
                                <input type="email" id="emailInput" name="email" class="form-control" data-preview="Email" placeholder="email@domain.com">
                                <div id="email-feedback" class="mt-1" style="display:none;"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">REGION</label>
                                <select name="region" id="regionSelect" class="form-control select2" data-preview="Region" required>
                                    <option value="">Select Region</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">DISTRICT</label>
                                <select name="district" id="districtSelect" class="form-control select2" data-preview="District" required disabled>
                                    <option value="">Select District</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small">WARD / AREA</label>
                                <input type="text" name="ward" class="form-control" data-preview="Ward">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold small">PHYSICAL ADDRESS & LANDMARK</label>
                                <input type="text" name="address" class="form-control" data-preview="Address" placeholder="Physical Address">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-secondary prev-step px-4"><i class="fa fa-arrow-left mr-2"></i> Back</button>
                        <button type="button" class="btn btn-primary next-step px-5">Next: Requirements <i class="fa fa-arrow-right ml-2"></i></button>
                    </div>
                </div>

                <!-- STEP 3: REQUIREMENTS -->
                <div class="form-section" id="step3">
                    <h3 class="tile-title" style="color: var(--primary-brand)"><i class="fa fa-shopping-basket mr-2"></i> Section 3: Requirements & Value</h3>
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="font-weight-bold small d-block mb-3 mandatory-label">SELECT CUSTOMER REQUIREMENTS</label>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="req-tile" data-val="School Books">
                                        <i class="fa fa-book"></i>
                                        <span>School Books</span>
                                        <i class="fa fa-check-circle req-check"></i>
                                        <input type="checkbox" name="requirements[]" value="School Books" class="requirement-cb d-none">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="req-tile" data-val="Exercise Books">
                                        <i class="fa fa-edit"></i>
                                        <span>Exercise Books</span>
                                        <i class="fa fa-check-circle req-check"></i>
                                        <input type="checkbox" name="requirements[]" value="Exercise Books" class="requirement-cb d-none">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="req-tile" data-val="Office Stationery">
                                        <i class="fa fa-archive"></i>
                                        <span>Office Stationery</span>
                                        <i class="fa fa-check-circle req-check"></i>
                                        <input type="checkbox" name="requirements[]" value="Office Stationery" class="requirement-cb d-none">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="req-tile" data-val="Printing Services">
                                        <i class="fa fa-print"></i>
                                        <span>Printing Services</span>
                                        <i class="fa fa-check-circle req-check"></i>
                                        <input type="checkbox" name="requirements[]" value="Printing Services" class="requirement-cb d-none">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="req-tile" data-val="School Supply">
                                        <i class="fa fa-truck"></i>
                                        <span>School Supply</span>
                                        <i class="fa fa-check-circle req-check"></i>
                                        <input type="checkbox" name="requirements[]" value="School Supply" class="requirement-cb d-none">
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="req-tile" data-val="Other">
                                        <i class="fa fa-plus-circle"></i>
                                        <span>Other Requirements</span>
                                        <i class="fa fa-check-circle req-check"></i>
                                        <input type="checkbox" name="requirements[]" value="Other" class="requirement-cb d-none">
                                    </div>
                                </div>
                            </div>
                            <div id="req-error" class="text-danger small mt-2" style="display:none; font-weight: 700;">Please select at least one requirement.</div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">DETAILED REQUIREMENTS DESCRIPTION</label>
                                <textarea name="detailed_requirement" class="form-control" data-preview="Detailed Requirements" rows="4" required placeholder="Explain specific customer needs..."></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small">EST MONTHLY VALUE (TZS)</label>
                                <input type="number" name="estimated_monthly_value" class="form-control" data-preview="Est Value" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small">URGENCY</label>
                                <select name="urgency" class="form-control select2" data-preview="Urgency">
                                    <option value="High">High</option>
                                    <option value="Medium" selected>Medium</option>
                                    <option value="Low">Low</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold small">EXPECTED PURCHASE DATE</label>
                                <input type="date" name="expected_purchase_date" class="form-control" data-preview="Expected Date">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-secondary prev-step px-4"><i class="fa fa-arrow-left mr-2"></i> Back</button>
                        <button type="button" class="btn btn-primary next-step px-5">Next: Tracking <i class="fa fa-arrow-right ml-2"></i></button>
                    </div>
                </div>

                <!-- STEP 4: TRACKING -->
                <div class="form-section" id="step4">
                    <h3 class="tile-title" style="color: var(--primary-brand)"><i class="fa fa-clipboard-check mr-2"></i> Section 4: Final Tracking</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">BUYING STAGE</label>
                                <select name="buying_stage" class="form-control select2" data-preview="Buying Stage" required>
                                    <option value="">Select Stage</option>
                                    <option value="Inquiry">Inquiry</option>
                                    <option value="Quotation Sent">Quotation Sent</option>
                                    <option value="Negotiation">Negotiation</option>
                                    <option value="Order Confirmed">Order Confirmed</option>
                                    <option value="Delivered">Delivered</option>
                                    <option value="Payment Pending">Payment Pending</option>
                                    <option value="Closed Won">Closed Won</option>
                                    <option value="Closed Lost">Closed Lost</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">PAYMENT TERMS</label>
                                <select name="payment_terms" class="form-control select2" data-preview="Payment Terms" required>
                                    <option value="">Select Payment Terms</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Credit">Credit</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">ASSIGNED OFFICER</label>
                                @if(Auth::user()->role === 'super_admin' || Auth::user()->role === 'manager')
                                    <select name="sales_officer_id" class="form-control select2" data-preview="Assigned Officer" required>
                                        <option value="{{ Auth::id() }}">{{ Auth::user()->name }} (Self)</option>
                                        @foreach($officers as $officer)
                                            @if($officer->id !== Auth::id())
                                                <option value="{{ $officer->id }}">{{ $officer->name }} [{{ $officer->branch->name ?? 'HQ' }}]</option>
                                            @endif
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control bg-light" data-preview="Officer" value="{{ Auth::user()->name }}" readonly>
                                    <input type="hidden" name="sales_officer_id" value="{{ Auth::id() }}">
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold small mandatory-label">NEXT FOLLOW-UP SCHEDULE</label>
                                <input type="date" name="next_follow_up_date" class="form-control border-primary shadow-sm" data-preview="Next Follow-up" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold small">ADDITIONAL INTERNAL NOTES</label>
                                <textarea name="notes" class="form-control" data-preview="Internal Notes" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-secondary prev-step px-4"><i class="fa fa-arrow-left mr-2"></i> Back</button>
                        <button type="button" class="btn btn-primary next-step px-5" id="btnToPreview">Review & Preview <i class="fa fa-eye ml-2"></i></button>
                    </div>
                </div>

                <!-- STEP 5: PREVIEW -->
                <div class="form-section" id="step5">
                    <h3 class="tile-title" style="color: var(--primary-brand)"><i class="fa fa-search mr-2"></i> Section 5: Review & Confirm Entry</h3>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="preview-table">
                            <thead class="bg-light">
                                <tr>
                                    <th style="color: var(--primary-brand); width: 40%;">Field</th>
                                    <th style="color: var(--primary-brand);">Data Details</th>
                                </tr>
                            </thead>
                            <tbody id="preview-tbody">
                                <!-- Data populated by JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between mt-5">
                        <button type="button" class="btn btn-secondary prev-step px-4"><i class="fa fa-arrow-left mr-2"></i> Edit Information</button>
                        <button type="submit" class="btn btn-success px-5 shadow-lg py-2" style="font-weight: 700; letter-spacing: 1px;"><i class="fa fa-check-circle mr-2"></i> CONFIRM & REGISTER LEAD</button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('assets/js/data/tz-locations.js') }}"></script>
<script src="{{ asset('assets/js/data/countries_data.js') }}"></script>
<script>
    $(document).ready(function() {
        const countrySelect = $('#countrySelect');
        if (typeof allCountries !== 'undefined') {
            allCountries.forEach(c => {
                const selected = c.name === 'Tanzania' ? 'selected' : '';
                countrySelect.append(`<option value="${c.name}" data-code="${c.code}" data-flag="${c.flag}" ${selected}>${c.name} (${c.code})</option>`);
            });
        }

        function formatCountry(country) {
            if (!country.id) return country.text;
            const flag = $(country.element).data('flag');
            if (!flag) return country.text;
            return $(`<span><img src="https://flagcdn.com/16x12/${flag}.png" class="mr-2" style="margin-top:-2px;"> ${country.text}</span>`);
        }

        $('.select2').select2({ 
            width: '100%',
            templateResult: formatCountry,
            templateSelection: formatCountry
        });

        // Requirement Tile Logic (Bulletproof)
        $('.req-tile').off('click').on('click', function(e) {
            e.preventDefault(); // Stop any conflicting default browser actions
            
            // Toggle visual state
            $(this).toggleClass('active');
            
            // Sync hidden checkbox with visual state
            let cb = $(this).find('.requirement-cb');
            cb.prop('checked', $(this).hasClass('active'));
            
            // Hide error if at least one is selected
            if ($('.requirement-cb:checked').length > 0) {
                $('#req-error').hide();
            }
        });

        // School Type Toggle
        $('#customerType').on('change', function() {
            if ($(this).val() === 'School') {
                $('#schoolTypeRow').show();
                $('select[name="school_level"]').prop('required', true);
            } else {
                $('#schoolTypeRow').hide();
                $('select[name="school_level"]').prop('required', false).val('').trigger('change');
            }
        });

        // Locations
        const regionSelect = $('#regionSelect');
        if (typeof tzLocations !== 'undefined') {
            Object.keys(tzLocations).sort().forEach(r => regionSelect.append(new Option(r, r)));
        }

        $('#regionSelect').on('change', function() {
            const r = $(this).val();
            const d = $('#districtSelect');
            d.empty().append('<option value="">Select District</option>');
            if (r && typeof tzLocations !== 'undefined' && tzLocations[r]) {
                tzLocations[r].sort().forEach(dis => d.append(new Option(dis, dis)));
                d.prop('disabled', false);
            } else { d.prop('disabled', true); }
            d.trigger('change');
        });

        // Country & Phone Logic
        $('#countrySelect').on('change', function() {
            const code = $(this).find(':selected').data('code');
            $('#countryCodeDisplay').text(code);
            $('.altCountryCodeDisplay').text(code);
            syncFullPhone();
            syncFullAltPhone();
        });

        function syncFullPhone() {
            const code = $('#countryCodeDisplay').text();
            const number = $('#phoneInput').val().replace(/\D/g, ''); 
            $('#fullPhone').val(code + number);
        }

        function syncFullAltPhone() {
            const code = $('.altCountryCodeDisplay').first().text();
            const number = $('#altPhoneInput').val().replace(/\D/g, '');
            $('#fullAltPhone').val(number ? (code + number) : '');
        }

        $('#phoneInput').on('input', function() {
            this.value = this.value.replace(/\D/g, ''); 
            if (this.value.length > 9) this.value = this.value.slice(0, 9);
            syncFullPhone();
        });

        $('#altPhoneInput').on('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 9) this.value = this.value.slice(0, 9);
            syncFullAltPhone();
        });

        // Wizard Logic
        let currentStep = 1;
        $('.next-step').on('click', function() {
            let valid = true;
            $(`#step${currentStep} [required]`).each(function() {
                if ($(this).val() == "" || $(this).val() == null) { 
                    $(this).addClass('is-invalid'); 
                    if($(this).hasClass('select2-hidden-accessible')) {
                        $(this).next('.select2-container').find('.select2-selection').css('border-color', '#940000');
                    }
                    valid = false; 
                } else { 
                    $(this).removeClass('is-invalid'); 
                    if($(this).hasClass('select2-hidden-accessible')) {
                        $(this).next('.select2-container').find('.select2-selection').css('border-color', '');
                    }
                }
            });

            if (currentStep == 2) {
                const phoneVal = $('#phoneInput').val();
                if (phoneVal.length !== 9) {
                    $('#phoneInput').addClass('is-invalid');
                    $('#phone-feedback').html('<small class="text-danger">Primary phone must be 9 digits.</small>').show();
                    valid = false;
                }
                const altVal = $('#altPhoneInput').val();
                if (altVal.length > 0 && altVal.length !== 9) {
                    $('#altPhoneInput').addClass('is-invalid');
                    $('#alt-phone-feedback').html('<small class="text-danger">Alt phone must be 9 digits.</small>').show();
                    valid = false;
                }
            }

            if (currentStep == 3 && $('.requirement-cb:checked').length === 0) {
                $('#req-error').show();
                valid = false;
            }

            if (valid) {
                if (currentStep == 4) generatePreview();
                $(`#step${currentStep}`).removeClass('active');
                $(`.step[data-step="${currentStep}"]`).addClass('completed').removeClass('active');
                currentStep++;
                $(`#step${currentStep}`).addClass('active');
                $(`.step[data-step="${currentStep}"]`).addClass('active');
                window.scrollTo({top:0, behavior:'smooth'});
            }
        });

        $('.prev-step').on('click', function() {
            $(`#step${currentStep}`).removeClass('active');
            $(`.step[data-step="${currentStep}"]`).removeClass('active');
            currentStep--;
            $(`#step${currentStep}`).addClass('active');
            $(`.step[data-step="${currentStep}"]`).addClass('active').removeClass('completed');
            window.scrollTo({top:0, behavior:'smooth'});
        });

        function generatePreview() {
            let sAll = '';
            
            for(let i=1; i<=4; i++) {
                let sectionTitle = ['Identity & Classification', 'Contact & Location', 'Opportunity & Requirements', 'Tracking & Assignments'][i-1];
                sAll += `<tr><td colspan="2" class="text-center font-weight-bold text-uppercase" style="background: #fdfdfd; color: var(--primary-brand); letter-spacing: 1px;">${sectionTitle}</td></tr>`;
                
                $(`#step${i} [data-preview]`).each(function() {
                    let val = $(this).is('select') ? $(this).find('option:selected').text() : ($(this).val() || 'N/A');
                    if(val && !val.includes('Select') && val !== '') {
                        sAll += `<tr><td class="font-weight-bold" style="color: #666;">${$(this).data('preview')}</td><td class="font-weight-bold text-dark">${val}</td></tr>`;
                    }
                });
                
                if (i === 3) {
                    let reqs = [];
                    $('.requirement-cb:checked').each(function() { reqs.push($(this).val()); });
                    sAll += `<tr><td class="font-weight-bold" style="color: #666;">Requirements</td><td class="font-weight-bold text-dark">${reqs.join(', ') || 'None'}</td></tr>`;
                }
            }

            $('#preview-tbody').html(sAll);
        }

        // ── Real-time AJAX duplicate check ──────────────────────────────────
        const checkDuplicateUrl = '{{ route("customers.check_duplicate") }}';
        const csrfToken = '{{ csrf_token() }}';
        let phoneOk = true, emailOk = true;

        function showFieldFeedback(feedbackId, inputId, data) {
            const $fb = $(`#${feedbackId}`);
            const $input = $(`#${inputId}`);
            if (data.duplicate) {
                $input.addClass('is-invalid').css('border-color','#940000');
                $fb.html(`
                    <div style="background:#fff0f0;border:1px solid #940000;border-radius:6px;padding:10px 14px;font-size:12px;">
                        <strong style="color:#940000;"><i class="fa fa-ban mr-1"></i> Duplicate Found</strong><br>
                        <strong>${data.customer_name}</strong> is already registered by 
                        <strong>${data.owner}</strong> — <em>${data.branch} Branch</em>.
                        <a href="/customers/${data.customer_id}" class="ml-2 font-weight-bold" style="color:#940000;">View Record →</a>
                    </div>`).show();
                return false;
            } else {
                $input.removeClass('is-invalid').css('border-color','#28a745');
                $fb.html(`<small class="text-success"><i class="fa fa-check-circle"></i> Available</small>`).show();
                return true;
            }
        }

        $('#phoneInput').on('blur', function() {
            const val = $(this).val().trim();
            if (val.length < 9) return;
            const fullVal = $('#fullPhone').val();
            $.post(checkDuplicateUrl, { _token: csrfToken, field: 'phone', value: fullVal }, function(res) {
                phoneOk = showFieldFeedback('phone-feedback', 'phoneInput', res);
            });
        });

        $('#emailInput').on('blur', function() {
            const val = $(this).val().trim();
            const $fb = $('#email-feedback');
            if (!val) { $fb.hide(); $(this).css('border-color',''); return; }

            // 1. Validate format first
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(val)) {
                $(this).addClass('is-invalid').css('border-color','#940000');
                $fb.html(`<small style="color:#940000;"><i class="fa fa-times-circle mr-1"></i> Invalid format. Example: name@domain.com</small>`).show();
                emailOk = false;
                return;
            }

            // 2. Then check for duplicates
            $.post(checkDuplicateUrl, { _token: csrfToken, field: 'email', value: val }, function(res) {
                emailOk = showFieldFeedback('email-feedback', 'emailInput', res);
            });
        });
        // ────────────────────────────────────────────────────────────────────
    });
</script>
@endsection
