<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Deal;
use App\Models\Investment;
use Illuminate\Http\Request;

class DealController extends Controller
{
    /**
     * Get available deals for user
     * GET /api/v1/user/deals
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Deal::live()->with(['product']);

        // Filter by sector if provided
        if ($request->filled('sector')) {
            $query->where('sector', $request->sector);
        }

        // Filter by deal type
        if ($request->filled('deal_type')) {
            $query->where('deal_type', $request->deal_type);
        }

        // Search by company name or title
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('company', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $deals = $query->orderBy('sort_order', 'asc')
            ->orderBy('deal_closes_at', 'asc')
            ->paginate(20);

        // Add computed properties
        $dealsData = $deals->items();
        foreach ($dealsData as $deal) {
            $deal->remaining_shares = $deal->getRemainingSharesAttribute();
            $deal->is_available = $deal->getIsAvailableAttribute();

            // Check if user has invested in this deal
            $deal->user_investment = Investment::where('user_id', $user->id)
                ->where('deal_id', $deal->id)
                ->whereIn('status', ['active', 'pending'])
                ->first();
        }

        return response()->json([
            'success' => true,
            'deals' => $dealsData,
            'pagination' => [
                'total' => $deals->total(),
                'per_page' => $deals->perPage(),
                'current_page' => $deals->currentPage(),
                'last_page' => $deals->lastPage(),
            ],
        ]);
    }

    /**
     * Get deal details
     * GET /api/v1/user/deals/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $deal = Deal::with(['product'])
            ->where('status', 'active')
            ->findOrFail($id);

        // Add computed properties
        $deal->remaining_shares = $deal->getRemainingSharesAttribute();
        $deal->is_available = $deal->getIsAvailableAttribute();

        // Check if user has already invested
        $userInvestment = Investment::where('user_id', $user->id)
            ->where('deal_id', $deal->id)
            ->whereIn('status', ['active', 'pending'])
            ->first();

        // Get user's active subscriptions for investment
        $activeSubscriptions = $user->subscriptions()
            ->whereIn('status', ['active', 'paused'])
            ->with('plan')
            ->get();

        // Add available balance to each subscription
        foreach ($activeSubscriptions as $subscription) {
            $subscription->available_balance = $subscription->availableBalance;
        }

        return response()->json([
            'success' => true,
            'deal' => $deal,
            'user_investment' => $userInvestment,
            'user_subscriptions' => $activeSubscriptions,
            'is_available' => $deal->is_available,
            'remaining_shares' => $deal->remaining_shares,
        ]);
    }

    /**
     * Get featured deals
     * GET /api/v1/user/deals/featured
     */
    public function featured(Request $request)
    {
        $user = $request->user();

        $deals = Deal::featured()
            ->with(['product'])
            ->orderBy('sort_order', 'asc')
            ->limit(6)
            ->get();

        // Add computed properties
        foreach ($deals as $deal) {
            $deal->remaining_shares = $deal->getRemainingSharesAttribute();
            $deal->is_available = $deal->getIsAvailableAttribute();

            // Check if user has invested
            $deal->user_investment = Investment::where('user_id', $user->id)
                ->where('deal_id', $deal->id)
                ->whereIn('status', ['active', 'pending'])
                ->first();
        }

        return response()->json([
            'success' => true,
            'deals' => $deals,
        ]);
    }
}
