@extends('layouts.vali')

@section('title', 'Create Campaign')
@section('page_icon', 'fa-plus')

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="tile">
            <h3 class="tile-title">Schedule New Campaign</h3>
            <div class="tile-body">
                <form action="{{ route('campaigns.store') }}" method="POST">
                    @csrf
                    
                    <div class="form-group">
                        <label class="font-weight-bold">Campaign Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Back to School Promo" required>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Event Date</label>
                        <input type="date" name="event_date" class="form-control" required>
                        <small class="text-muted">SMS will be dispatched automatically at 07:00 AM on this date.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Target Service (Optional)</label>
                            <select name="target_service" class="form-control">
                                <option value="">All Services</option>
                                <option value="Bookshop">Bookshop</option>
                                <option value="Wakala">Wakala</option>
                                <option value="Stationery">Stationery</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Target Location (Optional)</label>
                            <select name="target_location" class="form-control">
                                <option value="">All Locations</option>
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
                                    <option value="{{ $region }}">{{ $region }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Target Buying Stage (Optional)</label>
                        <select name="target_stage" class="form-control">
                            <option value="">All Stages</option>
                            <option value="Inquiry">Inquiry</option>
                            <option value="Quotation Sent">Quotation Sent</option>
                            <option value="Negotiation">Negotiation</option>
                            <option value="Order Confirmed">Order Confirmed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">SMS Message Content</label>
                        <textarea name="message" class="form-control" rows="5" placeholder="Type the campaign message here..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Delivery Channels</label><br>
                        <div class="animated-checkbox d-inline-block mr-3">
                            <label>
                                <input type="checkbox" name="channels[]" value="sms" checked>
                                <span class="label-text">SMS</span>
                            </label>
                        </div>
                        <div class="animated-checkbox d-inline-block mr-3">
                            <label>
                                <input type="checkbox" name="channels[]" value="whatsapp">
                                <span class="label-text">WhatsApp</span>
                            </label>
                        </div>
                        <div class="animated-checkbox d-inline-block">
                            <label>
                                <input type="checkbox" name="channels[]" value="email">
                                <span class="label-text">Email</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-save"></i> Save Campaign</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
