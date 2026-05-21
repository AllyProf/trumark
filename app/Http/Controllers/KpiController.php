<?php

namespace App\Http\Controllers;

use App\Models\KpiActivity;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Services\KpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller
{
    /**
     * Show the leaderboard of all staff performance
     */
    public function leaderboard(Request $request)
    {
        $user     = Auth::user();
        $branchId = ($user->role === 'super_admin')
            ? ($request->filled('branch_id') ? $request->branch_id : $this->getActiveBranchId())
            : $user->branch_id;
        $month = $request->get('month', now()->month);
        $year  = $request->get('year', now()->year);
        $userId = $request->user_id;
        
        $branches = \App\Models\Branch::where('is_active', true)->get();
        $allStaff = User::when($user->role === 'manager', fn($q) => $q->where('branch_id', $user->branch_id))
            ->get();

        $staff = User::with('branch')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($userId, fn($q) => $q->where('id', $userId))
            ->when($user->role === 'sales_officer', fn($q) => $q->where('id', $user->id))
            ->get();

        foreach ($staff as $s) {
            // Filtered points (by selected month/year)
            $s->monthly_points = KpiActivity::where('user_id', $s->id)
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->sum('points');
            
            // Total points
            $s->total_points = KpiActivity::where('user_id', $s->id)->sum('points');
            $s->kpi_level = KpiService::getLevel($s->total_points);
        }

        // Sort by filtered points desc (ensure it's treated as a number)
        $staff = $staff->sortByDesc(function($s) {
            return (int) $s->monthly_points;
        })->values();

        // Calculate Last Month's Champion
        $lastMonth = now()->subMonth();
        $lastMonthWinner = User::get()
            ->map(function($u) use ($lastMonth) {
                $u->last_month_points = KpiActivity::where('user_id', $u->id)
                    ->whereMonth('created_at', $lastMonth->month)
                    ->whereYear('created_at', $lastMonth->year)
                    ->sum('points');
                return $u;
            })
            ->sortByDesc('last_month_points')
            ->first();
            
        if ($lastMonthWinner && $lastMonthWinner->last_month_points > 0) {
            $lastMonthWinner->kpi_level = KpiService::getLevel(KpiActivity::where('user_id', $lastMonthWinner->id)->sum('points'));
        } else {
            $lastMonthWinner = null;
        }

        return view('kpi.leaderboard', compact('staff', 'branches', 'allStaff', 'lastMonthWinner'));
    }

    /**
     * Show the detailed activity ledger (point history)
     */
    public function activities(Request $request)
    {
        $user     = Auth::user();
        $branchId = $this->getActiveBranchId();

        $query = KpiActivity::with(['user', 'customer', 'performer'])->orderByDesc('created_at');

        if ($user->role === 'sales_officer') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'manager') {
            $query->whereHas('user', fn($q) => $q->where('branch_id', $user->branch_id));
        } elseif ($user->role === 'super_admin' && $branchId) {
            // Scope to the session-selected branch
            $query->whereHas('user', fn($q) => $q->where('branch_id', $branchId));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $activities = $query->paginate(30);

        // Staff dropdown scoped to active branch
        $staff = ($user->role === 'super_admin' || $user->role === 'manager')
            ? User::when($branchId && $user->role === 'super_admin', fn($q) => $q->where('branch_id', $branchId))
                ->when($user->role === 'manager', fn($q) => $q->where('branch_id', $user->branch_id))
                ->get()
            : [];

        return view('kpi.activities', compact('activities', 'staff'));
    }

    /**
     * Show attendance and system usage logs
     */
    public function attendance(Request $request)
    {
        $user     = Auth::user();
        $branchId = $this->getActiveBranchId();

        $query = UserLoginLog::with('user')->orderByDesc('login_at');

        if ($user->role === 'sales_officer') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'manager') {
            $query->whereHas('user', fn($q) => $q->where('branch_id', $user->branch_id));
        } elseif ($user->role === 'super_admin' && $branchId) {
            $query->whereHas('user', fn($q) => $q->where('branch_id', $branchId));
        }

        $logs = $query->paginate(30);

        return view('kpi.attendance', compact('logs'));
    }

    /**
     * Show a deep-dive profile of a specific officer's performance
     */
    public function officerProfile($id)
    {
        $user = Auth::user();
        
        // Security: Officers can only see their own profile
        if ($user->role === 'sales_officer' && $user->id != $id) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $officer = User::with(['branch', 'leads'])->findOrFail($id);
        
        // 1. Core Stats
        $totalPoints = KpiActivity::where('user_id', $officer->id)->sum('points');
        $monthlyPoints = KpiActivity::where('user_id', $officer->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('points');
            
        $level = KpiService::getLevel($totalPoints);
        
        // 2. Branch Average (Comparison - Avg Points Per Officer)
        $branchStaffIds = User::where('branch_id', $officer->branch_id)->pluck('id');
        $branchTotalPoints = KpiActivity::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('user_id', $branchStaffIds)
            ->sum('points');
        
        $branchStaffCount = $branchStaffIds->count();
        $branchAvg = $branchStaffCount > 0 ? ($branchTotalPoints / $branchStaffCount) : 0;
            
        // 3. Sales Funnel Performance
        $funnelData = [
            'Inquiry' => \App\Models\Customer::where('sales_officer_id', $officer->id)->where('buying_stage', 'Inquiry')->count(),
            'Quotation Sent' => \App\Models\Customer::where('sales_officer_id', $officer->id)->where('buying_stage', 'Quotation Sent')->count(),
            'Negotiation' => \App\Models\Customer::where('sales_officer_id', $officer->id)->where('buying_stage', 'Negotiation')->count(),
            'Order Confirmed' => \App\Models\Customer::where('sales_officer_id', $officer->id)->where('buying_stage', 'Order Confirmed')->count(),
            'Delivered' => \App\Models\Customer::where('sales_officer_id', $officer->id)->where('buying_stage', 'Delivered')->count(),
            'Payment Pending' => \App\Models\Customer::where('sales_officer_id', $officer->id)->where('buying_stage', 'Payment Pending')->count(),
            'Closed Won' => \App\Models\Customer::where('sales_officer_id', $officer->id)->where('buying_stage', 'Closed Won')->count(),
        ];
        
        $chartData = [
            'labels' => array_keys($funnelData),
            'data' => array_values($funnelData)
        ];
        
        // 4. Performance Notes (Privacy Filter)
        $notes = \App\Models\KpiNote::with('manager')
            ->where('user_id', $officer->id)
            ->when($user->role === 'sales_officer', function($q) {
                return $q->where('is_visible_to_staff', true);
            })
            ->orderByDesc('created_at')
            ->get();
        
        // 5. Recent data for lists
        $activities = KpiActivity::with(['customer', 'performer'])
            ->where('user_id', $officer->id)
            ->orderByDesc('created_at')
            ->paginate(5);
            
        $attendance = UserLoginLog::where('user_id', $officer->id)
            ->orderByDesc('login_at')
            ->limit(10)
            ->get();
            
        $assignedPortfolio = \App\Models\Customer::where('sales_officer_id', $officer->id)
            ->orderByDesc('updated_at')
            ->paginate(10, ['*'], 'portfolio_page');

        return view('kpi.officer_profile', compact(
            'officer', 'totalPoints', 'monthlyPoints', 'level', 
            'activities', 'attendance', 'chartData', 'branchAvg', 'notes', 'assignedPortfolio'
        ));
    }

    /**
     * Store a new performance note for an officer
     */
    public function storeNote(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'note' => 'required|string|max:1000',
            'type' => 'required|in:observation,achievement,warning',
        ]);

        $note = \App\Models\KpiNote::create([
            'user_id' => $request->user_id,
            'manager_id' => Auth::id(),
            'note' => $request->note,
            'type' => $request->type,
            'is_visible_to_staff' => $request->has('is_visible'),
        ]);

        // Notify the officer if the note is visible to them
        if ($note->is_visible_to_staff) {
            $officer = User::find($request->user_id);
            if ($officer) {
                $officer->notify(new \App\Notifications\PerformanceNoteNotification($note));
            }
        }

        return back()->with('success', 'Performance note recorded successfully.');
    }

    /**
     * Delete a performance note
     */
    public function deleteNote($id)
    {
        $note = \App\Models\KpiNote::findOrFail($id);
        
        // Only the manager who wrote it or a super admin can delete
        if (Auth::id() == $note->manager_id || Auth::user()->role == 'super_admin') {
            $note->delete();
            return back()->with('success', 'Performance note deleted.');
        }

        return back()->with('error', 'Unauthorized action.');
    }
    /**
     * Show performance comparison between different branches
     */
    public function branchComparison()
    {
        $user = Auth::user();
        
        // Only managers and super admins can see this
        if ($user->role === 'sales_officer') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $branches = \App\Models\Branch::where('is_active', true)->get();
        $branchData = [];

        foreach ($branches as $branch) {
            $officerIds = User::where('branch_id', $branch->id)->pluck('id');
            
            $totalPoints = KpiActivity::whereIn('user_id', $officerIds)->sum('points');
            $officerCount = $officerIds->count();
            $avgPoints = $officerCount > 0 ? round($totalPoints / $officerCount, 1) : 0;
            $activityCount = KpiActivity::whereIn('user_id', $officerIds)->count();
            
            // Top performer in this branch
            $topPerformer = User::where('branch_id', $branch->id)
                ->get()
                ->sortByDesc(function($u) {
                    return KpiActivity::where('user_id', $u->id)->sum('points');
                })
                ->first();

            $topPoints = $topPerformer ? KpiActivity::where('user_id', $topPerformer->id)->sum('points') : 0;

            // Historical trend (Last 3 months) for the branch
            $trend = [];
            for ($i = 2; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $trend[] = (int) KpiActivity::whereIn('user_id', $officerIds)
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->sum('points');
            }

            $branchData[] = [
                'branch' => $branch,
                'total_points' => $totalPoints,
                'avg_points' => $avgPoints,
                'officer_count' => $officerCount,
                'activity_count' => $activityCount,
                'top_performer' => $topPerformer,
                'top_points' => $topPoints,
                'trend' => $trend
            ];
        }

        // Sort branchData by total points descending
        usort($branchData, function($a, $b) {
            return $b['total_points'] <=> $a['total_points'];
        });

        $months = [
            now()->subMonths(2)->format('M'),
            now()->subMonths(1)->format('M'),
            now()->format('M')
        ];

        return view('kpi.branch_comparison', compact('branchData', 'months'));
    }

    public function guide()
    {
        $points = \App\Services\KpiService::getPointsMap();
        return view('kpi.guide', compact('points'));
    }
}
