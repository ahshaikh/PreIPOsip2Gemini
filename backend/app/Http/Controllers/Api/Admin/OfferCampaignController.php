<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Deal;
use App\Models\Plan;
use App\Models\OfferStatistic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

/**
 * Manage Offer Campaigns - Link offers to products, deals, and plans.
 *
 * BUSINESS LOGIC:
 * - Run targeted promotions for specific products
 * - Create deal-specific campaigns
 * - Offer plan-tier exclusive promotions
 * - Track campaign performance
 */
class OfferCampaignController extends Controller
{
    // =====================================================
    // OFFER-PRODUCT CAMPAIGNS
    // =====================================================

    /**
     * Get all products assigned to an offer.
     * GET /api/v1/admin/offers/{offer}/products
     */
    public function getProducts($offerId)
    {
        $offer = Offer::with(['products' => function ($query) {
            $query->withCount('investments')
                  ->orderByPivot('priority', 'desc');
        }])->findOrFail($offerId);

        return response()->json([
            'success' => true,
            'offer' => $offer,
            'products' => $offer->products,
        ]);
    }

    /**
     * Assign products to an offer.
     * POST /api/v1/admin/offers/{offer}/products
     */
    public function assignProducts(Request $request, $offerId)
    {
        $offer = Offer::findOrFail($offerId);

        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'custom_discount_percent' => 'nullable|numeric|min:0|max:100',
            'custom_discount_amount' => 'nullable|numeric|min:0',
            'is_featured' => 'boolean',
            'priority' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $syncData = [];
        foreach ($request->product_ids as $productId) {
            $syncData[$productId] = [
                'custom_discount_percent' => $request->custom_discount_percent,
                'custom_discount_amount' => $request->custom_discount_amount,
                'is_featured' => $request->is_featured ?? false,
                'priority' => $request->priority ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $offer->products()->syncWithoutDetaching($syncData);

        // Update offer scope if not already set
        if ($offer->scope === 'global') {
            $offer->update(['scope' => 'products']);
        }

        return response()->json([
            'success' => true,
            'message' => count($request->product_ids) . ' products assigned to offer',
            'assigned_count' => count($request->product_ids),
        ]);
    }

    /**
     * Remove product from offer.
     * DELETE /api/v1/admin/offers/{offer}/products/{product}
     */
    public function removeProduct($offerId, $productId)
    {
        $offer = Offer::findOrFail($offerId);
        $product = Product::findOrFail($productId);

        if (!$offer->products()->where('product_id', $product->id)->exists()) {
            return response()->json([
                'error' => 'Product not assigned to this offer'
            ], 404);
        }

        $offer->products()->detach($product->id);

        return response()->json([
            'success' => true,
            'message' => 'Product removed from offer campaign',
        ]);
    }

    // =====================================================
    // OFFER-DEAL CAMPAIGNS
    // =====================================================

    /**
     * Get all deals assigned to an offer.
     * GET /api/v1/admin/offers/{offer}/deals
     */
    public function getDeals($offerId)
    {
        $offer = Offer::with(['deals' => function ($query) {
            $query->with('company')
                  ->withCount('investments')
                  ->orderByPivot('priority', 'desc');
        }])->findOrFail($offerId);

        return response()->json([
            'success' => true,
            'offer' => $offer,
            'deals' => $offer->deals,
        ]);
    }

    /**
     * Assign deals to an offer.
     * POST /api/v1/admin/offers/{offer}/deals
     */
    public function assignDeals(Request $request, $offerId)
    {
        $offer = Offer::findOrFail($offerId);

        $validator = Validator::make($request->all(), [
            'deal_ids' => 'required|array|min:1',
            'deal_ids.*' => 'exists:deals,id',
            'custom_discount_percent' => 'nullable|numeric|min:0|max:100',
            'custom_discount_amount' => 'nullable|numeric|min:0',
            'min_investment_override' => 'nullable|numeric|min:0',
            'is_featured' => 'boolean',
            'priority' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $syncData = [];
        foreach ($request->deal_ids as $dealId) {
            $syncData[$dealId] = [
                'custom_discount_percent' => $request->custom_discount_percent,
                'custom_discount_amount' => $request->custom_discount_amount,
                'min_investment_override' => $request->min_investment_override,
                'is_featured' => $request->is_featured ?? false,
                'priority' => $request->priority ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $offer->deals()->syncWithoutDetaching($syncData);

        // Update offer scope if not already set
        if ($offer->scope === 'global') {
            $offer->update(['scope' => 'deals']);
        }

        return response()->json([
            'success' => true,
            'message' => count($request->deal_ids) . ' deals assigned to offer',
            'assigned_count' => count($request->deal_ids),
        ]);
    }

    /**
     * Remove deal from offer.
     * DELETE /api/v1/admin/offers/{offer}/deals/{deal}
     */
    public function removeDeal($offerId, $dealId)
    {
        $offer = Offer::findOrFail($offerId);
        $deal = Deal::findOrFail($dealId);

        if (!$offer->deals()->where('deal_id', $deal->id)->exists()) {
            return response()->json([
                'error' => 'Deal not assigned to this offer'
            ], 404);
        }

        $offer->deals()->detach($deal->id);

        return response()->json([
            'success' => true,
            'message' => 'Deal removed from offer campaign',
        ]);
    }

    // =====================================================
    // OFFER-PLAN CAMPAIGNS
    // =====================================================

    /**
     * Get all plans assigned to an offer.
     * GET /api/v1/admin/offers/{offer}/plans
     */
    public function getPlans($offerId)
    {
        $offer = Offer::with(['plans' => function ($query) {
            $query->withCount('subscriptions')
                  ->orderByPivot('priority', 'desc');
        }])->findOrFail($offerId);

        return response()->json([
            'success' => true,
            'offer' => $offer,
            'plans' => $offer->plans,
        ]);
    }

    /**
     * Assign plans to an offer.
     * POST /api/v1/admin/offers/{offer}/plans
     */
    public function assignPlans(Request $request, $offerId)
    {
        $offer = Offer::findOrFail($offerId);

        $validator = Validator::make($request->all(), [
            'plan_ids' => 'required|array|min:1',
            'plan_ids.*' => 'exists:plans,id',
            'additional_discount_percent' => 'nullable|numeric|min:0|max:100',
            'is_exclusive' => 'boolean',
            'priority' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $syncData = [];
        foreach ($request->plan_ids as $planId) {
            $syncData[$planId] = [
                'additional_discount_percent' => $request->additional_discount_percent,
                'is_exclusive' => $request->is_exclusive ?? false,
                'priority' => $request->priority ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $offer->plans()->syncWithoutDetaching($syncData);

        // Update offer scope if not already set
        if ($offer->scope === 'global') {
            $offer->update(['scope' => 'plans']);
        }

        return response()->json([
            'success' => true,
            'message' => count($request->plan_ids) . ' plans assigned to offer',
            'assigned_count' => count($request->plan_ids),
        ]);
    }

    /**
     * Remove plan from offer.
     * DELETE /api/v1/admin/offers/{offer}/plans/{plan}
     */
    public function removePlan($offerId, $planId)
    {
        $offer = Offer::findOrFail($offerId);
        $plan = Plan::findOrFail($planId);

        if (!$offer->plans()->where('plan_id', $plan->id)->exists()) {
            return response()->json([
                'error' => 'Plan not assigned to this offer'
            ], 404);
        }

        $offer->plans()->detach($plan->id);

        return response()->json([
            'success' => true,
            'message' => 'Plan removed from offer campaign',
        ]);
    }

    // =====================================================
    // CAMPAIGN PERFORMANCE
    // =====================================================

    /**
     * Get campaign performance statistics.
     * GET /api/v1/admin/offers/{offer}/statistics
     */
    public function statistics($offerId)
    {
        $offer = Offer::with(['usages', 'statistics'])->findOrFail($offerId);

        $stats = [
            'total_usage_count' => $offer->usage_count,
            'total_users' => $offer->usages()->distinct('user_id')->count(),
            'total_revenue' => $offer->usages()->sum('investment_amount'),
            'total_discount_given' => $offer->usages()->sum('discount_applied'),

            // By product
            'product_performance' => $offer->products()->get()->map(function ($product) use ($offer) {
                $productStats = OfferStatistic::where('offer_id', $offer->id)
                    ->where('product_id', $product->id)
                    ->selectRaw('
                        SUM(total_views) as views,
                        SUM(total_applications) as applications,
                        SUM(total_conversions) as conversions,
                        SUM(total_discount_given) as discount,
                        SUM(total_revenue_generated) as revenue
                    ')
                    ->first();

                return [
                    'product' => $product,
                    'stats' => $productStats,
                ];
            }),

            // By deal
            'deal_performance' => $offer->deals()->get()->map(function ($deal) use ($offer) {
                $dealStats = OfferStatistic::where('offer_id', $offer->id)
                    ->where('deal_id', $deal->id)
                    ->selectRaw('
                        SUM(total_views) as views,
                        SUM(total_applications) as applications,
                        SUM(total_conversions) as conversions,
                        SUM(total_discount_given) as discount,
                        SUM(total_revenue_generated) as revenue
                    ')
                    ->first();

                return [
                    'deal' => $deal,
                    'stats' => $dealStats,
                ];
            }),

            // Time-series (last 30 days)
            'daily_stats' => OfferStatistic::where('offer_id', $offer->id)
                ->where('stat_date', '>=', now()->subDays(30))
                ->groupBy('stat_date')
                ->selectRaw('
                    stat_date,
                    SUM(total_views) as views,
                    SUM(total_applications) as applications,
                    SUM(total_conversions) as conversions,
                    SUM(total_discount_given) as discount,
                    SUM(total_revenue_generated) as revenue
                ')
                ->orderBy('stat_date', 'desc')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'offer' => $offer,
            'stats' => $stats,
        ]);
    }
}
