<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Subscription;

/**
 * [P1 FIX]: Middleware to check if user's CURRENT PLAN allows access to a product.
 *
 * PURPOSE: Product access control (NOT plan subscription eligibility).
 *
 * IMPORTANT DISTINCTION:
 * - PlanEligibilityService: "Can user SUBSCRIBE to this plan?" (eligibility to join)
 * - This middleware: "Can user with THEIR plan ACCESS this product?" (access control)
 *
 * These are DIFFERENT concerns and both are needed.
 *
 * EXECUTION PATH:
 * 1. User requests investment/deal access
 * 2. Check user's active subscription plan
 * 3. Check if product is accessible by that plan
 * 4. Allow or block with upgrade prompt
 *
 * USAGE: Apply to routes with 'plan.eligible' middleware
 * (Currently registered but not applied to any routes)
 *
 * WHY IT CANNOT FAIL:
 * - Database relationships enforce plan-product linkage
 * - Validation happens before investment logic
 * - Clear error messages guide user
 */
class CheckPlanEligibility
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // If no user authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Get product_id from request (could be in different places)
        $productId = $request->product_id
                  ?? $request->route('product')
                  ?? $request->input('product_id');

        // If no product specified, skip check
        if (!$productId) {
            return $next($request);
        }

        $product = Product::find($productId);

        // If product doesn't exist, let controller handle 404
        if (!$product) {
            return $next($request);
        }

        // If product is available to all plans, allow access
        if ($product->eligibility_mode === 'all_plans') {
            return $next($request);
        }

        // Get user's active subscription
        $subscription = Subscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'paused'])
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->first();

        // If no active subscription, block access
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'error' => 'subscription_required',
                'message' => 'You need an active subscription to access this product.',
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                ],
                'action' => [
                    'type' => 'subscribe',
                    'url' => '/plans',
                    'message' => 'Choose a subscription plan to get started',
                ],
            ], 403);
        }

        $plan = $subscription->plan;

        // Check if plan can access this product
        if (!$plan->canAccessProduct($product)) {
            // Find which plans CAN access this product (for upgrade prompt)
            $eligiblePlans = $product->plans()
                ->where('is_active', true)
                ->get(['plans.id', 'plans.name', 'plans.monthly_amount'])
                ->toArray();

            return response()->json([
                'success' => false,
                'error' => 'plan_upgrade_required',
                'message' => "Your current plan ('{$plan->name}') does not include access to '{$product->name}'.",
                'current_plan' => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                ],
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                ],
                'eligible_plans' => $eligiblePlans,
                'action' => [
                    'type' => 'upgrade',
                    'message' => 'Upgrade your plan to access this product',
                    'plans' => $eligiblePlans,
                ],
            ], 403);
        }

        // Access granted - attach plan info to request for controller use
        $request->merge([
            '_user_plan' => $plan,
            '_plan_discount' => $plan->getProductDiscount($product),
        ]);

        return $next($request);
    }
}
