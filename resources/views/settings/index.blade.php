@extends('layouts.vali')

@section('title', 'System Settings')

@section('page_icon', 'fa-cogs')

@section('subtitle')
Configure global application behavior, branding, and automation settings
@endsection

@section('styles')
<style>
    .settings-nav {
        background: #fff;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .settings-nav .nav-link {
        color: #555;
        font-weight: 600;
        padding: 15px 20px;
        border-left: 3px solid transparent;
        border-bottom: 1px solid #f1f1f1;
        border-radius: 0;
        transition: all 0.3s;
        display: flex;
        align-items: center;
    }
    .settings-nav .nav-link i { 
        width: 25px;
        font-size: 16px;
        margin-right: 10px;
        text-align: center;
        color: #999;
    }
    .settings-nav .nav-link:hover {
        background: #f9f9f9;
        color: #940000;
    }
    .settings-nav .nav-link.active {
        background: #fff !important;
        color: #940000 !important;
        border-left-color: #940000;
    }
    .settings-nav .nav-link.active i {
        color: #940000;
    }
    .section-header {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f1f1f1;
    }
    .section-header h4 {
        margin: 0;
        font-weight: 700;
        font-size: 18px;
        color: #333;
    }
    .section-header i {
        margin-right: 15px;
        color: #940000;
        font-size: 20px;
    }
    .form-group label {
        font-weight: 700;
        color: #444;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .form-control-lg-custom {
        height: 45px;
        font-size: 15px;
        border-radius: 6px;
    }
    .save-btn-fixed {
        margin-top: 20px;
    }
    
    /* ── MOBILE RESPONSIVE ─────────────────────────────────── */
    @media (max-width: 767px) {
        /* Convert vertical pills sidebar to horizontal scrollable native tab menu */
        .settings-nav {
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            overflow-x: auto !important;
            white-space: nowrap !important;
            background: transparent !important;
            box-shadow: none !important;
            border-bottom: 2px solid #eee !important;
            padding-bottom: 5px !important;
            margin-bottom: 15px !important;
            border-radius: 0 !important;
            -webkit-overflow-scrolling: touch;
        }
        
        .settings-nav .nav-link {
            flex: 0 0 auto !important;
            display: inline-block !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            border-bottom: 3px solid transparent !important;
            padding: 10px 15px !important;
            font-size: 13px !important;
            border-radius: 4px !important;
            margin-right: 5px !important;
        }
        
        .settings-nav .nav-link.active {
            border-bottom: 3px solid #940000 !important;
            background: #fff !important;
            border-radius: 4px 4px 0 0 !important;
        }
        
        .settings-nav::-webkit-scrollbar {
            height: 4px;
        }
        .settings-nav::-webkit-scrollbar-thumb {
            background-color: rgba(148, 0, 0, 0.2);
            border-radius: 4px;
        }
        
        /* Sticky Mobile Bottom Save Bar (Native Experience) */
        .save-btn-fixed {
            position: fixed !important;
            bottom: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 1050 !important;
            margin-top: 0 !important;
            padding: 10px 15px !important;
            background: #fff !important;
            box-shadow: 0 -4px 10px rgba(0,0,0,0.08) !important;
            border-top: 1px solid #eee !important;
        }
        
        .save-btn-fixed button {
            margin: 0 !important;
        }
        
        /* Prevent content from clipping under the sticky bottom bar */
        .col-md-9 {
            padding-bottom: 80px !important;
        }
        
        /* Keep table inputs legible */
        .table input {
            min-width: 140px !important;
        }
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            <div class="row">
                <!-- Sidebar Navigation -->
                <div class="col-md-3">
                    <div class="nav flex-column nav-pills settings-nav" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <a class="nav-link active" id="v-pills-general-tab" data-toggle="pill" href="#v-pills-general" role="tab">
                            <i class="fa fa-desktop"></i> Application Basics
                        </a>
                        <a class="nav-link" id="v-pills-contact-tab" data-toggle="pill" href="#v-pills-contact" role="tab">
                            <i class="fa fa-building"></i> Company Profile
                        </a>
                        <a class="nav-link" id="v-pills-sms-tab" data-toggle="pill" href="#v-pills-sms" role="tab">
                            <i class="fa fa-paper-plane"></i> SMS Gateway
                        </a>
                        <a class="nav-link" id="v-pills-survey-tab" data-toggle="pill" href="#v-pills-survey" role="tab">
                            <i class="fa fa-check-square-o"></i> Survey & KPI
                        </a>
                        <a class="nav-link" id="v-pills-performance-tab" data-toggle="pill" href="#v-pills-performance" role="tab">
                            <i class="fa fa-line-chart"></i> Performance Config
                        </a>
                        <a class="nav-link" id="v-pills-ops-tab" data-toggle="pill" href="#v-pills-ops" role="tab">
                            <i class="fa fa-calendar-o"></i> Operations & Goals
                        </a>
                        <a class="nav-link" id="v-pills-security-tab" data-toggle="pill" href="#v-pills-security" role="tab">
                            <i class="fa fa-shield"></i> Security & Session
                        </a>
                        <a class="nav-link" id="v-pills-branding-tab" data-toggle="pill" href="#v-pills-branding" role="tab">
                            <i class="fa fa-paint-brush"></i> UI & Branding
                        </a>
                    </div>
                    
                    <div class="save-btn-fixed">
                        <button type="submit" class="btn btn-primary btn-block btn-lg shadow-sm font-weight-bold">
                            <i class="fa fa-save mr-2"></i> Save All Settings
                        </button>
                    </div>
                </div>

                <!-- Settings Content -->
                <div class="col-md-9">
                    <div class="tile border-0 shadow-sm">
                        <div class="tile-body p-3">
                            <div class="tab-content" id="v-pills-tabContent">
                                
                                <!-- Application Basics -->
                                <div class="tab-pane fade show active" id="v-pills-general" role="tabpanel">
                                    <div class="section-header">
                                        <i class="fa fa-desktop"></i>
                                        <h4>Application Basics</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Application Name</label>
                                                <input type="text" name="company_name" class="form-control form-control-lg-custom" value="{{ $settings['company_name'] ?? 'TRUMARK' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>System Browser Title</label>
                                                <input type="text" name="system_title" class="form-control form-control-lg-custom" value="{{ $settings['system_title'] ?? 'TRUMARK CRM' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Copyright Footer Text</label>
                                                <input type="text" name="footer_text" class="form-control form-control-lg-custom" value="{{ $settings['footer_text'] ?? 'Copyright © 2026 TRUMARK. All Rights Reserved.' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Company Profile -->
                                <div class="tab-pane fade" id="v-pills-contact" role="tabpanel">
                                    <div class="section-header">
                                        <i class="fa fa-building"></i>
                                        <h4>Company Profile</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Support Email Address</label>
                                                <input type="email" name="company_email" class="form-control form-control-lg-custom" value="{{ $settings['company_email'] ?? 'info@trumark.co.tz' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Public Phone Number</label>
                                                <input type="text" name="company_phone" class="form-control form-control-lg-custom" value="{{ $settings['company_phone'] ?? '+255 616 775 800' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Headquarters Address</label>
                                                <textarea name="company_address" class="form-control" rows="4">{{ $settings['company_address'] ?? 'Kariakoo, Dar es Salaam, Tanzania' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SMS Gateway -->
                                <div class="tab-pane fade" id="v-pills-sms" role="tabpanel">
                                    <div class="section-header">
                                        <i class="fa fa-paper-plane"></i>
                                        <h4>SMS Gateway (Onfon SMS)</h4>
                                    </div>

                                    @if(isset($smsBalance))
                                        <div class="alert {{ $smsBalance['success'] ? 'alert-info' : 'alert-danger' }} mb-4 border-0 shadow-sm d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-uppercase font-weight-bold d-block mb-1">Live Account Balance:</small>
                                                <h3 class="mb-0 font-weight-bold">
                                                    @if($smsBalance['success'])
                                                        {{ $smsBalance['balance'] }}
                                                    @else
                                                        <span class="text-danger">Error: {{ $smsBalance['error'] }}</span>
                                                    @endif
                                                </h3>
                                            </div>
                                            <a href="{{ route('settings.index') }}#v-pills-sms" class="btn btn-light btn-sm font-weight-bold border shadow-sm">
                                                <i class="fa fa-refresh mr-1"></i> Refresh
                                            </a>
                                        </div>
                                    @endif

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Client ID</label>
                                                <input type="text" name="sms_client_id" class="form-control form-control-lg-custom" value="{{ $settings['sms_client_id'] ?? 'trumark' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>API Key</label>
                                                <input type="password" name="sms_api_key" class="form-control form-control-lg-custom" value="{{ $settings['sms_api_key'] ?? '' }}">
                                                <small class="text-muted"><i class="fa fa-info-circle mr-1"></i> Paste your Onfon SMS API Key here.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Authorized Sender ID</label>
                                                <input type="text" name="sms_sender_id" class="form-control form-control-lg-custom" value="{{ $settings['sms_sender_id'] ?? 'TRUMARK' }}">
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <div class="section-header">
                                        <i class="fa fa-toggle-on"></i>
                                        <h4>SMS Channel Controls</h4>
                                    </div>
                                    <div class="alert alert-warning py-2 border-0">
                                        <small><i class="fa fa-info-circle mr-1"></i> Turn SMS off globally, or allow/disallow SMS for each action. WhatsApp and Email are controlled separately.</small>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Master SMS Channel</label>
                                                <select name="sms_channel_enabled" class="form-control form-control-lg-custom">
                                                    <option value="1" {{ ($settings['sms_channel_enabled'] ?? '1') == '1' ? 'selected' : '' }}>ENABLED — SMS can be sent (per rules below)</option>
                                                    <option value="0" {{ ($settings['sms_channel_enabled'] ?? '1') == '0' ? 'selected' : '' }}>DISABLED — Block all SMS system-wide</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label class="font-weight-bold d-block mb-2">Allow SMS for these actions</label>
                                            <div class="p-3 bg-light rounded">
                                                <div class="row">
                                                    @php
                                                        $smsToggles = [
                                                            'welcome_channels_sms' => ['New lead welcome (customer)', '0'],
                                                            'survey_channels_sms' => ['Survey invitations (manual & automated)', '1'],
                                                            'followup_channels_sms' => ['Follow-up reminders (customer & officer)', '1'],
                                                            'sms_allow_manual' => ['Manual message from customer list', '1'],
                                                            'sms_allow_bulk' => ['Bulk SMS Reminders page', '1'],
                                                            'sms_allow_campaign' => ['Scheduled campaigns', '1'],
                                                            'sms_allow_lead_assignment' => ['Lead assigned alert (to officer)', '1'],
                                                            'sms_allow_staff' => ['Staff account create & password reset', '1'],
                                                        ];
                                                    @endphp
                                                    @foreach($smsToggles as $key => [$label, $default])
                                                        <div class="col-md-6 mb-2">
                                                            <div class="animated-checkbox">
                                                                <label>
                                                                    <input type="checkbox" name="{{ $key }}" value="1" {{ ($settings[$key] ?? $default) == '1' ? 'checked' : '' }}>
                                                                    <span class="label-text">{{ $label }}</span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <div class="section-header">
                                        <i class="fa fa-whatsapp"></i>
                                        <h4>WhatsApp Cloud API Configuration</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>WhatsApp Access Token (Permanent)</label>
                                                <input type="password" name="whatsapp_access_token" class="form-control form-control-lg-custom" value="{{ $settings['whatsapp_access_token'] ?? '' }}">
                                                <small class="text-muted">Generate this from the Meta for Developers portal.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>WhatsApp Phone Number ID</label>
                                                <input type="text" name="whatsapp_phone_number_id" class="form-control form-control-lg-custom" value="{{ $settings['whatsapp_phone_number_id'] ?? '' }}">
                                                <small class="text-muted">The unique ID for your registered WhatsApp number.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>WhatsApp Business Account ID</label>
                                                <input type="text" name="whatsapp_business_account_id" class="form-control form-control-lg-custom" value="{{ $settings['whatsapp_business_account_id'] ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="section-header">
                                        <i class="fa fa-list-alt"></i>
                                        <h4>Registered WhatsApp Templates</h4>
                                        <small class="text-muted ml-2">Templates must be created and approved in Meta Business Suite first.</small>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered">
                                                    <thead class="bg-light text-center small">
                                                        <tr>
                                                            <th>Template Name (Meta)</th>
                                                            <th>Language Code</th>
                                                            <th>Description / Purpose</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><input type="text" name="wa_template_survey_name" class="form-control form-control-sm" value="{{ $settings['wa_template_survey_name'] ?? 'survey_invitation' }}" placeholder="e.g. survey_invite"></td>
                                                            <td><input type="text" name="wa_template_survey_lang" class="form-control form-control-sm" value="{{ $settings['wa_template_survey_lang'] ?? 'en' }}" placeholder="e.g. en or sw"></td>
                                                            <td class="align-middle small">Used for automated satisfaction surveys</td>
                                                        </tr>
                                                        <tr>
                                                            <td><input type="text" name="wa_template_followup_name" class="form-control form-control-sm" value="{{ $settings['wa_template_followup_name'] ?? 'follow_up_reminder' }}" placeholder="e.g. general_followup"></td>
                                                            <td><input type="text" name="wa_template_followup_lang" class="form-control form-control-sm" value="{{ $settings['wa_template_followup_lang'] ?? 'en' }}" placeholder="e.g. en or sw"></td>
                                                            <td class="align-middle small">Used for automated follow-up reminders</td>
                                                        </tr>
                                                        <tr>
                                                            <td><input type="text" name="whatsapp_template_general" class="form-control form-control-sm" value="{{ $settings['whatsapp_template_general'] ?? 'general_broadcast' }}" placeholder="e.g. marketing_promo"></td>
                                                            <td><input type="text" name="wa_template_general_lang" class="form-control form-control-sm" value="{{ $settings['wa_template_general_lang'] ?? 'en' }}" placeholder="e.g. en or sw"></td>
                                                            <td class="align-middle small">Default template for manual broadcasts</td>
                                                        </tr>
                                                        <tr>
                                                            <td><input type="text" name="wa_template_welcome_name" class="form-control form-control-sm" value="{{ $settings['wa_template_welcome_name'] ?? 'general_broadcast' }}" placeholder="e.g. welcome_lead"></td>
                                                            <td><input type="text" name="wa_template_welcome_lang" class="form-control form-control-sm" value="{{ $settings['wa_template_welcome_lang'] ?? 'en' }}" placeholder="e.g. en or sw"></td>
                                                            <td class="align-middle small">Sent instantly to new lead registrations</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="alert alert-info py-2 mt-2">
                                                <i class="fa fa-info-circle mr-1"></i> <strong>Note:</strong> Ensure your Meta templates use <code>{{1}}</code> for Customer Name and <code>{{2}}</code> for Links/URLs.
                                            </div>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="section-header">
                                        <i class="fa fa-credit-card"></i>
                                        <h4>WhatsApp Bot — Payment & Pricing</h4>
                                        <small class="text-muted ml-2">Shown automatically after bot orders and on /payment command.</small>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>M-Pesa (Lipa Namba / Paybill)</label>
                                                <input type="text" name="payment_mpesa" class="form-control form-control-lg-custom" value="{{ $settings['payment_mpesa'] ?? '' }}" placeholder="e.g. Lipa Namba 123456 — TRUMARK">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Tigo Pesa</label>
                                                <input type="text" name="payment_tigo" class="form-control form-control-lg-custom" value="{{ $settings['payment_tigo'] ?? '' }}" placeholder="e.g. 0712 345 678 — TRUMARK">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Airtel Money</label>
                                                <input type="text" name="payment_airtel" class="form-control form-control-lg-custom" value="{{ $settings['payment_airtel'] ?? '' }}" placeholder="e.g. 0789 123 456 — TRUMARK">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Bank Name</label>
                                                <input type="text" name="payment_bank_name" class="form-control form-control-lg-custom" value="{{ $settings['payment_bank_name'] ?? '' }}" placeholder="e.g. CRDB Bank">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Bank Account Number</label>
                                                <input type="text" name="payment_bank_account" class="form-control form-control-lg-custom" value="{{ $settings['payment_bank_account'] ?? '' }}" placeholder="e.g. 0150123456789">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Account Holder Name</label>
                                                <input type="text" name="payment_bank_holder" class="form-control form-control-lg-custom" value="{{ $settings['payment_bank_holder'] ?? '' }}" placeholder="e.g. TRUMARK CO. LTD">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Delivery Fee Estimate (Dar)</label>
                                                <input type="number" name="wa_delivery_fee_dar" class="form-control form-control-lg-custom" value="{{ $settings['wa_delivery_fee_dar'] ?? '4000' }}" min="0">
                                                <small class="text-muted">Added to bot price estimates for Dar delivery.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Bot Price List (for auto estimates)</label>
                                                <textarea name="wa_bot_price_list" class="form-control" rows="8" placeholder="keywords comma-separated|Product Label|Unit Price">{{ $settings['wa_bot_price_list'] ?? '' }}</textarea>
                                                <small class="text-muted">One product per line. Format: <code>keyword1,keyword2|Product Name|2500</code>. Lines starting with # are ignored. Leave blank to use built-in defaults.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Survey & KPI -->
                                <div class="tab-pane fade" id="v-pills-survey" role="tabpanel">
                                    <div class="section-header">
                                        <i class="fa fa-check-square-o"></i>
                                        <h4>Survey & KPI Automation</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Public System URL</label>
                                                <input type="url" name="survey_public_url" class="form-control form-control-lg-custom" value="{{ $settings['survey_public_url'] ?? 'https://trumark.emca.tech' }}" placeholder="https://trumark.emca.tech">
                                                <small class="text-muted">Used for survey links sent via SMS, WhatsApp, and email. Example: <code>{{ rtrim($settings['survey_public_url'] ?? 'https://trumark.emca.tech', '/') }}/feedback/{uuid}</code></small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Automation Status</label>
                                                <select name="survey_enabled" class="form-control form-control-lg-custom">
                                                    <option value="1" {{ ($settings['survey_enabled'] ?? '0') == '1' ? 'selected' : '' }}>ENABLED</option>
                                                    <option value="0" {{ ($settings['survey_enabled'] ?? '0') == '0' ? 'selected' : '' }}>DISABLED</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Frequency</label>
                                                <select name="survey_frequency" id="survey_frequency" class="form-control form-control-lg-custom">
                                                    <option value="weekly" {{ ($settings['survey_frequency'] ?? 'weekly') == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                    <option value="monthly" {{ ($settings['survey_frequency'] ?? 'weekly') == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                    <option value="specific" {{ ($settings['survey_frequency'] ?? '') == 'specific' ? 'selected' : '' }}>Specific Date</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3 {{ ($settings['survey_frequency'] ?? 'weekly') == 'weekly' ? '' : 'd-none' }}" id="weekly_day_div">
                                            <div class="form-group">
                                                <label>Day of Week</label>
                                                <select name="survey_day_weekly" class="form-control form-control-lg-custom">
                                                    @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                                        <option value="{{ $day }}" {{ ($settings['survey_day_weekly'] ?? 'Monday') == $day ? 'selected' : '' }}>{{ $day }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3 {{ ($settings['survey_frequency'] ?? '') == 'monthly' ? '' : 'd-none' }}" id="monthly_day_div">
                                            <div class="form-group">
                                                <label>Day of Month</label>
                                                <select name="survey_day_monthly" class="form-control form-control-lg-custom">
                                                    <option value="first" {{ ($settings['survey_day_monthly'] ?? '1') == 'first' ? 'selected' : '' }}>First day of month</option>
                                                    @for($i=1; $i<=31; $i++)
                                                        <option value="{{ $i }}" {{ ($settings['survey_day_monthly'] ?? '1') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                                    @endfor
                                                    <option value="last" {{ ($settings['survey_day_monthly'] ?? '1') == 'last' ? 'selected' : '' }}>Last day of month</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3 {{ ($settings['survey_frequency'] ?? '') == 'specific' ? '' : 'd-none' }}" id="specific_date_div">
                                            <div class="form-group">
                                                <label>Choose Date</label>
                                                <input type="date" name="survey_specific_date" class="form-control form-control-lg-custom" value="{{ $settings['survey_specific_date'] ?? '' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Preferred Time</label>
                                                <input type="time" name="survey_time" class="form-control form-control-lg-custom" value="{{ $settings['survey_time'] ?? '09:00' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Channels</label>
                                                <div class="p-3 bg-light rounded d-flex justify-content-around">
                                                    <div class="animated-checkbox">
                                                        <label>
                                                            <input type="checkbox" name="survey_channels_whatsapp" value="1" {{ ($settings['survey_channels_whatsapp'] ?? '0') == '1' ? 'checked' : '' }}><span class="label-text">WhatsApp</span>
                                                        </label>
                                                    </div>
                                                    <div class="animated-checkbox">
                                                        <label>
                                                            <input type="checkbox" name="survey_channels_email" value="1" {{ ($settings['survey_channels_email'] ?? '1') == '1' ? 'checked' : '' }}><span class="label-text">Email</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <small class="text-muted">SMS for surveys is controlled under <b>SMS Gateway → SMS Channel Controls</b>.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Positive KPI Reward</label>
                                                <div class="input-group">
                                                    <input type="number" name="survey_kpi_points" class="form-control form-control-lg-custom" value="{{ $settings['survey_kpi_points'] ?? '5' }}">
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">Points</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-header mt-4">
                                        <i class="fa fa-envelope-o"></i>
                                        <h4>Messaging Templates</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="alert alert-info py-2">
                                                <small><i class="fa fa-info-circle mr-1"></i> Use <b>{name}</b> for customer name and <b>{link}</b> for the survey link.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <textarea name="survey_sms_template" class="form-control" rows="2">{{ $settings['survey_sms_template'] ?? 'Habari {name}, asante kwa kuchagua TRUMARK. Tafadhali tufahamishe jinsi ulivyohudumiwa hapa: {link}. Asante!' }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>WhatsApp Template Name (Meta)</label>
                                                <input type="text" name="wa_template_survey_name" class="form-control form-control-lg-custom" value="{{ $settings['wa_template_survey_name'] ?? 'survey_invitation' }}">
                                                <small class="text-muted">Ensure it uses <code>{{1}}</code> for Name and <code>{{2}}</code> for Link.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>WhatsApp Template Language</label>
                                                <input type="text" name="wa_template_survey_lang" class="form-control form-control-lg-custom" value="{{ $settings['wa_template_survey_lang'] ?? 'en' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Email Subject</label>
                                                <input type="text" name="survey_email_subject" class="form-control form-control-lg-custom" value="{{ $settings['survey_email_subject'] ?? 'Maoni Yako ni Muhimu kwa TRUMARK' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Email Body (Main Message)</label>
                                                <textarea name="survey_email_body" class="form-control" rows="4">{{ $settings['survey_email_body'] ?? 'Asante kwa kuendelea kuwa mteja wetu wa TRUMARK. Tunathamini sana ushirikiano wako na tungependa kusikia maoni yako kuhusu huduma tulizokupatia.' }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-header mt-4">
                                        <i class="fa fa-clock-o"></i>
                                        <h4>Follow-up Reminders</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Automation Status</label>
                                                <select name="followup_reminder_enabled" class="form-control form-control-lg-custom">
                                                    <option value="1" {{ ($settings['followup_reminder_enabled'] ?? '0') == '1' ? 'selected' : '' }}>ENABLED</option>
                                                    <option value="0" {{ ($settings['followup_reminder_enabled'] ?? '0') == '0' ? 'selected' : '' }}>DISABLED</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Daily Reminder Time</label>
                                                <input type="time" name="followup_reminder_time" class="form-control form-control-lg-custom" value="{{ $settings['followup_reminder_time'] ?? '08:30' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Channels</label>
                                                <div class="p-3 bg-light rounded d-flex justify-content-around">
                                                    <div class="animated-checkbox">
                                                        <label>
                                                            <input type="checkbox" name="followup_channels_whatsapp" value="1" {{ ($settings['followup_channels_whatsapp'] ?? '0') == '1' ? 'checked' : '' }}><span class="label-text">WhatsApp</span>
                                                        </label>
                                                    </div>
                                                    <div class="animated-checkbox">
                                                        <label>
                                                            <input type="checkbox" name="followup_channels_email" value="1" {{ ($settings['followup_channels_email'] ?? '1') == '1' ? 'checked' : '' }}><span class="label-text">Email</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <small class="text-muted">SMS for follow-ups is controlled under <b>SMS Gateway → SMS Channel Controls</b>.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <textarea name="followup_reminder_template" class="form-control" rows="2">{{ $settings['followup_reminder_template'] ?? 'Habari {name}, TRUMARK tunapenda kukukumbusha kuhusu huduma tulizozungumzia. Je, una maswali yoyote? Karibu!' }}</textarea>
                                                <small class="text-muted">Use <b>{name}</b> for customer name.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>WhatsApp Follow-up Template (Meta)</label>
                                                <input type="text" name="wa_template_followup_name" class="form-control form-control-lg-custom" value="{{ $settings['wa_template_followup_name'] ?? 'follow_up_reminder' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>WhatsApp Follow-up Language</label>
                                                <input type="text" name="wa_template_followup_lang" class="form-control form-control-lg-custom" value="{{ $settings['wa_template_followup_lang'] ?? 'en' }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="section-header mt-4">
                                        <i class="fa fa-commenting-o"></i>
                                        <h4>Additional Communication Templates</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Welcome Message Channels</label>
                                                <div class="p-3 bg-light rounded d-flex justify-content-around">
                                                    <div class="animated-checkbox">
                                                        <label>
                                                            <input type="checkbox" name="welcome_channels_whatsapp" value="1" {{ ($settings['welcome_channels_whatsapp'] ?? '1') == '1' ? 'checked' : '' }}><span class="label-text">WhatsApp</span>
                                                        </label>
                                                    </div>
                                                    <div class="animated-checkbox">
                                                        <label>
                                                            <input type="checkbox" name="welcome_channels_email" value="1" {{ ($settings['welcome_channels_email'] ?? '1') == '1' ? 'checked' : '' }}><span class="label-text">Email</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                <small class="text-muted">SMS for welcome messages is controlled under <b>SMS Gateway → SMS Channel Controls</b>.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Welcome Message Template</label>
                                                <textarea name="template_welcome_sms" class="form-control" rows="2">{{ $settings['template_welcome_sms'] ?? 'Hello, a new lead for {name} has been added to the TRUMARK system.' }}</textarea>
                                                <small class="text-muted">Use <b>{name}</b> for customer name.</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Welcome WhatsApp Template (Meta)</label>
                                                <input type="text" name="wa_template_welcome_name" class="form-control form-control-lg-custom" value="{{ $settings['wa_template_welcome_name'] ?? 'general_broadcast' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Welcome WhatsApp Language</label>
                                                <input type="text" name="wa_template_welcome_lang" class="form-control form-control-lg-custom" value="{{ $settings['wa_template_welcome_lang'] ?? 'en' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Lead Assigned SMS Alert</label>
                                                <textarea name="template_lead_assigned" class="form-control" rows="2">{{ $settings['template_lead_assigned'] ?? 'Hi {officer}, you have been assigned a new lead: {customer}. Please check your dashboard.' }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>KPI Adjustment Alert (Notification)</label>
                                                <textarea name="template_kpi_adjustment" class="form-control" rows="2">{{ $settings['template_kpi_adjustment'] ?? 'Your KPI points have been adjusted by {admin}. Reason: {reason}' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Performance Config -->
                                <div class="tab-pane fade" id="v-pills-performance" role="tabpanel">
                                    <div class="section-header">
                                        <i class="fa fa-line-chart"></i>
                                        <h4>KPI Points Strategy</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Points: Sale (New Customer)</label>
                                                <input type="number" name="kpi_sale_new" class="form-control form-control-lg-custom" value="{{ $settings['kpi_sale_new'] ?? '10' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Points: Sale (Repeat Customer)</label>
                                                <input type="number" name="kpi_sale_repeat" class="form-control form-control-lg-custom" value="{{ $settings['kpi_sale_repeat'] ?? '10' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Penalty: Late Update</label>
                                                <input type="number" name="kpi_late_update" class="form-control form-control-lg-custom" value="{{ $settings['kpi_late_update'] ?? '-10' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>High-Value Sale Threshold (Amount)</label>
                                                <div class="input-group">
                                                    <input type="number" name="kpi_high_value_threshold" class="form-control form-control-lg-custom" value="{{ $settings['kpi_high_value_threshold'] ?? '5000000' }}">
                                                    <div class="input-group-append"><span class="input-group-text">TZS</span></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Bonus Points: High-Value Sale</label>
                                                <input type="number" name="kpi_high_value_points" class="form-control form-control-lg-custom" value="{{ $settings['kpi_high_value_points'] ?? '10' }}">
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <div class="section-header mt-4">
                                        <i class="fa fa-list"></i>
                                        <h4>Specific Activity Points (Earning & Deductions)</h4>
                                    </div>
                                    <div class="row">
                                        @foreach(\App\Services\KpiService::POINTS as $key => $defaultVal)
                                            @php 
                                                $settingKey = 'kpi_pt_' . strtolower($key); 
                                                // Prettify label
                                                $label = ucwords(str_replace('_', ' ', $key));
                                                // We skip the legacy ones which already have dedicated inputs above
                                                $skip = ['SALE_CLOSED_NEW', 'SALE_CLOSED_REPEAT', 'LATE_UPDATE', 'SALE_HIGH_VALUE', 'POSITIVE_FEEDBACK'];
                                            @endphp
                                            @if(!in_array($key, $skip))
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label style="font-size: 11px;" class="font-weight-bold text-uppercase">{{ $label }}</label>
                                                    <input type="number" name="{{ $settingKey }}" class="form-control form-control-sm" value="{{ $settings[$settingKey] ?? $defaultVal }}">
                                                </div>
                                            </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    <hr>
                                    <div class="section-header">
                                        <i class="fa fa-trophy"></i>
                                        <h4>KPI Performance Levels (Thresholds)</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Sales Champion (Min Points)</label>
                                                <input type="number" name="kpi_level_champion" class="form-control form-control-lg-custom" value="{{ $settings['kpi_level_champion'] ?? '35000' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Excellent Performer (Min Points)</label>
                                                <input type="number" name="kpi_level_excellent" class="form-control form-control-lg-custom" value="{{ $settings['kpi_level_excellent'] ?? '25100' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Good Performer (Min Points)</label>
                                                <input type="number" name="kpi_level_good" class="form-control form-control-lg-custom" value="{{ $settings['kpi_level_good'] ?? '15100' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Fair Performance (Min Points)</label>
                                                <input type="number" name="kpi_level_fair" class="form-control form-control-lg-custom" value="{{ $settings['kpi_level_fair'] ?? '8100' }}">
                                                <small class="text-muted">Below this is "Needs Improvement"</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Operations & Goals -->
                                <div class="tab-pane fade" id="v-pills-ops" role="tabpanel">
                                    <div class="section-header">
                                        <i class="fa fa-calendar-o"></i>
                                        <h4>Operational Thresholds</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Business Closing Time</label>
                                                <input type="time" name="business_closing_time" class="form-control form-control-lg-custom" value="{{ $settings['business_closing_time'] ?? '18:00' }}">
                                                <small class="text-muted">Updates after this time are considered "Late".</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Daily Lead Goal (per Officer)</label>
                                                <input type="number" name="daily_lead_goal" class="form-control form-control-lg-custom" value="{{ $settings['daily_lead_goal'] ?? '10' }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Dormant Days Threshold</label>
                                                <div class="input-group">
                                                    <input type="number" name="dormant_days" class="form-control form-control-lg-custom" value="{{ $settings['dormant_days'] ?? '30' }}">
                                                    <div class="input-group-append"><span class="input-group-text">Days</span></div>
                                                </div>
                                                <small class="text-muted">When a lead becomes "Inactive".</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security & Session -->
                                <div class="tab-pane fade" id="v-pills-security" role="tabpanel">
                                    <div class="section-header">
                                        <i class="fa fa-shield"></i>
                                        <h4>Security & Access Control</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Password Expiry Period</label>
                                                <div class="input-group">
                                                    <input type="number" name="password_expiry_days" class="form-control form-control-lg-custom" value="{{ $settings['password_expiry_days'] ?? '90' }}">
                                                    <div class="input-group-append"><span class="input-group-text">Days</span></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>System Session Timeout</label>
                                                <div class="input-group">
                                                    <input type="number" name="session_timeout" class="form-control form-control-lg-custom" value="{{ $settings['session_timeout'] ?? '120' }}">
                                                    <div class="input-group-append"><span class="input-group-text">Minutes</span></div>
                                                </div>
                                                <small class="text-muted">Auto-logout after inactivity.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- UI & Branding -->
                                <div class="tab-pane fade" id="v-pills-branding" role="tabpanel">
                                    <div class="section-header">
                                        <i class="fa fa-paint-brush"></i>
                                        <h4>UI & Visual Branding</h4>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>System Accent Color</label>
                                                <div class="d-flex align-items-center">
                                                    <input type="color" name="brand_color" class="form-control mr-2" value="{{ $settings['brand_color'] ?? '#940000' }}" style="height: 45px; width: 80px; padding: 5px; border-radius: 6px;">
                                                    <input type="text" class="form-control form-control-lg-custom" value="{{ $settings['brand_color'] ?? '#940000' }}" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Dashboard Welcome Title</label>
                                                <input type="text" name="welcome_message" class="form-control form-control-lg-custom" value="{{ $settings['welcome_message'] ?? 'Welcome back to TRUMARK CRM!' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Keep active tab on refresh
    $(document).ready(function() {
        if (location.hash) {
            $('a[href="' + location.hash + '"]').tab('show');
        }
        $(document.body).on("click", "a[data-toggle='pill']", function(event) {
            location.hash = this.getAttribute("href");
        });

        // Survey Frequency Toggle
        function toggleSurveyFields() {
            var freq = $('#survey_frequency').val();
            $('#weekly_day_div, #monthly_day_div, #specific_date_div').addClass('d-none');
            
            if (freq === 'weekly') {
                $('#weekly_day_div').removeClass('d-none');
            } else if (freq === 'monthly') {
                $('#monthly_day_div').removeClass('d-none');
            } else if (freq === 'specific') {
                $('#specific_date_div').removeClass('d-none');
            }
        }

        $('#survey_frequency').on('change', toggleSurveyFields);
        toggleSurveyFields(); // Init
    });
</script>
@endsection
