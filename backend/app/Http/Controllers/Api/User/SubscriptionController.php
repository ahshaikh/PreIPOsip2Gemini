<?php
// V-FINAL-1730-262 (Lifecycle Management Added)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Show the user's active subscription.
     */
    public function show(Request $request)
    {
        $subscription = Subscription::where('user_id', $request->user()->id)
            ->with('plan.features', 'payments')
            ->latest() // Get the most recent one
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
        if (!setting('investment_enabled', true)) {
            return response()->json(['message' => 'New investments are temporarily disabled.'], 403);
        }

        $user = $request->user();
        $validated = $request->validate(['plan_id' => 'required|exists:plans,id']);
        
        if ($user->kyc->status !== 'verified') {
            return response()->json(['message' => 'KYC must be verified to start a subscription.'], 403);
        }
        
        // Check if user already has an ACTIVE or PAUSED subscription
        if (Subscription::where('user_id', $user->id)->whereIn('status', ['active', 'paused'])->exists()) {
            return response()->json(['message' => 'You already have an active subscription. Please upgrade/downgrade instead.'], 400);
        }
        
        $plan = Plan::findOrFail($validated['plan_id']);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'subscription_code' => 'SUB-' . Str::random(10),
            'status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths($plan->duration_months),
            'next_payment_date' => now(),
        ]);

        // Create the first payment record
        $subscription->payments()->create([
            'user_id' => $user->id,
            'amount' => $plan->monthly_amount,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Subscription created. Please complete the first payment.',
            'subscription' => $subscription,
        ], 201);
    }

    /**
     * FSD-PLAN-004: Change Plan (Upgrade/Downgrade)
     */
    public function changePlan(Request $request)
    {
        $validated = $request->validate(['new_plan_id' => 'required|exists:plans,id']);
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->where('status', 'active')->firstOrFail();
        
        $newPlan = Plan::findOrFail($validated['new_plan_id']);
        
        if ($sub->plan_id === $newPlan->id) {
            return response()->json(['message' => 'You are already subscribed to this plan.'], 400);
        }

        // Logic: Change is effective immediately for FUTURE payments.
        // We do not recalculate past bonuses to keep it simple for V1.
        
        $sub->update([
            'plan_id' => $newPlan->id,
            // We might want to reset end_date or keep it same. 
            // FSD implies simplified flow: just change the billing amount.
        ]);

        // Update any PENDING payment to the new amount
        $pendingPayment = $sub->payments()->where('status', 'pending')->first();
        if ($pendingPayment) {
            $pendingPayment->update(['amount' => $newPlan->monthly_amount]);
        }

        return response()->json(['message' => "Plan changed to {$newPlan->name}. Next payment will be ₹{$newPlan->monthly_amount}."]);
    }

    /**
     * FSD-PLAN-005: Pause Subscription
     */
    public function pause(Request $request)
    {
        $validated = $request->validate(['months' => 'required|integer|min:1|max:3']);
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->where('status', 'active')->firstOrFail();

        $months = $validated['months'];
        
        // Shift dates
        $newNextPayment = $sub->next_payment_date->addMonths($months);
        $newEndDate = $sub->end_date->addMonths($months);

        $sub->update([
            'status' => 'paused',
            'pause_start_date' => now(),
            'pause_end_date' => now()->addMonths($months),
            'next_payment_date' => $newNextPayment,
            'end_date' => $newEndDate
        ]);

        return response()->json(['message' => "Subscription paused for $months months. Next payment due: " . $newNextPayment->toDateString()]);
    }

    /**
     * Resume Subscription (Manual or Auto)
     */
    public function resume(Request $request)
    {
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->where('status', 'paused')->firstOrFail();

        $sub->update([
            'status' => 'active',
            'pause_start_date' => null,
            'pause_end_date' => null,
            // We don't pull back the dates, we just resume from current state
        ]);

        return response()->json(['message' => 'Subscription resumed successfully.']);
    }

    /**
     * FSD-PLAN-006: Cancel Subscription
     */
    public function cancel(Request $request)
    {
        $validated = $request->validate(['reason' => 'required|string|max:255']);
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->whereIn('status', ['active', 'paused'])->firstOrFail();

        $sub->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['reason']
        ]);

        // Optional: Cancel any pending payments
        $sub->payments()->where('status', 'pending')->update(['status' => 'failed']);

        return response()->json(['message' => 'Subscription cancelled. Your portfolio remains active.']);
    }
}