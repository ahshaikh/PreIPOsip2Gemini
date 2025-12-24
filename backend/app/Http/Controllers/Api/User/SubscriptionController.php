<?php
// V-PHASE3-1730-089 (Created) | V-FINAL-1730-451 | V-FINAL-1730-479 (Custom Amount) | V-FINAL-1730-579 (Refund Logic) | V-FIX-MULTI-SUB (Gemini)

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use App\Services\PlanEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    protected $service;
    protected $eligibilityService;

    public function __construct(SubscriptionService $service, PlanEligibilityService $eligibilityService)
    {
        $this->service = $service;
        $this->eligibilityService = $eligibilityService;
    }

    /**
     * Get User's Latest Subscription
     * Endpoint: /api/v1/user/subscription
     *
     * V-FIX-DASHBOARD-RESILIENCE: Added error handling to prevent 500 errors
     * Returns null instead of crashing when tables don't exist or queries fail
     */
    public function show(Request $request)
    {
        try {
            $user = $request->user();

            // Check if required tables exist before querying
            if (!DB::getSchemaBuilder()->hasTable('subscriptions') ||
                !DB::getSchemaBuilder()->hasTable('plans')) {
                Log::warning('Subscription tables missing', [
                    'user_id' => $user->id,
                    'tables_checked' => ['subscriptions', 'plans']
                ]);
                return response()->json(null);
            }

            $subscription = Subscription::where('user_id', $user->id)
                ->with('plan.features', 'payments')
                ->latest()
                ->first();

            // Return null with success status if no subscription exists
            // This allows frontend to handle gracefully instead of showing loading forever
            return response()->json($subscription);

        } catch (\Throwable $e) {
            // Return null instead of 500 error
            // This allows dashboard to load even if subscription data unavailable
            Log::error("Subscription Error: " . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json(null);
        }
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

        // Check eligibility requirements
        $eligibilityCheck = $this->eligibilityService->checkEligibility($user, $plan);
        if (!$eligibilityCheck['eligible']) {
            return response()->json([
                'message' => 'You do not meet the eligibility requirements for this plan.',
                'errors' => $eligibilityCheck['errors']
            ], 403);
        }

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
        // [MODIFIED] Added subscription_id to validation to handle multiple subscriptions
        $validated = $request->validate([
            'new_plan_id' => 'required|exists:plans,id',
            'subscription_id' => 'sometimes|exists:subscriptions,id'
        ]);

        $user = $request->user();
        $newPlan = Plan::findOrFail($validated['new_plan_id']);

        // Find specific subscription or default to first non-cancelled subscription
        $query = Subscription::where('user_id', $user->id)->whereIn('status', ['active', 'paused', 'pending']);
        if (isset($validated['subscription_id'])) {
            $query->where('id', $validated['subscription_id']);
        }
        $sub = $query->latest()->firstOrFail();

        // Check if same plan
        if ($newPlan->id === $sub->plan_id) {
            return response()->json(['message' => 'You are already on this plan.'], 400);
        }

        try {
            if ($newPlan->monthly_amount > $sub->amount) {
                // UPGRADE
                $prorated = $this->service->upgradePlan($sub, $newPlan);
                $message = "Plan upgraded successfully. A pro-rata charge of ₹{$prorated} has been created.";
                if ($prorated == 0) $message = "Plan upgraded successfully. Changes effective next cycle.";
                return response()->json(['message' => $message]);
            } elseif ($newPlan->monthly_amount < $sub->amount) {
                // DOWNGRADE
                $this->service->downgradePlan($sub, $newPlan);
                return response()->json(['message' => 'Plan downgraded successfully. Changes effective next cycle.']);
            } else {
                // Same amount but different plan
                return response()->json(['message' => 'Cannot change to a plan with the same amount.'], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * V-AUDIT-MODULE7-005 (MEDIUM): Pause subscription with Plan-based validation.
     *
     * Configuration Fix:
     * - Previous: Hardcoded pause limit of max:3 months for all plans
     * - Problem: Different plans may have different pause policies
     * - Solution: Use Plan's max_pause_duration_months configuration
     */
    public function pause(Request $request)
    {
        $user = $request->user();

        // Find user's active subscription (subscription_id is optional)
        $query = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan'); // Eager load plan for max_pause_duration_months

        if ($request->has('subscription_id')) {
            $query->where('id', $request->input('subscription_id'));
        }

        $sub = $query->latest()->firstOrFail();

        // V-AUDIT-MODULE7-005: Use Plan's max_pause_duration_months instead of hardcoded max:3
        $maxPauseDuration = $sub->plan->max_pause_duration_months ?? 3; // Default to 3 if not set

        $validated = $request->validate([
            'months' => "required|integer|min:1|max:{$maxPauseDuration}",
            'subscription_id' => 'sometimes|exists:subscriptions,id' // Optional
        ]);

        try {
            $this->service->pauseSubscription($sub, $validated['months']);
            return response()->json(['message' => "Subscription paused for {$validated['months']} months."]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function resume(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => 'sometimes|exists:subscriptions,id' // Optional
        ]);

        $user = $request->user();

        // Find user's paused subscription
        $query = Subscription::where('user_id', $user->id)
            ->where('status', 'paused');

        if (isset($validated['subscription_id'])) {
            $query->where('id', $validated['subscription_id']);
        }

        $sub = $query->latest()->firstOrFail();

        try {
            $this->service->resumeSubscription($sub);
            return response()->json(['message' => 'Subscription resumed.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'subscription_id' => 'sometimes|exists:subscriptions,id' // Optional
        ]);

        $user = $request->user();

        // Find user's active or paused subscription
        $query = Subscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'paused']);

        if (isset($validated['subscription_id'])) {
            $query->where('id', $validated['subscription_id']);
        }

        $sub = $query->latest()->firstOrFail();

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