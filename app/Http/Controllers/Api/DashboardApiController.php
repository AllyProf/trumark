<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SmsLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardApiController extends Controller
{
    /**
     * Get dashboard summary statistics.
     */
    public function index()
    {
        $user = Auth::user();
        $isOfficer = $user->role === 'sales_officer';

        $total_customers = Customer::when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->count();
        
        $pending_follow_ups = Customer::when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->whereNotNull('next_follow_up_date')
            ->whereNotIn('buying_stage', ['Closed Won', 'Closed Lost'])
            ->count();

        $pipeline_value = Customer::when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->whereNotIn('buying_stage', ['Closed Won', 'Closed Lost'])
            ->sum('estimated_monthly_value');

        $won_value = Customer::when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->whereIn('buying_stage', ['Closed Won', 'Delivered'])
            ->sum('estimated_monthly_value');

        // Funnel Stages
        $funnel = [
            'Inquiry' => Customer::when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Inquiry')->count(),
            'Quotation Sent' => Customer::when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Quotation Sent')->count(),
            'Negotiation' => Customer::when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Negotiation')->count(),
            'Order Confirmed' => Customer::when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->where('buying_stage', 'Order Confirmed')->count(),
            'Others' => Customer::when($isOfficer, function($q) use ($user) {
                return $q->where('sales_officer_id', $user->id);
            })->whereNotIn('buying_stage', ['Inquiry', 'Quotation Sent', 'Negotiation', 'Order Confirmed'])->count(),
        ];

        // Calculate total for percentages
        $total_funnel = array_sum($funnel);
        $funnel_percentages = [];
        foreach ($funnel as $key => $count) {
            $funnel_percentages[$key] = $total_funnel > 0 ? round(($count / $total_funnel) * 100) : 0;
        }

        // Recent SMS Activity
        $recent_logs = SmsLog::with('customer:id,name')->latest()->take(5)->get()->map(function($log) {
            return [
                'id' => $log->id,
                'customer_name' => $log->customer->name ?? 'Unknown',
                'message' => $log->message,
                'status' => $log->status,
                'time_ago' => $log->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_customers' => $total_customers,
                    'pending_follow_ups' => $pending_follow_ups,
                    'pipeline_value' => (float)$pipeline_value,
                    'won_value' => (float)$won_value,
                ],
                'funnel' => $funnel,
                'funnel_percentages' => $funnel_percentages,
                'recent_activity' => $recent_logs,
            ]
        ]);
    }
}
