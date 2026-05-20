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
            'message' => 'required|string|max:1000',
        ]);

        $message = str_replace('{name}', $customer->name, $request->message);
        $results = [];

        // 1. Send SMS
        $smsResult = $this->sms->sendSms($customer->phone, $message);
        SmsLog::create([
            'customer_id' => $customer->id,
            'sender_id'   => Auth::id(),
            'phone'       => $customer->phone,
            'message'     => "[SMS] " . $message,
            'status'      => $smsResult['success'] ? 'sent' : 'failed',
            'response'    => isset($smsResult['response']) ? (is_array($smsResult['response']) ? json_encode($smsResult['response']) : $smsResult['response']) : null,
        ]);
        if ($smsResult['success']) $results[] = 'SMS';

        // 2. Send WhatsApp (Template)
        $waTemplate = $request->get('wa_template', 'general_broadcast');
        
        // Smart Mapping for Meta Templates
        $waParams = ['customer_name' => $customer->name];
        
        if ($waTemplate === 'general_broadcast') {
            $waParams['message_content'] = preg_replace('/\s+/', ' ', $message);
        } elseif ($waTemplate === 'survey_invitation') {
            $waParams['survey_link'] = " " . route('feedback.show', ['uuid' => $customer->survey_uuid]) . " ";
        }
        // Note: payment_reminder, quote_ready, follow_up_reminder only take customer_name in Meta Manager

        $waResult = $this->whatsapp->sendTemplateMessage($customer->phone, $waTemplate, 'en', $waParams);
        
        SmsLog::create([
            'customer_id' => $customer->id,
            'sender_id'   => Auth::id(),
            'phone'       => $customer->phone,
            'message'     => "[WhatsApp: {$waTemplate}] " . $message,
            'status'      => $waResult['success'] ? 'sent' : 'failed',
            'response'    => isset($waResult['response']) ? (is_array($waResult['response']) ? json_encode($waResult['response']) : $waResult['response']) : null,
        ]);
        if ($waResult['success']) $results[] = 'WhatsApp';

        // 3. Send Email
        if ($customer->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($customer->email)->send(new \App\Mail\CustomerReminderMail($customer, $message));
                $results[] = 'Email';
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Direct Email failed: " . $e->getMessage());
            }
        }

        $sentString = implode(', ', $results);
        return back()->with('success', "✅ Message broadcast successful! Sent via: {$sentString}");
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
            'status'       => 'nullable|string',
            'buying_stage' => 'nullable|string',
        ]);

        $data = $request->except(['phone_number', 'alternative_phone_number']);
        $data['estimated_monthly_value'] = $request->estimated_monthly_value ?? 0;
        $data['status'] = $request->status ?: 'New Customer';
        $data['buying_stage'] = $request->buying_stage ?: 'Inquiry';

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

        // --- SMART BRANCH FALLBACK (Fixes 1452) ---
        // Ensure the branchId exists in branches table. If not, fallback to first available.
        if (!\App\Models\Branch::where('id', $branchId)->exists()) {
            $fallbackBranch = \App\Models\Branch::first();
            $branchId = $fallbackBranch ? $fallbackBranch->id : null;
        }
        // ------------------------------------------

        $customer = Customer::create(array_merge($data, [
            'sales_officer_id' => $officerId,
            'branch_id'        => $branchId,
            'is_draft'         => false,
        ]));

        \App\Models\AuditLog::record("Registered new customer lead: {$customer->name} (Buying Stage: {$customer->buying_stage})", 'Customers');

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
            $assignedOfficer->notify(new \App\Notifications\LeadAssignedSmsNotification($customer));
        }

        $welcomeTemplate = $settings['template_welcome_sms'] ?? 'Hello {name}, thank you for choosing TRUMARK Co. LTD. We have received your inquiry and our team is working on it. Welcome!';
        $finalWelcomeMessage = str_replace('{name}', $customer->name, $welcomeTemplate);
        
        // 1. Send SMS (Direct)
        $smsResult = $this->sms->sendSms($customer->phone, $finalWelcomeMessage);
        SmsLog::create([
            'customer_id' => $customer->id,
            'sender_id'   => Auth::id(),
            'phone'       => $customer->phone,
            'message'     => "[SMS Welcome] " . $finalWelcomeMessage,
            'status'      => $smsResult['success'] ? 'sent' : 'failed',
            'response'    => isset($smsResult['response']) ? (is_array($smsResult['response']) ? json_encode($smsResult['response']) : $smsResult['response']) : null,
        ]);

        // 2. Send WhatsApp (Template)
        $waTemplate = $settings['wa_template_welcome_name'] ?? ($settings['whatsapp_template_general'] ?? 'general_broadcast');
        $waLang = $settings['wa_template_welcome_lang'] ?? 'en';
        
        $cleanWaMsg = preg_replace('/\s+/', ' ', $finalWelcomeMessage);
        $waResult = $this->whatsapp->sendTemplateMessage($customer->phone, $waTemplate, $waLang, [
            'customer_name' => $customer->name,
            'message_content' => $cleanWaMsg
        ]);
        SmsLog::create([
            'customer_id' => $customer->id,
            'sender_id'   => Auth::id(),
            'phone'       => $customer->phone,
            'message'     => "[WhatsApp Welcome: {$waTemplate}] " . $finalWelcomeMessage,
            'status'      => $waResult['success'] ? 'sent' : 'failed',
            'response'    => isset($waResult['response']) ? (is_array($waResult['response']) ? json_encode($waResult['response']) : $waResult['response']) : null,
        ]);

        // 3. Send Email
        if ($customer->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($customer->email)->send(new \App\Mail\CustomerReminderMail($customer, $finalWelcomeMessage));
                
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'sender_id'   => Auth::id(),
                    'phone'       => $customer->phone,
                    'message'     => "[Email Welcome] " . $finalWelcomeMessage,
                    'status'      => 'sent',
                    'response'    => 'Welcome Email dispatched successfully via SMTP',
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Welcome Email failed for {$customer->email}: " . $e->getMessage());
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'sender_id'   => Auth::id(),
                    'phone'       => $customer->phone,
                    'message'     => "[Email Welcome FAILED] " . $finalWelcomeMessage,
                    'status'      => 'failed',
                    'response'    => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('customers.index')->with('success', '✅ Lead registered and welcome notifications sent via all channels!');
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
            'phone'        => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:255',
            'status'       => 'nullable|string',
            'buying_stage' => 'nullable|string',
        ]);

        $data = $request->except(['_token', '_method', 'phone_number', 'alternative_phone_number']);
        $data['estimated_monthly_value'] = $request->estimated_monthly_value ?? 0;
        
        // Safety Fallback for DB constraints
        if (empty($data['phone'])) {
            $data['phone'] = 'No Phone Provided';
        }

        $user = Auth::user();

        // --- SMART BRANCH FALLBACK (Fixes 1452) ---
        if (isset($data['branch_id']) && !\App\Models\Branch::where('id', $data['branch_id'])->exists()) {
            $fallbackBranch = \App\Models\Branch::first();
            $data['branch_id'] = $fallbackBranch ? $fallbackBranch->id : null;
        }
        // ------------------------------------------

        // Handle officer reassignment (super_admin & manager only)
        if (($user->role === 'super_admin' || $user->role === 'manager') && $request->filled('sales_officer_id')) {
            $newOfficerId = $request->sales_officer_id;
            if ($newOfficerId != $customer->sales_officer_id) {
                $newOfficer = \App\Models\User::find($newOfficerId);
                if ($newOfficer) {
                    $data['branch_id'] = $newOfficer->branch_id;
                    // Notify the newly assigned officer
                    $newOfficer->notify(new \App\Notifications\LeadAssignedSmsNotification($customer));
                }
            }
        }

        $oldStage = $customer->buying_stage;
        $oldStatus = $customer->status;
        $customer->update($data);

        \App\Models\AuditLog::record("Updated details for customer lead: {$customer->name}", 'Customers');

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

        \App\Models\AuditLog::record("Quick updated customer {$customer->name} (Stage: {$customer->buying_stage}, Status: {$customer->status})", 'Customers');

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

        \App\Models\AuditLog::record("Started repeat sales cycle for customer: {$customer->name} (Value: {$customer->estimated_monthly_value})", 'Customers');

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
            'customer_ids' => 'required_without:select_all_in_db|array',
            'message' => 'required|string|max:500',
            'channels' => 'required|array|min:1',
            'select_all_in_db' => 'nullable|integer'
        ]);

        $successCount = 0;
        $failCount = 0;
        $channels = $request->channels;
        
        /**
         * TIMEOUT PROTECTION & SCALABILITY:
         * If the list is large (e.g. 200+ leads), sending SMS/WhatsApp/Email synchronously 
         * would normally cause a browser timeout. 
         * 1. set_time_limit(0) allows this script to run as long as needed.
         * 2. ignore_user_abort(true) ensures the send continues even if the user closes the browser.
         * 3. For lists over 1000, we recommend using a Background Job (Queue) which is already
         *    configured in your .env (QUEUE_CONNECTION=database).
         */
        set_time_limit(0);
        ignore_user_abort(true);

        // Handle "Select All in Database" logic
        if ($request->select_all_in_db == 1) {
            $user = Auth::user();
            $branchId = $this->getActiveBranchId();
            $query = Customer::when($branchId, fn($q) => $q->where('branch_id', $branchId));
            if ($user->role === 'sales_officer') {
                $query->where('sales_officer_id', Auth::id());
            }
            $customerIds = $query->pluck('id')->toArray();
        } else {
            $customerIds = $request->customer_ids;
        }

        // SMART DISPATCH: 
        // If the list is large (>50), we move it to the background so you don't have to wait.
        // This prevents timeouts and allows the CRM to stay fast.
        if (count($customerIds) > 50) {
            \App\Jobs\SendBulkBroadcastJob::dispatch(
                $customerIds, 
                $request->message, 
                $channels, 
                Auth::id()
            );

            return redirect()->back()->with('success', '🚀 Mega-Broadcast Started! Since you selected ' . count($customerIds) . ' customers, the system is sending them in the background. You can check the logs in a few minutes.');
        }

        foreach ($customerIds as $id) {
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
                        'response'    => isset($result['response']) ? (is_array($result['response']) ? json_encode($result['response']) : $result['response']) : null,
                    ]);
                    if ($result['success']) $successCount++; else $failCount++;
                }

                // Send via WhatsApp if selected
                if (in_array('whatsapp', $channels)) {
                    $waTemplate = \App\Models\SystemSetting::where('key', 'whatsapp_template_general')->first()?->value ?? 'general_broadcast';
                    $cleanMsg = preg_replace('/\s+/', ' ', $message);
                    
                    $result = $this->whatsapp->sendTemplateMessage($customer->phone, $waTemplate, 'en', [
                        'customer_name' => $customer->name,
                        'message_content' => $cleanMsg
                    ]);
                    
                    SmsLog::create([
                        'customer_id' => $customer->id,
                        'sender_id'   => Auth::id(),
                        'phone'       => $customer->phone,
                        'message'     => "[WhatsApp] " . $message,
                        'status'      => $result['success'] ? 'sent' : 'failed',
                        'response'    => isset($result['response']) ? (is_array($result['response']) ? json_encode($result['response']) : $result['response']) : null,
                    ]);
                    if ($result['success']) $successCount++;
                }

                // Send via Email if selected
                if (in_array('email', $channels) && $customer->email) {
                    try {
                        \Illuminate\Support\Facades\Mail::to($customer->email)->send(new \App\Mail\CustomerReminderMail($customer, $message));
                        $successCount++;
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Bulk Email failed for {$customer->email}: " . $e->getMessage());
                        $failCount++;
                    }
                } elseif (in_array('email', $channels)) {
                    $failCount++;
                }
            }
        }

        $msg = "🚀 Broadcast complete! Processed: " . count($customerIds) . " recipients via: " . implode(', ', array_map('strtoupper', $channels));
        return redirect()->route('customers.sms_reminders')->with('success', $msg);
    }

    public function bulkDelegateView(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'super_admin' && $user->role !== 'manager') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $branchId = $this->getActiveBranchId();
        $currentOfficerId = $request->get('current_officer_id');
        $region = $request->get('region');

        $query = Customer::with('salesOfficer')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($currentOfficerId === 'unassigned', fn($q) => $q->whereNull('sales_officer_id'))
            ->when($currentOfficerId && $currentOfficerId !== 'unassigned', fn($q) => $q->where('sales_officer_id', $currentOfficerId))
            ->when($region, fn($q) => $q->where('region', $region));

        $customers = $query->orderBy('name')->get();

        $officers = \App\Models\User::whereIn('role', ['sales_officer', 'manager', 'super_admin'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $regions = Customer::whereNotNull('region')
            ->distinct()
            ->pluck('region')
            ->filter(function($r) {
                $rUpper = strtoupper($r);
                return !str_contains($rUpper, 'SEC') && 
                       !str_contains($rUpper, 'SCH') && 
                       !str_contains($rUpper, 'VTC') &&
                       !str_contains($rUpper, 'ACADEMY');
            })
            ->toArray();

        return view('customers.bulk_delegate', compact('customers', 'officers', 'regions', 'currentOfficerId', 'region'));
    }

    public function processBulkDelegate(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|array',
            'target_officer_id' => 'required|exists:users,id',
        ]);

        $targetOfficer = \App\Models\User::find($request->target_officer_id);
        $count = 0;

        foreach ($request->customer_ids as $id) {
            $customer = Customer::find($id);
            if ($customer) {
                $customer->update([
                    'sales_officer_id' => $targetOfficer->id,
                    'branch_id'        => $targetOfficer->branch_id
                ]);
                
                // Notify the new officer
                $targetOfficer->notify(new \App\Notifications\LeadAssignedSmsNotification($customer));
                
                $count++;
            }
        }

        $delegatedIds = $request->customer_ids;

        return redirect()->route('customers.bulk_delegate')
            ->with('success', "✅ Successfully delegated {$count} leads to {$targetOfficer->name}!")
            ->with('delegated_ids', $delegatedIds);
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
        
        // Use production domain for WhatsApp and general links
        $url = 'https://trumark.mauzolink.co.tz/feedback/' . $customer->survey_uuid;
        
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

        if ($useWhatsapp && $customer->phone) {
            $templateName = $settings['wa_template_survey_name'] ?? 'survey_invitation';
            $languageCode = $settings['wa_template_survey_lang'] ?? 'en';
            
            // Format the survey message for the general_broadcast template
            $surveyMsg = str_replace('{link}', " " . $url . " ", ($settings['survey_sms_template'] ?? 'Tafadhali tufahamishe jinsi ulivyohudumiwa hapa: {link}. Asante!'));
            $surveyMsg .= "\n\n(Note: Save our contact to make the link clickable! 🙏)";
            $cleanMsg = preg_replace('/\s+/', ' ', $surveyMsg);

            $waParams = ['customer_name' => $customer->name];
            $buttonParams = [];

            if ($templateName === 'survey_invitation') {
                // We no longer pass survey_link to the body! 
                // Only pass the UUID for the Dynamic URL button
                $buttonParams = [$customer->survey_uuid];
            } else {
                $waParams['message_content'] = $cleanMsg;
            }

            $result = $this->whatsapp->sendTemplateMessage($customer->phone, $templateName, $languageCode, $waParams, $buttonParams);

            SmsLog::create([
                'customer_id' => $customer->id,
                'sender_id'   => Auth::id(),
                'phone'       => $customer->phone,
                'message'     => "[WhatsApp Survey] " . $url,
                'status'      => $result['success'] ? 'sent' : 'failed',
                'response'    => isset($result['response']) ? (is_array($result['response']) ? json_encode($result['response']) : $result['response']) : null,
            ]);
            if ($result['success']) $channelsSent[] = 'WhatsApp';
        }

        // 3. Send Email
        if ($customer->email) {
            try {
                $emailSubject = "We value your feedback - TruMark Co. LTD";
                $emailBody = str_replace(['{name}', '{link}'], [$customer->name, $url], $settings['survey_sms_template'] ?? 'Hello {name}, please share your feedback here: {link}');
                
                \Illuminate\Support\Facades\Mail::to($customer->email)->send(new \App\Mail\CustomerReminderMail($customer, $emailBody));
                $channelsSent[] = 'Email';

                SmsLog::create([
                    'customer_id' => $customer->id,
                    'sender_id'   => Auth::id(),
                    'phone'       => $customer->phone,
                    'message'     => "[Email Survey] " . $url,
                    'status'      => 'sent',
                    'response'    => 'Email dispatched successfully via SMTP',
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Survey Email failed: " . $e->getMessage());
                SmsLog::create([
                    'customer_id' => $customer->id,
                    'sender_id'   => Auth::id(),
                    'phone'       => $customer->phone,
                    'message'     => "[Email Survey FAILED] " . $url,
                    'status'      => 'failed',
                    'response'    => $e->getMessage(),
                ]);
            }
        }
        // Update timestamp
        $customer->update(['last_survey_sent_at' => now()]);

        $sentStr = implode(', ', $channelsSent);
        return back()->with('success', "✅ Survey invitations sent via: {$sentStr}");
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

            $allRows = $data[0];
            $headerIndex = 0;
            foreach ($allRows as $index => $r) {
                $line = array_map('strtolower', array_map('trim', $r));
                if (in_array('phone', $line) || in_array('name', $line)) {
                    $headerIndex = $index;
                    break;
                }
            }
            $headingsRow = $allRows[$headerIndex];
            $headings = array_map('strtolower', array_map('trim', $headingsRow));
            $rows = array_slice($allRows, $headerIndex + 1);
            
            // Map column names to indices
            $map = [
                'type'           => array_search('type', $headings),
                'name'           => array_search('name', $headings),
                'contact_person' => array_search('contact person', $headings),
                'position'       => array_search('position', $headings),
                'phone'          => array_search('phone', $headings),
                'email'          => array_search('email', $headings),
                'region'         => array_search('region', $headings),
                'district'       => array_search('district', $headings),
                'source'         => array_search('source', $headings),
                'status'         => array_search('status', $headings),
                'requirements'   => array_search('requirements', $headings),
                'school_level'   => array_search('school level', $headings),
            ];

            \Illuminate\Support\Facades\DB::beginTransaction();
            
            $lastType = 'Potential Customer';
            $lastName = '';

            // Get a safe branch fallback in case the user's branch_id is invalid
            $fallbackBranchId = \App\Models\Branch::first()->id ?? null;
            $userBranchId = $user->branch_id;
            if ($userBranchId && !\App\Models\Branch::where('id', $userBranchId)->exists()) {
                $userBranchId = $fallbackBranchId;
            }

            foreach ($rows as $row) {
                // Skip if row is effectively empty or looks exactly like headings
                if (empty(array_filter($row)) || $row === $headingsRow) continue;

                $currentType = ($map['type'] !== false) ? trim($row[$map['type']] ?? '') : '';
                $currentName = ($map['name'] !== false) ? trim($row[$map['name']] ?? '') : '';
                
                // Smart Inheritance
                if (!empty($currentType)) $lastType = $currentType;
                if (!empty($currentName)) $lastName = $currentName;

                $orgName = !empty($currentName) ? $currentName : $lastName;
                $type    = !empty($currentType) ? $currentType : $lastType;
                $contact = ($map['contact_person'] !== false) ? trim($row[$map['contact_person']] ?? '') : '';
                $phone   = ($map['phone'] !== false) ? trim($row[$map['phone']] ?? '') : '';
                $sLevel  = ($map['school_level'] !== false) ? trim($row[$map['school_level']] ?? '') : '';

                // Auto-detect School type if school level is present
                if (empty($currentType) && ($lastType === 'Potential Customer' || empty($lastType)) && !empty($sLevel)) {
                    $type = 'School';
                }

                // Smart Naming: Combine Org + Person for inherited rows to avoid duplicates
                if (empty($currentName) && !empty($contact) && !empty($lastName)) {
                    $finalName = $lastName . " - " . $contact;
                } else {
                    $finalName = !empty($orgName) ? $orgName : (!empty($contact) ? $contact : 'New Lead (' . date('d-m-Y H:i') . ')');
                }

                if (empty($phone)) $phone = 'No Phone Provided';

                $source = ($map['source'] !== false) ? trim($row[$map['source']] ?? 'Bulk Import') : 'Bulk Import';
                $reqRaw = ($map['requirements'] !== false) ? trim($row[$map['requirements']] ?? '') : '';
                $requirements = !empty($reqRaw) ? array_map('trim', explode(',', $reqRaw)) : [];

                $customer = Customer::create([
                    'sales_officer_id' => $user->id,
                    'branch_id'        => $userBranchId,
                    'type'             => $type,
                    'name'             => $finalName,
                    'contact_person'   => $contact,
                    'position'         => ($map['position'] !== false) ? trim($row[$map['position']] ?? '') : '',
                    'phone'            => $phone,
                    'email'            => ($map['email'] !== false) ? trim($row[$map['email']] ?? '') : '',
                    'region'           => ($map['region'] !== false) ? trim($row[$map['region']] ?? '') : '',
                    'district'         => ($map['district'] !== false) ? trim($row[$map['district']] ?? '') : '',
                    'source'           => $source,
                    'status'           => ($map['status'] !== false) ? trim($row[$map['status']] ?? 'Potential Customer') : 'Potential Customer',
                    'requirements'     => $requirements,
                    'school_level'     => ($map['school_level'] !== false) ? trim($row[$map['school_level']] ?? '') : '',
                    'buying_stage'     => 'Inquiry',
                ]);

                if ($customer) {
                    $importedCount++;
                    \App\Services\KpiService::recordActivity('REG_NEW_POTENTIAL', $user->id, $customer->id);
                    if (strtolower($source) === 'referral') {
                        \App\Services\KpiService::recordActivity('REFERRAL_EXISTING', $user->id, $customer->id);
                    }
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
