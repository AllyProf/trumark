<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SmsLog;
use App\Services\SmsService;
use App\Services\KpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    protected $sms;
    protected $whatsapp;

    public function __construct(SmsService $sms, \App\Services\WhatsAppService $whatsapp)
    {
        $this->sms = $sms;
        $this->whatsapp = $whatsapp;
    }

    public function index()
    {
        $user     = Auth::user();
        $branchId = $this->getActiveBranchId();

        $query = Customer::with('salesOfficer')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($user->role === 'sales_officer') {
            $query->where('sales_officer_id', Auth::id());
        }

        $customers = $query->latest()->paginate(10);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        $user = Auth::user();
        $officers = [];
        if ($user->role === 'super_admin' || $user->role === 'manager') {
            $officers = \App\Models\User::with('branch')->whereIn('role', ['sales_officer', 'manager', 'super_admin'])
                ->when($user->role === 'manager', function($q) use ($user) {
                    return $q->where('branch_id', $user->branch_id);
                })
                ->get();
        }
        return view('customers.create', compact('officers'));
    }

    public function show(Customer $customer)
    {
        $customer->load('salesOfficer');
        return view('customers.show', compact('customer'));
    }

    public function sendSms(Request $request, Customer $customer)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'channel' => 'nullable|string|in:sms,whatsapp'
        ]);

        $channel = $request->get('channel', 'sms');
        $message = str_replace('{name}', $customer->name, $request->message);
        
        if ($channel === 'whatsapp') {
            $result = $this->whatsapp->sendMessage($customer->phone, $message);
            $logMsg = "[WhatsApp] " . $message;
        } else {
            $result = $this->sms->sendSms($customer->phone, $message);
            $logMsg = $message;
        }

        // Log the communication
        SmsLog::create([
            'customer_id' => $customer->id,
            'sender_id'   => Auth::id(),
            'phone'       => $customer->phone,
            'message'     => $logMsg,
            'status'      => $result['success'] ? 'sent' : 'failed',
            'response'    => $result['response'] ?? null,
        ]);

        if ($result['success']) {
            $channelName = strtoupper($channel);
            return back()->with('success', "✅ {$channelName} sent successfully to {$customer->name} ({$customer->phone})!");
        } else {
            return back()->with('error', "❌ Failed to send. " . ($result['message'] ?? 'Please check the phone number and try again.'));
        }
    }

    /**
     * AJAX endpoint: real-time duplicate check on phone / email.
     */
    public function checkDuplicate(Request $request)
    {
        $field = $request->input('field'); // 'phone' or 'email'
        $value = trim($request->input('value', ''));

        if (!$value || !in_array($field, ['phone', 'email'])) {
            return response()->json(['duplicate' => false]);
        }

        $query = Customer::with(['salesOfficer', 'salesOfficer.branch']);

        if ($field === 'phone') {
            $clean = preg_replace('/[\s\-\(\)\+]/', '', $value);
            $query->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'(',''),'+','') LIKE ?", ["%{$clean}%"]);
        } else {
            $query->where('email', $value);
        }

        $existing = $query->first();

        if ($existing) {
            return response()->json([
                'duplicate'   => true,
                'owner'       => $existing->salesOfficer?->name ?? 'Unknown Officer',
                'branch'      => $existing->salesOfficer?->branch?->name ?? 'Unknown Branch',
                'customer_id' => $existing->id,
                'customer_name' => $existing->name,
            ]);
        }

        return response()->json(['duplicate' => false]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|max:20',
            'email'        => 'nullable|email|max:255',
            'status'       => 'required|string',
            'buying_stage' => 'required|string',
        ]);

        $data = $request->except(['phone_number', 'alternative_phone_number']);
        $data['estimated_monthly_value'] = $request->estimated_monthly_value ?? 0;

        // ─── HARD UNIQUENESS: Phone & Email ──────────────────────────────────
        $cleanPhone = preg_replace('/[\s\-\(\)\+]/', '', $request->phone);

        $phoneExists = Customer::with(['salesOfficer', 'salesOfficer.branch'])
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone,' ',''),'-',''),'(',''),'+','') LIKE ?", ["%{$cleanPhone}%"])
            ->first();

        if ($phoneExists) {
            $owner  = $phoneExists->salesOfficer?->name ?? 'Unknown Officer';
            $branch = $phoneExists->salesOfficer?->branch?->name ?? 'Unknown Branch';
            return back()->withInput()->with('duplicate_error', [
                'type'        => 'hard',
                'message'     => "Phone number {$request->phone} is already registered under \"{ $phoneExists->name}\" — owned by {$owner} ({$branch} Branch).",
                'owner'       => $owner,
                'branch'      => $branch,
                'matched_by'  => "Phone: {$request->phone}",
                'customer_id' => $phoneExists->id,
            ]);
        }

        if ($request->filled('email')) {
            $emailExists = Customer::with(['salesOfficer', 'salesOfficer.branch'])
                ->where('email', trim($request->email))
                ->first();

            if ($emailExists) {
                $owner  = $emailExists->salesOfficer?->name ?? 'Unknown Officer';
                $branch = $emailExists->salesOfficer?->branch?->name ?? 'Unknown Branch';
                return back()->withInput()->with('duplicate_error', [
                    'type'        => 'hard',
                    'message'     => "Email {$request->email} is already registered under \"{$emailExists->name}\" — owned by {$owner} ({$branch} Branch).",
                    'owner'       => $owner,
                    'branch'      => $branch,
                    'matched_by'  => "Email: {$request->email}",
                    'customer_id' => $emailExists->id,
                ]);
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        $user = Auth::user();
        $officerId = $user->id;
        $branchId = $user->branch_id;
        $assignedOfficer = $user;
        
        if (($user->role === 'super_admin' || $user->role === 'manager') && $request->filled('sales_officer_id')) {
            $officerId = $request->sales_officer_id;
            if ($officerId != $user->id) {
                $assignedOfficer = \App\Models\User::find($officerId);
                if ($assignedOfficer) {
                    $branchId = $assignedOfficer->branch_id;
                }
            }
        }

        $customer = Customer::create(array_merge($data, [
            'sales_officer_id' => $officerId,
            'branch_id'        => $branchId,
            'is_draft'         => false,
        ]));

        // ── Award KPI Points ───────────────────────────────────────────
        $settings = \App\Models\SystemSetting::pluck('value', 'key');
        $closingTime = $settings['business_closing_time'] ?? '18:00';
        $closingHour = (int)explode(':', $closingTime)[0];

        if ($customer->status === 'Potential Customer' || $customer->status === 'New Customer') {
            \App\Services\KpiService::recordActivity('REG_NEW_POTENTIAL', $officerId, $customer->id);
        }

        if ($customer->source === 'Referral') {
            \App\Services\KpiService::recordActivity('REFERRAL_EXISTING', $officerId, $customer->id);
        }

        if ($customer->tin_no && $customer->email && $customer->address && $customer->region) {
            \App\Services\KpiService::recordActivity('COMPLETE_PROFILE', $officerId, $customer->id);
        }

        // Organization Lead check (School/Company)
        if (in_array($customer->type, ['School', 'Company'])) {
            \App\Services\KpiService::recordActivity('NEW_ORG_LEAD', $officerId, $customer->id);
        }

        // Daily Update check
        if (now()->hour < $closingHour) {
            \App\Services\KpiService::recordActivity('DAILY_UPDATE_BEFORE_6', $user->id, $customer->id);
        } else {
            \App\Services\KpiService::recordActivity('LATE_UPDATE', $user->id, $customer->id);
        }
        // ───────────────────────────────────────────────────────────────

        // Notify the assigned sales officer if it's someone else
        if ($assignedOfficer && $assignedOfficer->id != $user->id) {
            $assignedOfficer->notify(new \App\Notifications\LeadAssignedNotification($customer));
        }

        $welcomeTemplate = $settings['template_welcome_sms'] ?? 'Hello, a new lead for {name} has been added to the TRUMARK system.';
        $message = str_replace('{name}', $customer->name, $welcomeTemplate);
        
        $result = $this->sms->sendSms($customer->phone, $message);
        
        // Log the welcome SMS
        SmsLog::create([
            'customer_id' => $customer->id,
            'sender_id'   => Auth::id(),
            'phone'       => $customer->phone,
            'message'     => $message,
            'status'      => $result['success'] ? 'sent' : 'failed',
            'response'    => $result['response'] ?? null,
        ]);

        $msg = 'Customer registered successfully and SMS sent!';

        return redirect()->route('customers.index')->with('success', $msg);
    }

    public function edit(Customer $customer)
    {
        $user = Auth::user();
        $officers = [];
        if ($user->role === 'super_admin' || $user->role === 'manager') {
            $officers = \App\Models\User::with('branch')
                ->whereIn('role', ['sales_officer', 'manager', 'super_admin'])
                ->when($user->role === 'manager', fn($q) => $q->where('branch_id', $user->branch_id))
                ->get();
        }
        return view('customers.edit', compact('customer', 'officers'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|max:20',
            'email'        => 'nullable|email|max:255',
            'status'       => 'required|string',
            'buying_stage' => 'required|string',
        ]);

        $data = $request->except(['_token', '_method', 'phone_number', 'alternative_phone_number']);
        $data['estimated_monthly_value'] = $request->estimated_monthly_value ?? 0;

        $user = Auth::user();

        // Handle officer reassignment (super_admin & manager only)
        if (($user->role === 'super_admin' || $user->role === 'manager') && $request->filled('sales_officer_id')) {
            $newOfficerId = $request->sales_officer_id;
            if ($newOfficerId != $customer->sales_officer_id) {
                $newOfficer = \App\Models\User::find($newOfficerId);
                if ($newOfficer) {
                    $data['branch_id'] = $newOfficer->branch_id;
                    // Notify the newly assigned officer
                    $newOfficer->notify(new \App\Notifications\LeadAssignedNotification($customer));
                }
            }
        }

        $oldStage = $customer->buying_stage;
        $oldStatus = $customer->status;
        $customer->update($data);

        // ── Award KPI Points for Stage Changes ────────────────────────
        $settings = \App\Models\SystemSetting::pluck('value', 'key');
        $highValueThreshold = (int)($settings['kpi_high_value_threshold'] ?? 5000000);

        if ($oldStage !== $customer->buying_stage) {
            if ($customer->buying_stage === 'Quotation Sent') {
                \App\Services\KpiService::recordActivity('QUOTATION_SENT', $user->id, $customer->id);
            } elseif ($customer->buying_stage === 'Delivered') {
                \App\Services\KpiService::recordActivity('PAYMENT_COLLECTED', $user->id, $customer->id);
            } elseif ($customer->buying_stage === 'Closed Won') {
                $code = ($customer->status === 'Existing Customer') ? 'SALE_CLOSED_REPEAT' : 'SALE_CLOSED_NEW';
                \App\Services\KpiService::recordActivity($code, $user->id, $customer->id);
                
                // High Value check
                if ($customer->estimated_monthly_value >= $highValueThreshold) {
                    \App\Services\KpiService::recordActivity('SALE_HIGH_VALUE', $user->id, $customer->id);
                }

                // Social Media Conversion check
                if (in_array($customer->source, ['WhatsApp', 'Instagram', 'Facebook'])) {
                    \App\Services\KpiService::recordActivity('SOCIAL_MEDIA_CONV', $user->id, $customer->id);
                }
            }
        }

        if ($oldStatus === 'Inactive Customer' && in_array($customer->status, ['Existing Customer', 'Potential Customer'])) {
            \App\Services\KpiService::recordActivity('RECOVER_INACTIVE', $user->id, $customer->id);
        }
        // ───────────────────────────────────────────────────────────────

        return redirect()->route('customers.show', $customer->id)->with('success', '✅ Customer record updated successfully!');
    }

    public function quickUpdate(Request $request, Customer $customer)
    {
        $request->validate([
            'status'            => 'required|string',
            'buying_stage'      => 'required|string',
            'next_follow_up_date' => 'nullable|date',
            'notes'             => 'nullable|string',
        ]);

        $oldStage = $customer->buying_stage;
        $oldStatus = $customer->status;
        $customer->update([
            'status'              => $request->status,
            'buying_stage'        => $request->buying_stage,
            'next_follow_up_date' => $request->next_follow_up_date,
            'notes'               => $request->notes,
        ]);

        // ── Award KPI Points ───────────────────────────────────────────
        $user = Auth::user();
        $settings = \App\Models\SystemSetting::pluck('value', 'key');
        $closingTime = $settings['business_closing_time'] ?? '18:00';
        $closingHour = (int)explode(':', $closingTime)[0];

        if ($oldStage !== $customer->buying_stage) {
            if ($customer->buying_stage === 'Quotation Sent') {
                \App\Services\KpiService::recordActivity('QUOTATION_SENT', $user->id, $customer->id);
            } elseif ($customer->buying_stage === 'Delivered') {
                \App\Services\KpiService::recordActivity('PAYMENT_COLLECTED', $user->id, $customer->id);
            } elseif ($customer->buying_stage === 'Closed Won') {
                $code = ($customer->status === 'Existing Customer') ? 'SALE_CLOSED_REPEAT' : 'SALE_CLOSED_NEW';
                \App\Services\KpiService::recordActivity($code, $user->id, $customer->id);

                if (in_array($customer->source, ['WhatsApp', 'Instagram', 'Facebook'])) {
                    \App\Services\KpiService::recordActivity('SOCIAL_MEDIA_CONV', $user->id, $customer->id);
                }
            }
        }

        if ($oldStatus === 'Inactive Customer' && in_array($customer->status, ['Existing Customer', 'Potential Customer'])) {
            \App\Services\KpiService::recordActivity('RECOVER_INACTIVE', $user->id, $customer->id);
        }

        // Check for late update
        if (now()->hour >= $closingHour) {
            \App\Services\KpiService::recordActivity('LATE_UPDATE', $user->id, $customer->id);
        } else {
            \App\Services\KpiService::recordActivity('DAILY_UPDATE_BEFORE_6', $user->id, $customer->id);
        }
        // ───────────────────────────────────────────────────────────────

        return back()->with('success', 'Lead updated successfully!');
    }

    public function newTransaction(Request $request, Customer $customer)
    {
        $request->validate([
            'new_value'            => 'required|numeric|min:0',
            'requirements'         => 'required|array',
            'detailed_requirement' => 'nullable|string',
        ]);

        if ($customer->buying_stage === 'Closed Won') {
            $pastProducts = is_array($customer->requirements) ? implode(', ', $customer->requirements) : 'Not specified';
            $pastDetails = $customer->detailed_requirement ?? 'None';
            
            \App\Models\Sale::create([
                'customer_id'    => $customer->id,
                'user_id'         => $customer->sales_officer_id ?? Auth::id(),
                'total_amount'   => $customer->estimated_monthly_value,
                'payment_status' => 'Paid',
                'notes'          => "Products: $pastProducts. Details: $pastDetails",
            ]);
        }

        // 2. Reset Pipeline for New Order
        $customer->update([
            'status'                  => 'Existing Customer',
            'buying_stage'            => 'Inquiry',
            'estimated_monthly_value' => $request->new_value,
            'requirements'            => $request->requirements,
            'detailed_requirement'    => $request->detailed_requirement,
            'notes'                   => "Repeat Order started on " . now()->format('d M Y') . ". " . $customer->notes,
        ]);

        return redirect()->route('customers.show', $customer->id)->with('success', '✅ New Sales Cycle started for this existing customer!');
    }

    public function followUps()
    {
        $user     = Auth::user();
        $branchId = $this->getActiveBranchId();

        $query = Customer::with('salesOfficer')
            ->whereNotNull('next_follow_up_date')
            ->whereNotIn('buying_stage', ['Closed Won', 'Closed Lost'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($user->role === 'sales_officer') {
            $query->where('sales_officer_id', Auth::id());
        }

        $settings = \App\Models\SystemSetting::pluck('value', 'key');
        $template = $settings['followup_reminder_template'] ?? 'Habari {name}, TRUMARK tunapenda kukukumbusha kuhusu huduma tulizozungumzia. Je, una maswali yoyote? Karibu!';

        $customers = $query->orderBy('next_follow_up_date', 'asc')->paginate(10);
        
        $dueCount = Customer::where('next_follow_up_date', '<=', now()->toDateString())
            ->whereNotIn('buying_stage', ['Closed Won', 'Closed Lost'])
            ->where('is_draft', false)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($user->role === 'sales_officer', fn($q) => $q->where('sales_officer_id', $user->id))
            ->count();

        return view('customers.follow_ups', compact('customers', 'template', 'dueCount'));
    }

    public function salesRecords()
    {
        $user     = Auth::user();
        $branchId = $this->getActiveBranchId();

        $query = Customer::with('salesOfficer')
            ->whereIn('buying_stage', ['Order Confirmed', 'Delivered', 'Closed Won'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($user->role === 'sales_officer') {
            $query->where('sales_officer_id', Auth::id());
        }

        $customers = $query->orderBy('updated_at', 'desc')->paginate(10);
        return view('customers.sales_records', compact('customers'));
    }

    public function smsReminders()
    {
        $user     = Auth::user();
        $branchId = $this->getActiveBranchId();

        $query = Customer::with('salesOfficer')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($user->role === 'sales_officer') {
            $query->where('sales_officer_id', Auth::id());
        }

        $customers = $query->orderBy('name', 'asc')->paginate(10);
        return view('customers.sms_reminders', compact('customers'));
    }

    public function sendBulkSms(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|array',
            'message' => 'required|string|max:500',
            'channels' => 'required|array|min:1'
        ]);

        $successCount = 0;
        $failCount = 0;
        $channels = $request->channels;

        foreach ($request->customer_ids as $id) {
            $customer = Customer::find($id);
            if ($customer) {
                $message = str_replace('{name}', $customer->name, $request->message);
                
                // Send via SMS if selected
                if (in_array('sms', $channels)) {
                    $result = $this->sms->sendSms($customer->phone, $message);
                    SmsLog::create([
                        'customer_id' => $customer->id,
                        'sender_id'   => Auth::id(),
                        'phone'       => $customer->phone,
                        'message'     => $message,
                        'status'      => $result['success'] ? 'sent' : 'failed',
                        'response'    => $result['response'] ?? null,
                    ]);
                    if ($result['success']) $successCount++; else $failCount++;
                }

                // Send via WhatsApp if selected
                if (in_array('whatsapp', $channels)) {
                    $result = $this->whatsapp->sendMessage($customer->phone, $message);
                    SmsLog::create([
                        'customer_id' => $customer->id,
                        'sender_id'   => Auth::id(),
                        'phone'       => $customer->phone,
                        'message'     => "[WhatsApp] " . $message,
                        'status'      => $result['success'] ? 'sent' : 'failed',
                        'response'    => $result['response'] ?? null,
                    ]);
                    // Only count once for the overall status if we sent both, 
                    // but for simplicity we'll just track if at least one worked? 
                    // Or track them separately. Let's keep it simple.
                }
            }
        }

        $msg = "✅ Messages dispatched to selected customers via: " . implode(', ', array_map('strtoupper', $channels));
        return back()->with('success', $msg);
    }

    public function sendSurvey(Customer $customer)
    {
        if (!$customer->survey_uuid) {
            $customer->survey_uuid = (string) \Illuminate\Support\Str::uuid();
            $customer->save();
        }

        $settings = \App\Models\SystemSetting::pluck('value', 'key');
        $useSms = ($settings['survey_channels_sms'] ?? '1') === '1';
        $useWhatsapp = ($settings['survey_channels_whatsapp'] ?? '0') === '1';
        $url = route('feedback.show', ['uuid' => $customer->survey_uuid]);
        
        $smsTemplate = $settings['survey_sms_template'] ?? 'Habari {name}, asante kwa kuchagua TRUMARK. Tafadhali tufahamishe jinsi ulivyohudumiwa hapa: {link}. Asante!';
        $message = str_replace(['{name}', '{link}'], [$customer->name, $url], $smsTemplate);

        $channelsSent = [];

        // 1. Send SMS
        if ($useSms) {
            $result = $this->sms->sendSms($customer->phone, $message);
            SmsLog::create([
                'customer_id' => $customer->id,
                'sender_id'   => Auth::id(),
                'phone'       => $customer->phone,
                'message'     => $message,
                'status'      => $result['success'] ? 'sent' : 'failed',
                'response'    => $result['response'] ?? null,
            ]);
            if ($result['success']) $channelsSent[] = 'SMS';
        }

        // 2. Send WhatsApp
        if ($useWhatsapp && $customer->phone) {
            $templateName = $settings['wa_template_survey_name'] ?? 'survey_invitation';
            $languageCode = $settings['wa_template_survey_lang'] ?? 'en';
            
            $result = $this->whatsapp->sendTemplateMessage($customer->phone, $templateName, $languageCode, [$customer->name, $url]);
            SmsLog::create([
                'customer_id' => $customer->id,
                'sender_id'   => Auth::id(),
                'phone'       => $customer->phone,
                'message'     => "[WhatsApp Template: {$templateName}] " . $url,
                'status'      => $result['success'] ? 'sent' : 'failed',
                'response'    => $result['response'] ?? null,
            ]);
            if ($result['success']) $channelsSent[] = 'WhatsApp';
        }

        // 3. Send Email
        if ($customer->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($customer->email)->send(new \App\Mail\SurveyInvitation($customer));
                $channelsSent[] = 'Email';
            } catch (\Exception $e) {
                // Log error but continue
            }
        }

        // Update timestamp
        $customer->update(['last_survey_sent_at' => now()]);

        $sentVia = count($channelsSent) > 0 ? "via " . implode(' and ', $channelsSent) : "";
        return back()->with('success', "✅ Survey invitation dispatched to {$customer->name} {$sentVia}.");
    }

    public function sendAllReminders(Request $request)
    {
        $settings = \App\Models\SystemSetting::pluck('value', 'key');
        $template = $request->input('message') ?: ($settings['followup_reminder_template'] ?? 'Habari {name}, TRUMARK tunapenda kukukumbusha kuhusu huduma tulizozungumzia. Je, una maswali yoyote? Karibu!');

        $user     = Auth::user();
        $branchId = $this->getActiveBranchId();

        $query = Customer::where('next_follow_up_date', '<=', now()->toDateString())
            ->whereNotIn('buying_stage', ['Closed Won', 'Closed Lost'])
            ->where('is_draft', false)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId));

        if ($user->role === 'sales_officer') {
            $query->where('sales_officer_id', $user->id);
        }

        $customers = $query->get();

        if ($customers->isEmpty()) {
            return back()->with('error', 'No due or overdue follow-up reminders found.');
        }

        $successCount = 0;
        foreach ($customers as $customer) {
            $message = str_replace('{name}', $customer->name, $template);
            $result = $this->sms->sendSms($customer->phone, $message);
            
            SmsLog::create([
                'customer_id' => $customer->id,
                'sender_id'   => Auth::id(),
                'phone'       => $customer->phone,
                'message'     => $message,
                'status'      => $result['success'] ? 'sent' : 'failed',
                'response'    => $result['response'] ?? null,
            ]);

            if ($result['success']) $successCount++;
        }

        return back()->with('success', "✅ Sent $successCount follow-up reminders successfully!");
    }

    /**
     * Show the import page
     */
    public function import()
    {
        return view('customers.import');
    }

    /**
     * Download the professional Excel template
     */
    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\CustomerTemplateExport, 'trumark_lead_template.xlsx');
    }

    /**
     * Handle the bulk Excel/CSV upload
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ]);

        $file = $request->file('import_file');
        
        $importedCount = 0;
        $errorCount = 0;
        $user = Auth::user();

        try {
            // Use Excel facade to read the file
            $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $file);
            
            if (empty($data) || empty($data[0])) {
                return back()->with('error', 'The uploaded file is empty.');
            }

            $rows = $data[0];
            $header = array_shift($rows); // Remove header row

            \Illuminate\Support\Facades\DB::beginTransaction();
            
            foreach ($rows as $row) {
                // Mapping (index-based to be safe with Excel library)
                // 0:Type, 1:Name, 2:Contact, 3:Position, 4:Phone, 5:Email, 6:Region, 7:District, 8:Source, 9:Status, 10:Req, 11:SchoolLevel
                
                $name = trim($row[1] ?? '');
                $phone = trim($row[4] ?? '');

                if (empty($name) || empty($phone)) {
                    $errorCount++;
                    continue;
                }

                $type = trim($row[0] ?? 'Potential Customer');
                
                $customer = Customer::create([
                    'sales_officer_id' => $user->id,
                    'branch_id'        => $user->branch_id,
                    'type'             => $type,
                    'name'             => $name,
                    'contact_person'   => trim($row[2] ?? ''),
                    'position'         => trim($row[3] ?? ''),
                    'phone'            => $phone,
                    'email'            => trim($row[5] ?? ''),
                    'region'           => trim($row[6] ?? ''),
                    'district'         => trim($row[7] ?? ''),
                    'source'           => trim($row[8] ?? 'Bulk Import'),
                    'status'           => trim($row[9] ?? 'Potential Customer'),
                    'requirements'     => trim($row[10] ?? ''),
                    'school_level'     => trim($row[11] ?? ''),
                    'buying_stage'     => 'Inquiry',
                ]);

                if ($customer) {
                    $importedCount++;
                    \App\Services\KpiService::recordActivity('REG_NEW_POTENTIAL', $user->id, $customer->id);
                    if (in_array(strtolower($type), ['school', 'company', 'organization'])) {
                        \App\Services\KpiService::recordActivity('NEW_ORG_LEAD', $user->id, $customer->id);
                    }
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('customers.index')->with('success', "✅ Imported {$importedCount} leads successfully.");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Error during import: ' . $e->getMessage());
        }
    }
}
