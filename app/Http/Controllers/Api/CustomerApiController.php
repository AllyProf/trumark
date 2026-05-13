<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerApiController extends Controller
{
    /**
     * Get a list of customers.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Customer::with('salesOfficer:id,name');

        if ($user->role === 'sales_officer') {
            $query->where('sales_officer_id', $user->id);
        }

        // Filtering by stage if provided
        if ($request->has('stage')) {
            $query->where('buying_stage', $request->stage);
        }

        // Search by name or phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhere('phone', 'LIKE', "%$search%");
            });
        }

        $customers = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * Get detailed information for a single customer.
     */
    public function show(Customer $customer)
    {
        $user = Auth::user();
        
        // Authorization check
        if ($user->role === 'sales_officer' && $customer->sales_officer_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $customer->load(['salesOfficer:id,name', 'smsLogs', 'followUps']);

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Register a new customer/lead.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'phone'        => 'required|string|max:20',
            'status'       => 'required|string',
            'buying_stage' => 'required|string',
            'type'         => 'nullable|string',
            'source'       => 'nullable|string',
            'email'        => 'nullable|email',
            'region'       => 'nullable|string',
            'district'     => 'nullable|string',
            'estimated_monthly_value' => 'nullable|numeric',
        ]);

        $customer = Customer::create(array_merge($validated, [
            'sales_officer_id' => Auth::id(),
            'is_draft'         => false,
        ]));

        // Trigger notification
        Auth::user()->notify(new \App\Notifications\LeadAssignedNotification($customer));

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully.',
            'data' => $customer
        ], 201);
    }

    /**
     * Update an existing customer.
     */
    public function update(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if ($user->role === 'sales_officer' && $customer->sales_officer_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status'            => 'sometimes|string',
            'buying_stage'      => 'sometimes|string',
            'next_follow_up_date' => 'nullable|date',
            'notes'             => 'nullable|string',
            'estimated_monthly_value' => 'nullable|numeric',
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'data' => $customer
        ]);
    }
}
