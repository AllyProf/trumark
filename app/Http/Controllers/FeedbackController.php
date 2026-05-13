<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerFeedback;
use App\Services\KpiService;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    /**
     * Show the public survey page.
     */
    public function show($uuid)
    {
        $customer = Customer::where('survey_uuid', $uuid)->firstOrFail();
        
        return view('feedback.survey', compact('customer'));
    }

    /**
     * Store the feedback.
     */
    public function store(Request $request, $uuid)
    {
        $customer = Customer::where('survey_uuid', $uuid)->firstOrFail();
        
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $feedback = CustomerFeedback::create([
            'customer_id' => $customer->id,
            'user_id' => $customer->sales_officer_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'ip_address' => $request->ip(),
        ]);

        // Award KPI Points based on rating
        if ($customer->sales_officer_id) {
            if ($request->rating >= 4) {
                // Positive Feedback
                KpiService::recordActivity(
                    'POSITIVE_FEEDBACK', 
                    $customer->sales_officer_id, 
                    $customer->id, 
                    "Excellent Customer Rating: {$request->rating} Stars. Comment: {$request->comment}"
                );
            } elseif ($request->rating <= 2) {
                // Customer Complaint
                KpiService::recordActivity(
                    'CUSTOMER_COMPLAINT', 
                    $customer->sales_officer_id, 
                    $customer->id, 
                    "Poor Customer Rating: {$request->rating} Stars. Comment: {$request->comment}"
                );
            }
        }

        return view('feedback.thank_you');
    }
}
