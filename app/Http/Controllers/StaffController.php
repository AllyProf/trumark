<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StaffController extends Controller
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
        if (Auth::user()->role !== 'super_admin' && Auth::user()->role !== 'manager') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $user = Auth::user();
        $staff = User::with('branch')
            ->where('id', '!=', Auth::id())
            ->when($user->role === 'manager', fn($q) => $q->where('branch_id', $user->branch_id))
            ->get();

        foreach ($staff as $s) {
            $s->kpi_points = \App\Models\KpiActivity::where('user_id', $s->id)->sum('points');
            $s->kpi_level  = \App\Services\KpiService::getLevel($s->kpi_points);
        }

        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $branches = \App\Models\Branch::where('is_active', true)->get();
        return view('staff.create', compact('branches'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|numeric|digits:9',
            'role' => 'required|string|in:super_admin,manager,sales_officer',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $nameParts = explode(' ', trim($request->name));
        $lastName = end($nameParts);
        $plainPassword = strtoupper($lastName);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => '+255' . $request->phone,
            'password' => Hash::make($plainPassword),
            'role' => $request->role,
            'branch_id' => $request->branch_id,
        ]);

        // 1. Send SMS
        $roleName = ucwords(str_replace('_', ' ', $user->role));
        $branchName = $user->branch ? $user->branch->name : 'Global';
        $smsMessage = "Welcome to TruMark, {$user->name}\nYour staff account has been successfully created.\n\nBranch : {$branchName}\nRole : {$roleName}\nUsername : {$user->email}\nPassword : {$plainPassword}\n\nPlease log in and change your password after your first access.\nIf you need help, contact your Manager.\n\n- TruMark Team";
        try {
            $smsResult = $this->sms->sendSms($user->phone, $smsMessage);
            \Illuminate\Support\Facades\Log::info("Staff SMS to {$user->phone}: " . json_encode($smsResult));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Staff SMS failed: " . $e->getMessage());
        }

        // 2. Send WhatsApp
        $waMessage = "Welcome to TruMark, {$user->name}\n\nYour staff account has been successfully created.\n\nYou can now access your dashboard using the details below:\n\nBranch : {$branchName}\nRole : {$roleName}\nUsername : {$user->email}\nPassword : {$plainPassword}\n\nPlease log in and change your password after your first access for security purposes.\n\nIf you need help, contact your Manager.\n\n- TruMark Team";
        $this->whatsapp->sendMessage($user->phone, $waMessage);

        // 3. Send Professional Email
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\StaffWelcomeMail($user, $plainPassword));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send welcome email to staff: " . $e->getMessage());
        }

        return redirect()->route('staff.index')->with('success', "Staff member registered! Credentials sent via Email, WhatsApp and SMS.");
    }

    public function edit($id)
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $staff = User::findOrFail($id);
        $branches = \App\Models\Branch::where('is_active', true)->get();
        return view('staff.edit', compact('staff', 'branches'));
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $user = User::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'phone' => 'required|numeric|digits:9',
            'role' => 'required|string|in:super_admin,manager,sales_officer',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $oldBranchId = $user->branch_id;
        $newBranchId = $request->branch_id;

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => '+255' . $request->phone,
            'role' => $request->role,
            'branch_id' => $newBranchId,
        ]);

        // If the branch changed, transfer all assigned customers to the new branch too
        if ($oldBranchId != $newBranchId && $newBranchId) {
            \App\Models\Customer::where('sales_officer_id', $user->id)
                ->update(['branch_id' => $newBranchId]);
        }

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully!');
    }

    public function destroy($id)
    {
        if (Auth::user()->role !== 'super_admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            return redirect()->route('staff.index')->with('error', 'You cannot delete yourself!');
        }

        $user->delete();
        return redirect()->route('staff.index')->with('success', 'Staff member deleted successfully!');
    }

    public function resetPassword(User $user)
    {
        if (Auth::user()->role !== 'super_admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Generate a random 6-character password
        $newPassword = strtoupper(Str::random(6));
        $user->update(['password' => Hash::make($newPassword)]);

        // 1. Send via SMS
        $message = "TruMark CRM: Your password has been reset. New Password: {$newPassword}. Please login and change it.";
        $smsResult = $this->sms->sendSms($user->phone, $message);

        // Log the SMS
        \App\Models\SmsLog::create([
            'customer_id' => null,
            'sender_id'   => Auth::id(),
            'phone'       => $user->phone,
            'message'     => $message,
            'status'      => $smsResult['success'] ? 'sent' : 'failed',
            'response'    => $smsResult['response'] ?? ($smsResult['error'] ?? 'Unknown Error'),
        ]);

        // 2. Send via WhatsApp
        $waMessage = "TRUMARK CRM: Password Reset 🔐\n\nYour new password is: *{$newPassword}*\n\nPlease login and change it immediately for security.\n" . url('/');
        $this->whatsapp->sendMessage($user->phone, $waMessage);

        // 3. Send via Professional Email
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\StaffPasswordResetMail($user, $newPassword));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send password reset email: " . $e->getMessage());
        }

        return back()->with('success', "New password generated and sent via Email, WhatsApp and SMS.");
    }

    public function toggleStatus(User $user)
    {
        if (Auth::user()->role !== 'super_admin') {
            return back()->with('error', 'Unauthorized access.');
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate yourself!');
        }

        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Staff account {$status} successfully!");
    }

    public function adjustKpi(Request $request, User $user)
    {
        $userRole = Auth::user()->role;
        if ($userRole !== 'super_admin' && $userRole !== 'manager') {
            return back()->with('error', 'Unauthorized access.');
        }

        $request->validate([
            'activity_code' => 'required|string',
            'notes'         => 'nullable|string'
        ]);

        $activity = \App\Services\KpiService::recordActivity(
            $request->activity_code, 
            $user->id, 
            null, 
            $request->notes
        );

        if ($activity) {
            $user->notify(new \App\Notifications\KpiAdjustmentNotification($activity));
        }

        return back()->with('success', "KPI Points adjusted for {$user->name}!");
    }
}
