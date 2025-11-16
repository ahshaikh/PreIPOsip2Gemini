<?php
// V-FINAL-1730-451 (Created) | V-FINAL-1730-479 (Custom Amount) | V-FINAL-1730-579 (Refund Logic)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
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
            return response()->json(null, 404);
        }
        return response()->json($subscription);
    }

    public function store(Request $request)
    {
        if (!setting('investment_enabled', true)) {
            return response()->json(['message' => 'New investments are temporarily disabled.'], 403);
        }
        
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'custom_amount' => 'nullable|numeric|min:1'
        ]);
        
        $user = $request->user();
        $plan = Plan::findOrFail($validated['plan_id']);
        $customAmount = $validated['custom_amount'] ?? null;

        try {
            $subscription = $this->service->createSubscription($user, $plan, $customAmount);
            return response()->json([
                'message' => 'Subscription created. Please complete the first payment.',
                'subscription' => $subscription->load('payments'),
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
            if ($newPlan->monthly_amount > $sub->amount) {
                $prorated = $this->service->upgradePlan($sub, $newPlan);
                $message = "Plan upgraded. A pro-rata charge of ₹{$prorated} has been created.";
                if ($prorated == 0) $message = "Plan upgraded. Changes effective next cycle.";
                return response()->json(['message' => $message]);
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
            $refundAmount = $this->service->cancelSubscription($sub, $validated['reason']);
            
            $message = 'Subscription cancelled.';
            if ($refundAmount > 0) {
                $message .= " A pro-rata refund of ₹{$refundAmount} has been credited to your wallet.";
            }
            
            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}