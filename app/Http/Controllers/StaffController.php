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

    public function __construct(SmsService $sms)
    {
        $this->sms = $sms;
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
            'phone' => 'required|string|max:20',
            'role' => 'required|string|in:manager,sales_officer',
        ]);

        $nameParts = explode(' ', trim($request->name));
        $lastName = end($nameParts);
        $plainPassword = strtoupper($lastName);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($plainPassword),
            'role' => $request->role,
            'branch_id' => $request->branch_id,
        ]);

        $message = "Karibu TRUMARK, {$user->name}. Login yako ni Email: {$user->email} na Password: {$plainPassword}";
        $this->sms->sendSms($user->phone, $message);

        return redirect()->route('staff.index')->with('success', "Staff member registered! Credentials sent to {$user->phone}");
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
            'phone' => 'required|string|max:20',
            'role' => 'required|string|in:manager,sales_officer',
        ]);

        $oldBranchId = $user->branch_id;
        $newBranchId = $request->branch_id;

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
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

        // Send via SMS
        $message = "TRUMARK CRM: Password yako mpya ni {$newPassword}. Tafadhali login na uibadilishe.";
        $result = $this->sms->sendSms($user->phone, $message);

        // Log the SMS
        \App\Models\SmsLog::create([
            'customer_id' => null, // null for staff
            'sender_id'   => Auth::id(),
            'phone'       => $user->phone,
            'message'     => $message,
            'status'      => $result['success'] ? 'sent' : 'failed',
            'response'    => $result['response'] ?? ($result['error'] ?? 'Unknown Error'),
        ]);

        if ($result['success']) {
            return back()->with('success', "New password generated and sent to {$user->phone}");
        } else {
            return back()->with('error', "Password reset but SMS failed to reach {$user->phone}. Please check your SMS settings.");
        }
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
