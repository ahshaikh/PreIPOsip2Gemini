<?php
// V-PHASE3-1730-089 (Created) | V-FINAL-1730-451 | V-FINAL-1730-479 (Custom Amount) | V-FINAL-1730-579 (Refund Logic) | V-FIX-MULTI-SUB (Gemini)
// DOMAIN-LAYER-REFACTOR: Refactored to use UserAggregateService for compliance enforcement

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

// [DOMAIN LAYER]: Import domain-level services and exceptions
use App\Contracts\UserAggregateServiceInterface;
use App\Exceptions\Domain\IneligibleActionException;
use App\Exceptions\Domain\SubscriptionNotFoundException;

class SubscriptionController extends Controller
{
    protected $service;
    protected $eligibilityService;
    protected $userAggregateService; // [DOMAIN LAYER]: Domain-level service

    /**
     * Constructor with dependency injection
     *
     * [DOMAIN LAYER]: Added UserAggregateServiceInterface for domain-level operations
     */
    public function __construct(
        SubscriptionService $service,
        PlanEligibilityService $eligibilityService,
        UserAggregateServiceInterface $userAggregateService
    ) {
        $this->service = $service;
        $this->eligibilityService = $eligibilityService;
        $this->userAggregateService = $userAggregateService;
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

    /**
     * Change Subscription Plan (Upgrade/Downgrade)
     *
     * [DOMAIN LAYER REFACTOR]: Now uses UserAggregateService
     * - No direct status checks (no whereIn(['active','paused']))
     * - Compliance enforced via assertCan()
     * - Controller doesn't know what "pending" means
     */
    public function changePlan(Request $request)
    {
        $validated = $request->validate([
            'new_plan_id' => 'required|exists:plans,id',
        ]);

        $user = $request->user();
        $newPlanId = $validated['new_plan_id'];

        try {
            // [DOMAIN LAYER]: Use aggregate service for domain operation
            // This handles ALL eligibility checks, status validation, and business rules
            $updatedAggregate = $this->userAggregateService->changeSubscriptionPlan(
                $user->id,
                $newPlanId
            );

            // Determine if it was upgrade or downgrade from result
            $subscription = $updatedAggregate->subscription;
            $newPlan = Plan::find($newPlanId);

            // Return success response
            return response()->json([
                'message' => 'Plan changed successfully.',
                'subscription' => $subscription,
            ]);

        } catch (IneligibleActionException $e) {
            // [DOMAIN LAYER]: Convert domain exception to HTTP response
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext(),
            ], 403);

        } catch (SubscriptionNotFoundException $e) {
            // [DOMAIN LAYER]: Handle missing subscription
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ], 404);

        } catch (\Exception $e) {
            // Catch any other unexpected errors
            Log::error('Subscription plan change failed', [
                'user_id' => $user->id,
                'new_plan_id' => $newPlanId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to change plan. Please try again.',
            ], 400);
        }
    }

    /**
     * Pause Subscription
     *
     * [DOMAIN LAYER REFACTOR]: Now uses UserAggregateService
     * - No direct status checks
     * - Compliance enforced via assertCan()
     * - Controller doesn't validate subscription status
     *
     * Note: Plan-specific pause duration limits are validated in SubscriptionService
     */
    public function pause(Request $request)
    {
        // Basic validation (pause duration validation happens in service layer)
        $validated = $request->validate([
            'months' => 'required|integer|min:1|max:12', // Broad range, actual limit from plan
        ]);

        $user = $request->user();
        $pauseMonths = $validated['months'];

        try {
            // [DOMAIN LAYER]: Use aggregate service for domain operation
            $updatedAggregate = $this->userAggregateService->pauseSubscription(
                $user->id,
                $pauseMonths
            );

            return response()->json([
                'message' => "Subscription paused for {$pauseMonths} months.",
                'subscription' => $updatedAggregate->subscription,
            ]);

        } catch (IneligibleActionException $e) {
            // [DOMAIN LAYER]: Convert domain exception to HTTP response
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext(),
            ], 403);

        } catch (SubscriptionNotFoundException $e) {
            // [DOMAIN LAYER]: Handle missing subscription
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ], 404);

        } catch (\Exception $e) {
            // Catch validation or other errors from service layer
            Log::error('Subscription pause failed', [
                'user_id' => $user->id,
                'pause_months' => $pauseMonths,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
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

    /**
     * Cancel Subscription
     *
     * [DOMAIN LAYER REFACTOR]: Now uses UserAggregateService
     * - No direct status checks (no whereIn(['active','paused','pending']))
     * - Compliance enforced via assertCan()
     * - Controller doesn't know about subscription states
     */
    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $reason = $validated['reason'];

        try {
            // [DOMAIN LAYER]: Use aggregate service for domain operation
            $updatedAggregate = $this->userAggregateService->cancelSubscription(
                $user->id,
                $reason
            );

            return response()->json([
                'message' => 'Subscription cancelled successfully.',
                'subscription' => $updatedAggregate->subscription,
            ]);

        } catch (IneligibleActionException $e) {
            // [DOMAIN LAYER]: Convert domain exception to HTTP response
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
                'context' => $e->getContext(),
            ], 403);

        } catch (SubscriptionNotFoundException $e) {
            // [DOMAIN LAYER]: Handle missing subscription
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => $e->getErrorCode(),
            ], 404);

        } catch (\Exception $e) {
            // Catch any other unexpected errors
            Log::error('Subscription cancellation failed', [
                'user_id' => $user->id,
                'reason' => $reason,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to cancel subscription. Please try again.',
            ], 400);
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