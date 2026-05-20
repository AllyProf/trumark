@extends('layouts.vali')

@section('title', 'Edit Lead: ' . $customer->name)

@section('page_icon', 'fa-edit')

@section('subtitle')
Updating record for {{ $customer->name }}
@endsection

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    :root { --primary-brand: #940000; --brand-light: #fff5f5; }
    .select2-container--default .select2-selection--single { height: 40px; border: 1px solid #ced4da; border-radius: 4px; padding-top: 5px; }
    .mandatory-label::after { content: " *"; color: var(--primary-brand); }
    .section-header { background: var(--brand-light); border-left: 5px solid var(--primary-brand); padding: 12px 20px; border-radius: 8px; margin-bottom: 25px; }
    .section-header h4 { margin: 0; color: var(--primary-brand); font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
    /* Requirement Tiles */
    .req-tile { border: 2px solid #eee; border-radius: 12px; padding: 18px; text-align: center; cursor: pointer; transition: all 0.3s; position: relative; background: #fff; }
    .req-tile:hover { border-color: #ddd; transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .req-tile.active { border-color: var(--primary-brand); background: var(--brand-light); box-shadow: 0 0 10px rgba(148,0,0,0.1); }
    .req-tile i { font-size: 26px; margin-bottom: 10px; color: var(--primary-brand); }
    .req-tile span { display: block; font-weight: 700; font-size: 11px; color: #333; text-transform: uppercase; letter-spacing: 0.5px; }
    .req-check { position: absolute; top: 8px; right: 8px; color: var(--primary-brand); display: none; font-size: 16px; }
    .req-tile.active .req-check { display: block; }
</style>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-11">

        <form action="{{ route('customers.update', $customer->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- SECTION 1: IDENTITY --}}
            <div class="tile">
                <div class="section-header">
                    <h4><i class="fa fa-id-card mr-2"></i> Section 1: Identity & Classification</h4>
                </div>
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <div class="form-group" style="background: var(--brand-light); padding: 15px; border-radius: 8px; border: 1px solid rgba(148,0,0,0.2);">
                            <label class="font-weight-bold" style="color: var(--primary-brand); font-size: 13px;">SERVICE CATEGORY</label>
                            <select name="service" id="serviceCategory" class="form-control select2" data-preview="Service Category" required>
                                <option value="Bookshop" {{ ($customer->service ?? 'Bookshop') == 'Bookshop' ? 'selected' : '' }}>📚 Bookshop (Full Form)</option>
                                <option value="Stationery" {{ $customer->service == 'Stationery' ? 'selected' : '' }}>✏️ Stationery (Simplified)</option>
                                <option value="Wakala" {{ $customer->service == 'Wakala' ? 'selected' : '' }}>📱 Wakala (Simplified)</option>
                                <option value="Others" {{ $customer->service == 'Others' ? 'selected' : '' }}>🏢 Others (Simplified)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">CUSTOMER STATUS</label>
                            <select name="status" class="form-control select2">
                                @foreach(['New Customer','Potential Customer','Existing Customer','Inactive Customer','VIP Customer'] as $s)
                                    <option value="{{ $s }}" {{ $customer->status == $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">CUSTOMER TYPE</label>
                            <select name="type" id="customerType" class="form-control select2">
                                <option value="">Select Type</option>
                                @foreach(['School','Institution','Company / Organization','Parent','Walk in'] as $t)
                                    <option value="{{ $t }}" {{ $customer->type == $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 bookshop-only" id="schoolTypeRow" style="{{ $customer->type == 'School' ? '' : 'display:none;' }}">
                        <div class="form-group">
                            <label class="font-weight-bold small">SCHOOL LEVEL</label>
                            @php
                                $savedLevels = is_array($customer->school_level) ? $customer->school_level : json_decode($customer->school_level ?? '[]', true) ?? [];
                            @endphp
                            <select name="school_level[]" class="form-control select2" multiple="multiple">
                                @foreach(['Pre Primary','Nursery and Primary','O-Level','A-Level','O-Level and A-Level','VTC','others'] as $lvl)
                                    <option value="{{ $lvl }}" {{ in_array($lvl, $savedLevels) ? 'selected' : '' }}>{{ $lvl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">CUSTOMER SOURCE</label>
                            <select name="source" class="form-control select2">
                                <option value="">Select Source</option>
                                @foreach(['Seminar','Conference','Exhibitions','Walk-in','Sales Visit','Referral','WhatsApp','Instagram','Facebook','Website','Phone Call'] as $src)
                                    <option value="{{ $src }}" {{ $customer->source == $src ? 'selected' : '' }}>{{ $src }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold small mandatory-label">CUSTOMER / ORGANIZATION NAME</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">CONTACT PERSON</label>
                            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $customer->contact_person) }}">
                        </div>
                    </div>
                    <div class="col-md-4 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">POSITION</label>
                            <input type="text" name="position" class="form-control" value="{{ old('position', $customer->position) }}">
                        </div>
                    </div>
                    <div class="col-md-4 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">TIN / REG NO.</label>
                            <input type="text" name="tin_no" class="form-control" value="{{ old('tin_no', $customer->tin_no) }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 2: LOCATION --}}
            <div class="tile">
                <div class="section-header">
                    <h4><i class="fa fa-map-marker mr-2"></i> Section 2: Contact & Location</h4>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold small">COUNTRY</label>
                            @php
                                $currentCountry = $customer->country ?? 'Tanzania';
                                $countries = [
                                    'Tanzania' => '+255',
                                    'Kenya' => '+254',
                                    'Uganda' => '+256',
                                    'Rwanda' => '+250',
                                    'Burundi' => '+257'
                                ];
                                $currentCode = '+255';
                                foreach($countries as $c => $code) {
                                    if($currentCountry == $c) $currentCode = $code;
                                }
                                // Try to extract the 9 digits from existing phone
                                $phoneOnly = preg_replace('/[^0-9]/', '', $customer->phone);
                                if(strlen($phoneOnly) > 9) {
                                    $phoneOnly = substr($phoneOnly, -9);
                                }
                            @endphp
                            <select name="country" id="countrySelect" class="form-control select2">
                                <!-- Populated by JS -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold small">PRIMARY PHONE</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold" id="countryCodeDisplay" style="background: #f8f9fa;">{{ $currentCode }}</span>
                                </div>
                                <input type="text" id="phoneEdit" name="phone_number" class="form-control" value="{{ $phoneOnly }}" maxlength="9">
                                <input type="hidden" name="phone" id="fullPhone" value="{{ $customer->phone }}">
                            </div>
                            <small class="text-muted">Enter 9 digits after country code.</small>
                            <div id="phone-edit-feedback" class="mt-1" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="col-md-4 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">ALT PHONE</label>
                            @php
                                $altPhoneOnly = preg_replace('/[^0-9]/', '', $customer->alternative_phone);
                                if(strlen($altPhoneOnly) > 9) {
                                    $altPhoneOnly = substr($altPhoneOnly, -9);
                                }
                            @endphp
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold altCountryCodeDisplay" style="background: #f8f9fa;">{{ $currentCode }}</span>
                                </div>
                                <input type="text" id="altPhoneEdit" name="alternative_phone_number" class="form-control" value="{{ $altPhoneOnly }}" maxlength="9" placeholder="Optional phone">
                                <input type="hidden" name="alternative_phone" id="fullAltPhone" value="{{ $customer->alternative_phone }}">
                            </div>
                            <small class="text-muted">Enter 9 digits after country code.</small>
                            <div id="alt-phone-edit-feedback" class="mt-1" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold small">EMAIL</label>
                            <input type="email" id="emailEdit" name="email" class="form-control" value="{{ old('email', $customer->email) }}">
                            <div id="email-edit-feedback" class="mt-1" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="col-md-6 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">REGION</label>
                            <select name="region" id="regionSelect" class="form-control select2">
                                <option value="">Select Region</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">DISTRICT</label>
                            <select name="district" id="districtSelect" class="form-control select2">
                                <option value="">Select District</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">WARD / AREA</label>
                            <input type="text" name="ward" class="form-control" value="{{ old('ward', $customer->ward) }}">
                        </div>
                    </div>
                    <div class="col-md-8 bookshop-only">
                        <div class="form-group">
                            <label class="font-weight-bold small">PHYSICAL ADDRESS & LANDMARK</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $customer->address) }}">
                        </div>
                    </div>
                    <div class="col-md-12 simplified-only" style="display:none;">
                        <div class="form-group">
                            <label class="font-weight-bold small mandatory-label">SERVICE DESCRIPTION</label>
                            <textarea name="service_description" class="form-control" data-preview="Service Description" rows="4" placeholder="Briefly describe the service required...">{{ old('service_description', $customer->service_description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 3: REQUIREMENTS --}}
            <div class="tile bookshop-only">
                <div class="section-header">
                    <h4><i class="fa fa-shopping-basket mr-2"></i> Section 3: Requirements & Value</h4>
                </div>
                <div class="row">
                    @php
                        $savedReqs = is_array($customer->requirements) ? $customer->requirements : json_decode($customer->requirements ?? '[]', true);
                    @endphp
                    <div class="col-md-12 mb-4">
                        <label class="font-weight-bold small d-block mb-3">SELECT CUSTOMER REQUIREMENTS</label>
                        <div class="row">
                            @foreach([
                                ['School Books', 'fa-book'],
                                ['Exercise Books', 'fa-edit'],
                                ['Office Stationery', 'fa-archive'],
                                ['Printing Services', 'fa-print'],
                                ['School Supply', 'fa-truck'],
                                ['Other', 'fa-plus-circle'],
                            ] as [$req, $icon])
                            <div class="col-md-4 mb-3">
                                <div class="req-tile {{ in_array($req, $savedReqs ?? []) ? 'active' : '' }}" data-val="{{ $req }}">
                                    <i class="fa {{ $icon }}"></i>
                                    <span>{{ $req }}</span>
                                    <i class="fa fa-check-circle req-check"></i>
                                    <input type="checkbox" name="requirements[]" value="{{ $req }}" class="requirement-cb d-none" {{ in_array($req, $savedReqs ?? []) ? 'checked' : '' }}>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <div id="req-error" class="text-danger small mt-2" style="display:none; font-weight:700;">Please select at least one requirement.</div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold small">DETAILED REQUIREMENTS</label>
                            <textarea name="detailed_requirement" class="form-control" rows="4">{{ old('detailed_requirement', $customer->detailed_requirement) }}</textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold small">EST MONTHLY VALUE (TZS)</label>
                            <input type="number" name="estimated_monthly_value" class="form-control" value="{{ old('estimated_monthly_value', $customer->estimated_monthly_value) }}" min="0">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold small">URGENCY</label>
                            <select name="urgency" class="form-control select2">
                                @foreach(['High','Medium','Low'] as $u)
                                    <option value="{{ $u }}" {{ $customer->urgency == $u ? 'selected' : '' }}>{{ $u }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold small">EXPECTED PURCHASE DATE</label>
                            <input type="date" name="expected_purchase_date" class="form-control" value="{{ old('expected_purchase_date', $customer->expected_purchase_date ? $customer->expected_purchase_date->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECTION 4: TRACKING --}}
            <div class="tile bookshop-only">
                <div class="section-header">
                    <h4><i class="fa fa-clipboard-check mr-2"></i> Section 4: Tracking & Pipeline</h4>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold small">BUYING STAGE</label>
                            <select name="buying_stage" class="form-control select2">
                                @foreach(['Inquiry','Quotation Sent','Negotiation','Order Confirmed','Delivered','Payment Pending','Closed Won','Closed Lost'] as $stage)
                                    <option value="{{ $stage }}" {{ $customer->buying_stage == $stage ? 'selected' : '' }}>{{ $stage }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold small">PAYMENT TERMS</label>
                            <select name="payment_terms" class="form-control select2">
                                <option value="">Select Payment Terms</option>
                                    <option value="Cash" {{ $customer->payment_terms == 'Cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="Bank Transfer" {{ $customer->payment_terms == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="Credit" {{ $customer->payment_terms == 'Credit' ? 'selected' : '' }}>Credit</option>
                                    <option value="Mobile Money" {{ $customer->payment_terms == 'Mobile Money' ? 'selected' : '' }}>Mobile Money</option>
                                    <option value="Unknown yet" {{ $customer->payment_terms == 'Unknown yet' ? 'selected' : '' }}>Unknown yet</option>
                            </select>
                        </div>
                    </div>
                    @if(Auth::user()->role === 'super_admin' || Auth::user()->role === 'manager')
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold small">ASSIGNED OFFICER</label>
                            <select name="sales_officer_id" class="form-control select2">
                                @foreach($officers as $officer)
                                    <option value="{{ $officer->id }}" {{ $customer->sales_officer_id == $officer->id ? 'selected' : '' }}>
                                        {{ $officer->name }} [{{ $officer->branch->name ?? 'HQ' }}]
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="font-weight-bold small">NEXT FOLLOW-UP</label>
                            <input type="date" name="next_follow_up_date" class="form-control" value="{{ old('next_follow_up_date', $customer->next_follow_up_date ? $customer->next_follow_up_date->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="font-weight-bold small">INTERNAL NOTES</label>
                            <textarea name="notes" class="form-control" rows="4">{{ old('notes', $customer->notes) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-4">
                    <a href="{{ route('customers.show', $customer->id) }}" class="btn btn-secondary px-4">
                        <i class="fa fa-times mr-2"></i> Cancel
                    </a>
                    <button type="button" id="confirmUpdateBtn" class="btn btn-success px-5 shadow-lg py-2" style="font-weight: 700; letter-spacing: 1px;"><i class="fa fa-save mr-2"></i> UPDATE LEAD INFORMATION</button>
                </div>
            </div>

        </form>
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
    const savedCountry = '{{ $currentCountry }}';
    if (typeof allCountries !== 'undefined') {
        allCountries.forEach(c => {
            const selected = c.name === savedCountry ? 'selected' : '';
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

    // School level toggle
    $('#customerType').on('change', function() {
        if ($(this).val() === 'School') {
            $('#schoolTypeRow').show();
        } else {
            $('#schoolTypeRow').hide();
            $('select[name="school_level"]').val('').trigger('change');
        }
    });

    // Locations with pre-selected values
    const savedRegion   = '{{ $customer->region }}';
    const savedDistrict = '{{ $customer->district }}';
    const regionSelect   = $('#regionSelect');
    const districtSelect = $('#districtSelect');

    if (typeof tzLocations !== 'undefined') {
        Object.keys(tzLocations).sort().forEach(r => {
            const selected = r === savedRegion ? 'selected' : '';
            regionSelect.append(`<option value="${r}" ${selected}>${r}</option>`);
        });
    }

    // Pre-load districts for saved region
    if (savedRegion && typeof tzLocations !== 'undefined' && tzLocations[savedRegion]) {
        tzLocations[savedRegion].sort().forEach(d => {
            const selected = d === savedDistrict ? 'selected' : '';
            districtSelect.append(`<option value="${d}" ${selected}>${d}</option>`);
        });
        districtSelect.prop('disabled', false);
    }

    regionSelect.on('change', function() {
        const r = $(this).val();
        districtSelect.empty().append('<option value="">Select District</option>');
        if (r && typeof tzLocations !== 'undefined' && tzLocations[r]) {
            tzLocations[r].sort().forEach(d => districtSelect.append(new Option(d, d)));
            districtSelect.prop('disabled', false);
        } else { districtSelect.prop('disabled', true); }
        districtSelect.trigger('change');
    });

    // Dynamic Service Type Switcher
    function toggleServiceFields() {
        let service = $('#serviceCategory').val();
        if (service !== 'Bookshop') {
            $('.bookshop-only').slideUp();
            $('.bookshop-only [required]').removeAttr('required').attr('data-was-required', 'true');
            $('.simplified-only').slideDown();
            $('.simplified-only textarea').attr('required', 'required');
        } else {
            $('.bookshop-only').slideDown();
            $('.bookshop-only [data-was-required="true"]').attr('required', 'required');
            $('.simplified-only').slideUp();
            $('.simplified-only textarea').removeAttr('required');
        }
    }
    
    $('#serviceCategory').on('change', toggleServiceFields);
    
    // Run on load
    toggleServiceFields();

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
        const number = $('#phoneEdit').val().replace(/\D/g, ''); 
        $('#fullPhone').val(code + number);
    }

    function syncFullAltPhone() {
        const code = $('.altCountryCodeDisplay').first().text();
        const number = $('#altPhoneEdit').val().replace(/\D/g, '');
        $('#fullAltPhone').val(number ? (code + number) : '');
    }

    $('#phoneEdit').on('input', function() {
        this.value = this.value.replace(/\D/g, ''); 
        if (this.value.length > 9) this.value = this.value.slice(0, 9);
        syncFullPhone();
    });

    $('#altPhoneEdit').on('input', function() {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length > 9) this.value = this.value.slice(0, 9);
        syncFullAltPhone();
    });

    // Form validation before submit
    $('#confirmUpdateBtn').on('click', function(e) {
        let valid = true;
        const phoneLen = $('#phoneEdit').val().length;
        if (phoneLen > 0 && phoneLen !== 9) {
            $('#phoneEdit').addClass('is-invalid');
            $('#phone-edit-feedback').html('<small class="text-danger">Primary phone must be 9 digits if provided.</small>').show();
            valid = false;
        }
        const altVal = $('#altPhoneEdit').val();
        if (altVal.length > 0 && altVal.length !== 9) {
            $('#altPhoneEdit').addClass('is-invalid');
            $('#alt-phone-edit-feedback').html('<small class="text-danger">Alt phone must be 9 digits.</small>').show();
            valid = false;
        }

        if (!valid) {
            window.scrollTo({top: $('#phoneEdit').offset().top - 100, behavior: 'smooth'});
            return;
        }
        
        Swal.fire({
            title: 'Confirm Update',
            text: "Are you sure you want to update this lead?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: '<i class="fa fa-check-circle mr-1"></i> Yes, update!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Updating...',
                    text: 'Please wait while we save the changes.',
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });
                $(this).closest('form').submit();
            }
        });
    });

    // Requirement tiles
    $('.req-tile').on('click', function(e) {
        e.preventDefault();
        $(this).toggleClass('active');
        const cb = $(this).find('.requirement-cb');
        cb.prop('checked', $(this).hasClass('active'));
    });

    // Real-time duplicate check (excluding current customer)
    const checkUrl  = '{{ route("customers.check_duplicate") }}';
    const csrfToken = '{{ csrf_token() }}';
    const customerId = {{ $customer->id }};

    function showFeedback(fbId, inputId, data) {
        const $fb = $(`#${fbId}`), $input = $(`#${inputId}`);
        if (data.duplicate && data.customer_id != customerId) {
            $input.css('border-color','#940000');
            $fb.html(`<div style="background:#fff0f0;border:1px solid #940000;border-radius:6px;padding:8px 12px;font-size:12px;">
                <strong style="color:#940000;"><i class="fa fa-ban mr-1"></i> Duplicate</strong> — 
                <strong>${data.customer_name}</strong> owned by <strong>${data.owner}</strong> (${data.branch}).
                <a href="/customers/${data.customer_id}" style="color:#940000;" class="ml-1 font-weight-bold">View →</a>
            </div>`).show();
        } else {
            $input.css('border-color','');
            $fb.hide();
        }
    }

    $('#phoneEdit').on('blur', function() {
        const val = $(this).val().trim();
        if (val.length < 9) return;
        const fullVal = $('#fullPhone').val();
        $.post(checkUrl, { _token: csrfToken, field: 'phone', value: fullVal }, res => showFeedback('phone-edit-feedback', 'phoneEdit', res));
    });

    $('#emailEdit').on('blur', function() {
        const val = $(this).val().trim();
        if (!val) return;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(val)) {
            $(this).css('border-color','#940000');
            $('#email-edit-feedback').html(`<small style="color:#940000;"><i class="fa fa-times-circle mr-1"></i> Invalid email format.</small>`).show();
            return;
        }
        $.post(checkUrl, { _token: csrfToken, field: 'email', value: val }, res => showFeedback('email-edit-feedback', 'emailEdit', res));
    });
});
</script>
@endsection
