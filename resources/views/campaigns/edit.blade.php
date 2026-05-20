@extends('layouts.vali')

@section('title', 'Edit Campaign')
@section('page_icon', 'fa-edit')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="tile">
            <h3 class="tile-title">Edit Campaign: {{ $campaign->name }}</h3>
            <div class="tile-body">
                <form action="{{ route('campaigns.update', $campaign->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Campaign Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $campaign->name }}" required>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Event Date</label>
                        <input type="date" name="event_date" class="form-control" value="{{ $campaign->event_date->format('Y-m-d') }}" required>
                        <small class="text-muted">SMS will be dispatched automatically at 07:00 AM on this date.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Target Service (Optional)</label>
                            <select name="target_service" class="form-control">
                                <option value="" {{ is_null($campaign->target_service) ? 'selected' : '' }}>All Services</option>
                                <option value="Bookshop" {{ $campaign->target_service == 'Bookshop' ? 'selected' : '' }}>Bookshop</option>
                                <option value="Wakala" {{ $campaign->target_service == 'Wakala' ? 'selected' : '' }}>Wakala</option>
                                <option value="Stationery" {{ $campaign->target_service == 'Stationery' ? 'selected' : '' }}>Stationery</option>
                                <option value="Others" {{ $campaign->target_service == 'Others' ? 'selected' : '' }}>Others</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Target Location (Optional)</label>
                            <select name="target_location" class="form-control">
                                <option value="" {{ is_null($campaign->target_location) ? 'selected' : '' }}>All Locations</option>
                                @php
                                    $regions = [
                                        'Arusha', 'Dar es Salaam', 'Dodoma', 'Geita', 'Iringa', 'Kagera', 'Katavi', 'Kigoma', 
                                        'Kilimanjaro', 'Lindi', 'Manyara', 'Mara', 'Mbeya', 'Morogoro', 'Mtwara', 'Mwanza', 
                                        'Njombe', 'Pemba North', 'Pemba South', 'Pwani', 'Rukwa', 'Ruvuma', 'Shinyanga', 
                                        'Simiyu', 'Singida', 'Songwe', 'Tabora', 'Tanga', 'Zanzibar Central/South', 
                                        'Zanzibar North', 'Zanzibar Urban/West'
                                    ];
                                @endphp
                                @foreach($regions as $region)
                                    <option value="{{ $region }}" {{ $campaign->target_location == $region ? 'selected' : '' }}>{{ $region }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Target Buying Stage (Optional)</label>
                        <select name="target_stage" class="form-control">
                            <option value="" {{ is_null($campaign->target_stage) ? 'selected' : '' }}>All Stages</option>
                            <option value="Inquiry" {{ $campaign->target_stage == 'Inquiry' ? 'selected' : '' }}>Inquiry</option>
                            <option value="Quotation Sent" {{ $campaign->target_stage == 'Quotation Sent' ? 'selected' : '' }}>Quotation Sent</option>
                            <option value="Negotiation" {{ $campaign->target_stage == 'Negotiation' ? 'selected' : '' }}>Negotiation</option>
                            <option value="Order Confirmed" {{ $campaign->target_stage == 'Order Confirmed' ? 'selected' : '' }}>Order Confirmed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">SMS Message Content</label>
                        <textarea name="message" class="form-control" rows="5" required>{{ $campaign->message }}</textarea>
                    </div>

                    @php
                        $channels = $campaign->channels ?? ['sms'];
                    @endphp
                    <div class="form-group">
                        <label class="font-weight-bold">Delivery Channels</label><br>
                        <div class="animated-checkbox d-inline-block mr-3">
                            <label>
                                <input type="checkbox" name="channels[]" value="sms" {{ in_array('sms', $channels) ? 'checked' : '' }}>
                                <span class="label-text">SMS</span>
                            </label>
                        </div>
                        <div class="animated-checkbox d-inline-block mr-3">
                            <label>
                                <input type="checkbox" name="channels[]" value="whatsapp" {{ in_array('whatsapp', $channels) ? 'checked' : '' }}>
                                <span class="label-text">WhatsApp</span>
                            </label>
                        </div>
                        <div class="animated-checkbox d-inline-block">
                            <label>
                                <input type="checkbox" name="channels[]" value="email" {{ in_array('email', $channels) ? 'checked' : '' }}>
                                <span class="label-text">Email</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-save"></i> Update Campaign</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
