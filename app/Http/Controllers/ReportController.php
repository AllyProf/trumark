<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $isGlobal = $user->role === 'super_admin';

        // Read session-stored branch (set from dashboard filter) for super_admin
        // Allow URL override so the reports page own branch selector still works
        $branchId = $this->getActiveBranchId();
        if ($isGlobal && $request->filled('branch_id')) {
            $branchId = $request->branch_id;
            session(['dashboard_branch_id' => $branchId]); // keep in sync
        }

        $branches      = $isGlobal ? \App\Models\Branch::where('is_active', true)->get() : [];
        $currentBranch = $branchId ? \App\Models\Branch::find($branchId) : null;
        
        $baseQuery = Customer::query()
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            });

        // Sales Officer Isolation: restricted to their own leads
        if ($user->role === 'sales_officer') {
            $baseQuery->where('sales_officer_id', $user->id);
        }

        // ── KPI Summary ───────────────────────────────────────────────
        $totalLeads      = (clone $baseQuery)->count();
        $totalPipelineValue = (clone $baseQuery)->whereNotIn('buying_stage', ['Closed Won', 'Closed Lost'])->sum('estimated_monthly_value');
        $totalWonValue   = (clone $baseQuery)->where('buying_stage', 'Closed Won')->sum('estimated_monthly_value');
        $totalLost       = (clone $baseQuery)->where('buying_stage', 'Closed Lost')->count();
        $pendingPayment  = (clone $baseQuery)->where('buying_stage', 'Payment Pending')->count();
        $pendingPaymentValue = (clone $baseQuery)->where('buying_stage', 'Payment Pending')->sum('estimated_monthly_value');

        // ── Funnel Stage Distribution ─────────────────────────────────
        $stageData = (clone $baseQuery)->select('buying_stage', DB::raw('count(*) as total'))
            ->groupBy('buying_stage')
            ->pluck('total', 'buying_stage')
            ->toArray();

        // ── Customer Status Distribution ──────────────────────────────
        $statusData = (clone $baseQuery)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // ── Customer Type Distribution ────────────────────────────────
        $typeData = (clone $baseQuery)->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        // ── Leads by Source ───────────────────────────────────────────
        $sourceData = (clone $baseQuery)->select('source', DB::raw('count(*) as total'))
            ->whereNotNull('source')
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();

        // ── Monthly Registrations (last 6 months) ────────────────────
        $monthlyData = (clone $baseQuery)->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Fill in any missing months with 0
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $key = now()->subMonths($i)->format('Y-m');
            $months[$key] = $monthlyData[$key] ?? 0;
        }

        // ── Pipeline Value by Stage ───────────────────────────────────
        $pipelineByStage = (clone $baseQuery)->select('buying_stage', DB::raw('SUM(estimated_monthly_value) as total_value'))
            ->groupBy('buying_stage')
            ->pluck('total_value', 'buying_stage')
            ->toArray();

        // ── Top Sales Officers ────────────────────────────────────────
        // Admins/Managers see all officers in their branch, Sales Officers see only themselves
        $topOfficersQuery = Customer::with('salesOfficer')
            ->select('sales_officer_id', DB::raw('count(*) as lead_count'), DB::raw('SUM(estimated_monthly_value) as pipeline'))
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            });

        if ($user->role === 'sales_officer') {
            $topOfficersQuery->where('sales_officer_id', $user->id);
        }

        $topOfficers = $topOfficersQuery->groupBy('sales_officer_id')
            ->orderByDesc('lead_count')
            ->get();

        // ── KPI Points & Performance Levels ──────────────────────────
        foreach ($topOfficers as $officer) {
            $officer->kpi_points = \App\Models\KpiActivity::where('user_id', $officer->sales_officer_id)->sum('points');
            $officer->kpi_level  = \App\Services\KpiService::getLevel($officer->kpi_points);
        }

        // ── Attendance & System Usage ────────────────────────────────
        $usageLogs = \App\Models\UserLoginLog::with('user')
            ->when($branchId, function($q) use ($branchId) {
                return $q->whereHas('user', fn($uq) => $uq->where('branch_id', $branchId));
            })
            ->orderByDesc('login_at')
            ->paginate(10)
            ->appends(request()->query());
        $wonCount  = (clone $baseQuery)->where('buying_stage', 'Closed Won')->count();
        $conversionRate = $totalLeads > 0 ? round(($wonCount / $totalLeads) * 100, 1) : 0;

        // Specific Demographic Counts
        $stats = [
            'primary_schools'   => (clone $baseQuery)->where('school_level', 'LIKE', '%Primary%')->count(),
            'secondary_schools' => (clone $baseQuery)->where('school_level', 'LIKE', '%Secondary%')->count(),
            'parents'           => (clone $baseQuery)->where('type', 'Parent')->count(),
            'walk_ins'          => (clone $baseQuery)->where('type', 'Walk in')->count(),
            'new_this_month'    => (clone $baseQuery)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        return view('reports.index', compact(
            'totalLeads', 'totalPipelineValue', 'totalWonValue',
            'totalLost', 'pendingPayment', 'pendingPaymentValue',
            'stageData', 'statusData', 'typeData', 'sourceData',
            'months', 'pipelineByStage', 'topOfficers', 'conversionRate',
            'wonCount', 'branches', 'currentBranch', 'usageLogs', 'stats'
        ));
    }

    public function statistics(Request $request)
    {
        $user     = auth()->user();
        $isGlobal = $user->role === 'super_admin';

        // Read session-stored branch (set from dashboard / reports filter)
        // Allow the page's own branch dropdown to override and keep session in sync
        $branchId = $this->getActiveBranchId();
        if ($isGlobal && $request->filled('branch_id')) {
            $branchId = $request->branch_id ?: null;
            session(['dashboard_branch_id' => $branchId]); // keep in sync with dashboard
        }

        $timeFilter = $request->get('time_filter', 'all');
        $startDate  = $request->get('start_date');
        $endDate    = $request->get('end_date');

        $branches      = $isGlobal ? \App\Models\Branch::where('is_active', true)->get() : [];
        $currentBranch = $branchId ? \App\Models\Branch::find($branchId) : null;

        $baseQuery = Customer::query()
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            });

        // Apply Time Filter or Date Range
        if ($startDate && $endDate) {
            $baseQuery->whereBetween('customers.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            $timeFilter = 'custom';
        } elseif ($timeFilter === 'week') {
            $baseQuery->whereBetween('customers.created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($timeFilter === 'month') {
            $baseQuery->whereMonth('customers.created_at', now()->month)->whereYear('customers.created_at', now()->year);
        } elseif ($timeFilter === 'year') {
            $baseQuery->whereYear('customers.created_at', now()->year);
        }

        if ($user->role === 'sales_officer') {
            $baseQuery->where('sales_officer_id', $user->id);
        }

        $stats = [
            'total'             => (clone $baseQuery)->count(),
            'primary_schools'   => (clone $baseQuery)->where('school_level', 'LIKE', '%Primary%')->count(),
            'secondary_schools' => (clone $baseQuery)->where('school_level', 'LIKE', '%Secondary%')->count(),
            'parents'           => (clone $baseQuery)->where('type', 'Parent')->count(),
            'walk_ins'          => (clone $baseQuery)->where('type', 'Walk in')->count(),
            'companies'         => (clone $baseQuery)->where('type', 'Company')->count(),
            'new_this_month'    => (clone $baseQuery)->whereMonth('customers.created_at', now()->month)->whereYear('customers.created_at', now()->year)->count(),
            'new_today'         => (clone $baseQuery)->whereDate('customers.created_at', now()->toDateString())->count(),
        ];

        // Type Breakdown
        $typeData = (clone $baseQuery)->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        // Status Breakdown
        $statusData = (clone $baseQuery)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Stage Breakdown
        $stageData = (clone $baseQuery)->select('buying_stage', DB::raw('count(*) as total'))
            ->groupBy('buying_stage')
            ->pluck('total', 'buying_stage')
            ->toArray();

        // Region Breakdown
        $regionData = (clone $baseQuery)->select('region', DB::raw('count(*) as total'))
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'region')
            ->toArray();

        // Branch Breakdown
        $branchData = (clone $baseQuery)->join('branches', 'customers.branch_id', '=', 'branches.id')
            ->select('branches.name', DB::raw('count(*) as total'))
            ->groupBy('branches.name')
            ->pluck('total', 'branches.name')
            ->toArray();

        // Source Breakdown
        $sourceData = (clone $baseQuery)->select('source', DB::raw('count(*) as total'))
            ->whereNotNull('source')
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();

        // Detailed List for Breakdown
        $customers = (clone $baseQuery)->with(['salesOfficer', 'branch'])
            ->orderBy('name')
            ->get();

        return view('reports.statistics', compact('stats', 'typeData', 'regionData', 'branchData', 'statusData', 'stageData', 'sourceData', 'branches', 'currentBranch', 'customers', 'timeFilter', 'startDate', 'endDate', 'branchId'));
    }

    public function surveys(Request $request)
    {
        $user     = auth()->user();
        $isGlobal = $user->role === 'super_admin';
        
        $branchId = $request->get('branch_id', $this->getActiveBranchId());
        $staffId  = $request->get('staff_id');

        $branches = $isGlobal ? \App\Models\Branch::where('is_active', true)->get() : [];
        
        // Fetch officers for the filter dropdown
        $officers = \App\Models\User::where('role', 'sales_officer')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        $query = \App\Models\CustomerFeedback::with(['customer', 'officer', 'customer.branch'])
            ->when($branchId, function($q) use ($branchId) {
                return $q->whereHas('customer', fn($cq) => $cq->where('branch_id', $branchId));
            })
            ->when($staffId, function($q) use ($staffId) {
                return $q->where('user_id', $staffId);
            });

        // Sales officers can only see feedback about themselves
        if ($user->role === 'sales_officer') {
            $query->where('user_id', $user->id);
            $staffId = $user->id;
        }

        $feedbacks = $query->latest()->paginate(15)->appends($request->query());
        
        $avgRating = (clone $query)->avg('rating');
        $totalFeedbacks = (clone $query)->count();

        // Rating breakdown
        $ratingBreakdown = (clone $query)->select('rating', DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating', 'desc')
            ->pluck('count', 'rating')
            ->toArray();

        return view('reports.surveys', compact('feedbacks', 'avgRating', 'totalFeedbacks', 'ratingBreakdown', 'branches', 'branchId', 'officers', 'staffId'));
    }
}
