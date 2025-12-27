<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Manage Plan-Product eligibility relationships.
 *
 * BUSINESS LOGIC:
 * - Assign products to plans (defines what subscribers can access)
 * - Set plan-specific pricing/discounts
 * - Control product availability per plan tier
 */
class PlanProductController extends Controller
{
    /**
     * Get all products assigned to a plan.
     * GET /api/v1/admin/plans/{plan}/products
     */
    public function index($planId)
    {
        $plan = Plan::with(['products' => function ($query) {
            $query->withCount('investments')
                  ->orderByPivot('priority', 'desc');
        }])->findOrFail($planId);

        return response()->json([
            'success' => true,
            'plan' => $plan,
            'products' => $plan->products,
        ]);
    }

    /**
     * Assign a product to a plan.
     * POST /api/v1/admin/plans/{plan}/products
     */
    public function store(Request $request, $planId)
    {
        $plan = Plan::findOrFail($planId);

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'min_investment_override' => 'nullable|numeric|min:0',
            'max_investment_override' => 'nullable|numeric|min:0',
            'is_featured' => 'boolean',
            'priority' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::findOrFail($request->product_id);

        // Check if already assigned
        if ($plan->products()->where('product_id', $product->id)->exists()) {
            return response()->json([
                'error' => 'Product already assigned to this plan. Use PUT to update.'
            ], 422);
        }

        // Validate investment overrides
        if ($request->max_investment_override && $request->min_investment_override) {
            if ($request->max_investment_override < $request->min_investment_override) {
                return response()->json([
                    'error' => 'Max investment override cannot be less than min investment override'
                ], 422);
            }
        }

        $plan->products()->attach($product->id, [
            'discount_percentage' => $request->discount_percentage ?? 0,
            'min_investment_override' => $request->min_investment_override,
            'max_investment_override' => $request->max_investment_override,
            'is_featured' => $request->is_featured ?? false,
            'priority' => $request->priority ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Product '{$product->name}' assigned to plan '{$plan->name}'",
            'product' => $plan->products()->find($product->id),
        ], 201);
    }

    /**
     * Update plan-product relationship settings.
     * PUT /api/v1/admin/plans/{plan}/products/{product}
     */
    public function update(Request $request, $planId, $productId)
    {
        $plan = Plan::findOrFail($planId);
        $product = Product::findOrFail($productId);

        if (!$plan->products()->where('product_id', $product->id)->exists()) {
            return response()->json([
                'error' => 'Product not assigned to this plan'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'min_investment_override' => 'nullable|numeric|min:0',
            'max_investment_override' => 'nullable|numeric|min:0',
            'is_featured' => 'boolean',
            'priority' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan->products()->updateExistingPivot($product->id, array_filter([
            'discount_percentage' => $request->discount_percentage,
            'min_investment_override' => $request->min_investment_override,
            'max_investment_override' => $request->max_investment_override,
            'is_featured' => $request->is_featured,
            'priority' => $request->priority,
        ], fn($val) => $val !== null));

        return response()->json([
            'success' => true,
            'message' => 'Plan-product settings updated',
            'product' => $plan->products()->find($product->id),
        ]);
    }

    /**
     * Remove product from plan.
     * DELETE /api/v1/admin/plans/{plan}/products/{product}
     */
    public function destroy($planId, $productId)
    {
        $plan = Plan::findOrFail($planId);
        $product = Product::findOrFail($productId);

        if (!$plan->products()->where('product_id', $product->id)->exists()) {
            return response()->json([
                'error' => 'Product not assigned to this plan'
            ], 404);
        }

        $plan->products()->detach($product->id);

        return response()->json([
            'success' => true,
            'message' => "Product removed from plan '{$plan->name}'",
        ]);
    }

    /**
     * Bulk assign multiple products to a plan.
     * POST /api/v1/admin/plans/{plan}/products/bulk
     */
    public function bulkAssign(Request $request, $planId)
    {
        $plan = Plan::findOrFail($planId);

        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $syncData = [];
        foreach ($request->product_ids as $productId) {
            $syncData[$productId] = [
                'discount_percentage' => $request->discount_percentage ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $plan->products()->syncWithoutDetaching($syncData);

        return response()->json([
            'success' => true,
            'message' => count($request->product_ids) . ' products assigned to plan',
            'assigned_count' => count($request->product_ids),
        ]);
    }

    /**
     * Get statistics for plan-product relationships.
     * GET /api/v1/admin/plans/{plan}/products/statistics
     */
    public function statistics($planId)
    {
        $plan = Plan::withCount('products')->findOrFail($planId);

        $stats = [
            'total_products' => $plan->products_count,
            'featured_products' => $plan->products()->wherePivot('is_featured', true)->count(),
            'avg_discount' => $plan->products()->avg('plan_products.discount_percentage'),
            'products_with_overrides' => $plan->products()
                ->whereNotNull('plan_products.min_investment_override')
                ->orWhereNotNull('plan_products.max_investment_override')
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'plan' => $plan,
            'stats' => $stats,
        ]);
    }
}
