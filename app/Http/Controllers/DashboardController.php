<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        // Allow both Super Admins and Managers to filter branches
        $isGlobal = in_array($user->role, ['super_admin', 'manager']);
        $isOfficer = $user->role === 'sales_officer';
        
        // Admins can switch branches — persist selection in session
        $branchId = $isGlobal ? null : $user->branch_id;
        if ($isGlobal) {
            if ($request->has('branch_id')) {
                // Explicit change via dropdown (empty string = Global / All)
                $branchId = $request->branch_id ?: null;
                session(['dashboard_branch_id' => $branchId]);
            } else {
                // Restore last selection from session
                $branchId = session('dashboard_branch_id');
            }
        }

        $branches = $isGlobal ? \App\Models\Branch::where('is_active', true)->get() : [];
        $currentBranch = $branchId ? \App\Models\Branch::find($branchId) : null;

        $total_customers = \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->count();
        
        $pending_follow_ups = \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->whereNotNull('next_follow_up_date')
            ->whereNotIn('buying_stage', ['Closed Won', 'Closed Lost'])
            ->count();

        $pipeline_value = \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->whereNotIn('buying_stage', ['Closed Won', 'Closed Lost'])
            ->sum('estimated_monthly_value');

        $won_value = \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->whereIn('buying_stage', ['Closed Won', 'Delivered'])
            ->sum('estimated_monthly_value');

        // Funnel Stages
        $funnel = [
            'Inquiry' => \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Inquiry')->count(),
            'Quotation Sent' => \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Quotation Sent')->count(),
            'Negotiation' => \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Negotiation')->count(),
            'Order Confirmed' => \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Order Confirmed')->count(),
            'Delivered' => \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Delivered')->count(),
            'Payment Pending' => \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Payment Pending')->count(),
            'Closed Won' => \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Closed Won')->count(),
            'Closed Lost' => \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Closed Lost')->count(),
        ];

        // Calculate total for percentages
        $total_funnel = array_sum($funnel);
        $funnel_percentages = [];
        foreach ($funnel as $key => $count) {
            $funnel_percentages[$key] = $total_funnel > 0 ? round(($count / $total_funnel) * 100) : 0;
        }

        // Recent SMS Activity (Isolated by Officer and Branch)
        $recent_logs = \App\Models\SmsLog::with('customer', 'sender')
            ->when($branchId, function($q) use ($branchId) {
                return $q->whereHas('customer', function($cq) use ($branchId) {
                    $cq->where('branch_id', $branchId);
                });
            })
            ->when($isOfficer, function($q) use ($user) {
                return $q->whereHas('customer', function($cq) use ($user) {
                    $cq->where('sales_officer_id', $user->id);
                });
            })
            ->latest()
            ->paginate(4, ['*'], 'comms_page');

        // Monthly Leads Trend (Last 6 Months)
        $leads_by_month = [];
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            $leads_by_month[] = \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                    return $q->where('branch_id', $branchId);
                })->when($isOfficer, function($q) use ($user) {
                    return $q->where('sales_officer_id', $user->id);
                })->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
        }

        // Customer Sources Distribution
        $sources = \App\Models\Customer::when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })->when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->select('source', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();

        return view('dashboard.index', compact(
            'total_customers', 
            'pending_follow_ups', 
            'pipeline_value', 
            'won_value', 
            'funnel', 
            'funnel_percentages',
            'recent_logs',
            'leads_by_month',
            'months',
            'sources',
            'branches',
            'currentBranch',
            'branchId'
        ));
    }
}
