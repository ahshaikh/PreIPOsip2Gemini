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

            // Return active, paused, or pending subscriptions for plan management
            // Cancelled/completed subscriptions should not prevent new subscriptions
            $subscription = Subscription::where('user_id', $user->id)
                ->whereIn('status', ['active', 'paused', 'pending'])
                ->with('plan.features', 'payments')
                ->latest()
                ->first();

            // Return null if no active/paused/pending subscription exists
            // This allows frontend to correctly show "Create Subscription" or "Complete Payment"
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

        // FIX 8 (P1): Check subscription limit enforcement
        if ($plan->max_subscriptions_per_user) {
            $existingCount = Subscription::where('user_id', $user->id)
                ->where('plan_id', $plan->id)
                ->whereIn('status', ['active', 'paused'])
                ->count();

            if ($existingCount >= $plan->max_subscriptions_per_user) {
                return response()->json([
                    'message' => "Maximum {$plan->max_subscriptions_per_user} active subscriptions allowed for this plan.",
                    'errors' => [
                        'plan_id' => ["You have reached the maximum limit of {$plan->max_subscriptions_per_user} active subscriptions for plan '{$plan->name}'."]
                    ]
                ], 422);
            }
        }

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
            $subscription->load('payments');

            // Check if payment was made from wallet
            $latestPayment = $subscription->payments()->latest()->first();
            $paidFromWallet = $latestPayment && $latestPayment->status === 'paid' && $latestPayment->payment_method === 'wallet';

            $message = $paidFromWallet
                ? 'Subscription activated! Payment deducted from wallet.'
                : 'Subscription created. Please complete the first payment.';

            return response()->json([
                'message' => $message,
                'subscription' => $subscription,
                'paid_from_wallet' => $paidFromWallet,
                'redirect_to' => $paidFromWallet ? 'companies' : 'payment',
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

        // Find specific subscription or default to first modifiable subscription
        // Valid statuses: active, paused, pending, cancelled, completed
        // Only active and paused subscriptions can be modified (pending requires payment first)
        $query = Subscription::where('user_id', $user->id)->whereIn('status', ['active', 'paused', 'pending']);
        if (isset($validated['subscription_id'])) {
            $query->where('id', $validated['subscription_id']);
        }
        $sub = $query->latest()->first();

        if (!$sub) {
            return response()->json(['message' => 'No active or paused subscription found to modify.'], 404);
        }

        // V-FIX-PENDING-SUBSCRIPTION: Block plan changes for pending subscriptions
        if ($sub->status === 'pending') {
            return response()->json([
                'message' => 'Please complete your first payment before changing plans.',
                'subscription_status' => 'pending',
                'action_required' => 'complete_payment'
            ], 400);
        }

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

        // Find user's active or pending subscription (subscription_id is optional)
        $query = Subscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'pending'])
            ->with('plan'); // Eager load plan for max_pause_duration_months

        if ($request->has('subscription_id')) {
            $query->where('id', $request->input('subscription_id'));
        }

        $sub = $query->latest()->first();

        if (!$sub) {
            return response()->json(['message' => 'No active subscription found to pause.'], 404);
        }

        // V-FIX-PENDING-SUBSCRIPTION: Block pause for pending subscriptions
        if ($sub->status === 'pending') {
            return response()->json([
                'message' => 'Cannot pause subscription. Please complete your first payment.',
                'subscription_status' => 'pending',
                'action_required' => 'complete_payment'
            ], 400);
        }

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

        $sub = $query->latest()->first();

        if (!$sub) {
            return response()->json(['message' => 'No paused subscription found to resume.'], 404);
        }

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

        // V-FIX-PENDING-SUBSCRIPTION: Allow cancellation of pending subscriptions (user can back out before payment)
        // Find user's active, paused, or pending subscription
        $query = Subscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'paused', 'pending']);

        if (isset($validated['subscription_id'])) {
            $query->where('id', $validated['subscription_id']);
        }

        $sub = $query->latest()->first();

        if (!$sub) {
            return response()->json(['message' => 'No subscription found to cancel.'], 404);
        }

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

    /**
     * Get Paginated Payment History for User's Subscriptions
     * Endpoint: /api/v1/user/subscription/payments
     * [PROTOCOL 7 IMPLEMENTATION]
     */
    public function payments(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string',
            'page' => 'nullable|integer',
        ]);

        $userId = $request->user()->id;

        // Get payments for user's subscriptions
        $query = DB::table('payments')
            ->join('subscriptions', 'payments.subscription_id', '=', 'subscriptions.id')
            ->where('subscriptions.user_id', $userId)
            ->select('payments.*');

        // Apply status filter
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('payments.status', $request->status);
        }

        // Dynamic Pagination
        $perPage = function_exists('setting') ? (int) setting('records_per_page', 15) : 15;

        $payments = $query->latest('payments.created_at')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($payments);
    }
}