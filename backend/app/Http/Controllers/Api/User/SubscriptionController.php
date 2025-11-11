// V-PHASE3-1730-089
<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    /**
     * Show the user's active subscription.
     */
    public function show(Request $request)
    {
        $subscription = Subscription::where('user_id', $request->user()->id)
            ->with('plan.features', 'payments')
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found.'], 404);
        }
        return response()->json($subscription);
    }

    /**
     * Create a new subscription.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Validate request
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);
        
        // 2. Check prerequisites
        if ($user->kyc->status !== 'verified') {
            return response()->json(['message' => 'KYC must be verified to start a subscription.'], 403);
        }
        if (Subscription::where('user_id', $user->id)->where('status', 'active')->exists()) {
            return response()->json(['message' => 'You already have an active subscription.'], 400);
        }
        
        $plan = Plan::findOrFail($validated['plan_id']);

        // 3. Create Subscription
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'subscription_code' => 'SUB-' . Str::random(10),
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths($plan->duration_months),
            'next_payment_date' => now(), // Payment is due immediately
        ]);

        // 4. Create the first payment record
        $payment = $subscription->payments()->create([
            'user_id' => $user->id,
            'amount' => $plan->monthly_amount,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Subscription created. Please complete the first payment.',
            'subscription' => $subscription,
            'payment' => $payment,
        ], 201);
    }
}