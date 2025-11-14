<?php
// V-FINAL-1730-262 (Created) | V-FINAL-1730-451 (Service Refactor)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService; // <-- IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    protected $service;
    public function __construct(SubscriptionService $service)
    {
        $this->service = $service;
    }

    public function show(Request $request)
    {
        $subscription = Subscription::where('user_id', $request->user()->id)
            ->with('plan.features', 'payments')
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'No active subscription found.'], 404);
        }
        return response()->json($subscription);
    }

    public function store(Request $request)
    {
        if (!setting('investment_enabled', true)) {
            return response()->json(['message' => 'New investments are temporarily disabled.'], 403);
        }
        
        $validated = $request->validate(['plan_id' => 'required|exists:plans,id']);
        $user = $request->user();
        $plan = Plan::findOrFail($validated['plan_id']);

        try {
            $subscription = $this->service->createSubscription($user, $plan);
            return response()->json([
                'message' => 'Subscription created. Please complete the first payment.',
                'subscription' => $subscription,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function changePlan(Request $request)
    {
        $validated = $request->validate(['new_plan_id' => 'required|exists:plans,id']);
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->where('status', 'active')->firstOrFail();
        $newPlan = Plan::findOrFail($validated['new_plan_id']);

        try {
            if ($newPlan->monthly_amount > $sub->plan->monthly_amount) {
                $prorated = $this->service->upgradePlan($sub, $newPlan);
                return response()->json(['message' => "Plan upgraded. Pro-rata charge: ₹{$prorated}"]);
            } else {
                $this->service->downgradePlan($sub, $newPlan);
                return response()->json(['message' => 'Plan downgraded. Changes effective next cycle.']);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function pause(Request $request)
    {
        $validated = $request->validate(['months' => 'required|integer|min:1|max:3']);
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->where('status', 'active')->firstOrFail();

        try {
            $this->service->pauseSubscription($sub, $validated['months']);
            return response()->json(['message' => "Subscription paused for {$validated['months']} months."]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function resume(Request $request)
    {
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->where('status', 'paused')->firstOrFail();

        try {
            $this->service->resumeSubscription($sub);
            return response()->json(['message' => 'Subscription resumed.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate(['reason' => 'required|string|max:255']);
        $user = $request->user();
        $sub = Subscription::where('user_id', $user->id)->whereIn('status', ['active', 'paused'])->firstOrFail();

        try {
            $this->service->cancelSubscription($sub, $validated['reason']);
            return response()->json(['message' => 'Subscription cancelled.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}