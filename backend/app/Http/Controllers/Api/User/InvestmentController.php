<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Deal;
use App\Models\Subscription;
use App\Models\Payment;
use App\Models\Campaign;
use App\Services\WalletService;
use App\Services\AllocationService;
use App\Services\CampaignService;
use App\Enums\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestmentController extends Controller
{
    protected $walletService;
    protected $allocationService;
    protected $campaignService;

    public function __construct(
        WalletService $walletService,
        AllocationService $allocationService,
        CampaignService $campaignService
    ) {
        $this->walletService = $walletService;
        $this->allocationService = $allocationService;
        $this->campaignService = $campaignService;
    }
    /**
     * Get user's investments
     * GET /api/v1/user/investments
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Investment::where('user_id', $user->id)
            ->with(['deal.product', 'company', 'subscription.plan']);

        // Filter by status if provided
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $investments = $query->orderBy('invested_at', 'desc')->paginate(20);

        // Calculate current value for each investment
        $investmentsData = $investments->items();
        foreach ($investmentsData as $investment) {
            $investment->current_value = $investment->getCurrentValueAttribute();
            $investment->unrealized_profit_loss = $investment->getUnrealizedProfitLossAttribute();
            $investment->profit_loss_percentage = $investment->getProfitLossPercentageAttribute();
        }

        return response()->json([
            'success' => true,
            'investments' => $investmentsData,
            'pagination' => [
                'total' => $investments->total(),
                'per_page' => $investments->perPage(),
                'current_page' => $investments->currentPage(),
                'last_page' => $investments->lastPage(),
            ],
        ]);
    }

    /**
     * Get user's portfolio summary
     * GET /api/v1/user/portfolio
     */
    public function portfolio(Request $request)
    {
        $user = $request->user();

        // Get active investments
        $activeInvestments = Investment::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('deal')
            ->get();

        // Calculate totals
        $totalInvested = Investment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'pending'])
            ->sum('total_amount');

        $totalCurrentValue = 0;
        foreach ($activeInvestments as $investment) {
            $totalCurrentValue += $investment->getCurrentValueAttribute();
        }

        $unrealizedProfitLoss = $totalCurrentValue - $totalInvested;

        $stats = [
            'total_invested' => (float) $totalInvested,
            'active_investments_count' => Investment::where('user_id', $user->id)
                ->where('status', 'active')
                ->count(),
            'pending_investments_count' => Investment::where('user_id', $user->id)
                ->where('status', 'pending')
                ->count(),
            'total_current_value' => (float) $totalCurrentValue,
            'unrealized_profit_loss' => (float) $unrealizedProfitLoss,
            'unrealized_profit_loss_percentage' => $totalInvested > 0
                ? (($unrealizedProfitLoss / $totalInvested) * 100)
                : 0,
            'exited_investments_count' => Investment::where('user_id', $user->id)
                ->where('status', 'exited')
                ->count(),
            'realized_profit_loss' => (float) Investment::where('user_id', $user->id)
                ->where('status', 'exited')
                ->sum('profit_loss'),
        ];

        return response()->json([
            'success' => true,
            'portfolio' => $stats,
        ]);
    }

    /**
     * Create a new investment
     * POST /api/v1/user/investments
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'deal_id' => 'required|exists:deals,id',
            'subscription_id' => 'required|exists:subscriptions,id',
            'shares_allocated' => 'required|integer|min:1',
            'campaign_code' => 'nullable|string',
            'campaign_terms_accepted' => 'nullable|boolean',
            'campaign_disclaimer_acknowledged' => 'nullable|boolean',
        ]);

        $user = $request->user();

        // Verify subscription ownership
        $subscription = Subscription::where('id', $validated['subscription_id'])
            ->where('user_id', $user->id)
            ->with('plan')
            ->firstOrFail();

        if (!in_array($subscription->status, ['active', 'paused'])) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription must be active to invest.',
            ], 400);
        }

        // Verify deal availability
        $deal = Deal::with('product')->findOrFail($validated['deal_id']);

        if (!$deal->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'This deal is no longer available.',
            ], 400);
        }

        if ($deal->remaining_shares < $validated['shares_allocated']) {
            return response()->json([
                'success' => false,
                'message' => "Only {$deal->remaining_shares} shares available.",
            ], 400);
        }

        // Calculate investment amount
        $totalAmount = $validated['shares_allocated'] * $deal->share_price;

        // Check minimum investment
        if ($totalAmount < $deal->min_investment) {
            return response()->json([
                'success' => false,
                'message' => "Minimum investment is ₹{$deal->min_investment}. You need at least " .
                            ceil($deal->min_investment / $deal->share_price) . " shares.",
            ], 400);
        }

        // Campaign/Discount Application
        $campaign = null;
        $discount = 0;
        $finalAmount = $totalAmount;

        if (!empty($validated['campaign_code'])) {
            $campaign = $this->campaignService->validateCampaignCode($validated['campaign_code']);

            if (!$campaign) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid campaign code.',
                ], 400);
            }

            // Check if campaign is applicable
            $applicabilityCheck = $this->campaignService->isApplicable($campaign, $user, $totalAmount);
            if (!$applicabilityCheck['applicable']) {
                return response()->json([
                    'success' => false,
                    'message' => $applicabilityCheck['reason'],
                ], 400);
            }

            // Calculate discount
            $discount = $this->campaignService->calculateDiscount($campaign, $totalAmount);
            $finalAmount = $totalAmount - $discount;
        }

        // Check wallet balance (New payment flow: Payment → Wallet → User selects shares → Debit wallet)
        $wallet = $user->wallet;
        if (!$wallet || $wallet->balance < $finalAmount) {
            $availableBalance = $wallet ? $wallet->balance : 0;
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance. Available: ₹' .
                            number_format($availableBalance, 2) .
                            ($discount > 0 ? ' (After ₹' . number_format($discount, 2) . ' discount)' : ''),
            ], 400);
        }

        try {
            DB::beginTransaction();

            // 1. Debit wallet for share purchase (using final amount after discount)
            $description = "Share purchase: {$validated['shares_allocated']} shares of {$deal->product->name}";
            if ($discount > 0) {
                $description .= " (₹" . number_format($discount, 2) . " discount applied)";
            }

            $this->walletService->withdraw(
                $user,
                $finalAmount,
                TransactionType::INVESTMENT,
                $description,
                null,
                false // Not locked, immediate debit
            );

            // 2. Create investment record
            $investment = Investment::create([
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'deal_id' => $deal->id,
                'company_id' => $deal->company_id, // Deal now has company_id FK
                'investment_code' => 'INV-' . strtoupper(uniqid()),
                'shares_allocated' => $validated['shares_allocated'],
                'price_per_share' => $deal->share_price,
                'total_amount' => $totalAmount, // Original amount before discount
                'status' => 'active',
                'invested_at' => now(),
            ]);

            // 3. Apply campaign if provided
            if ($campaign) {
                $campaignResult = $this->campaignService->applyCampaign(
                    $campaign,
                    $user,
                    $investment,
                    $totalAmount,
                    $validated['campaign_terms_accepted'] ?? false,
                    $validated['campaign_disclaimer_acknowledged'] ?? false
                );

                if (!$campaignResult['success']) {
                    // Rollback if campaign application fails
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Campaign application failed: ' . $campaignResult['message'],
                    ], 400);
                }
            }

            // 4. Allocate shares (creates UserInvestment records)
            // Note: We pass a dummy payment for allocation tracking
            // In the future, this should link to the actual payment that funded the wallet
            $dummyPayment = new Payment([
                'id' => null,
                'user_id' => $user->id,
                'amount' => $totalAmount,
            ]);
            $this->allocationService->allocateShares($dummyPayment, $totalAmount);

            DB::commit();

            Log::info("Investment created", [
                'user_id' => $user->id,
                'investment_id' => $investment->id,
                'deal_id' => $deal->id,
                'original_amount' => $totalAmount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'campaign_code' => $campaign?->code,
            ]);

            $responseData = [
                'success' => true,
                'message' => 'Investment created successfully.',
                'investment' => $investment->load(['deal', 'company']),
                'original_amount' => $totalAmount,
                'final_amount' => $finalAmount,
            ];

            if ($discount > 0) {
                $responseData['discount_applied'] = $discount;
                $responseData['campaign_code'] = $campaign->code;
            }

            return response()->json($responseData, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Investment creation failed", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create investment. Please try again.',
            ], 500);
        }
    }

    /**
     * Get a specific investment
     * GET /api/v1/user/investments/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $investment = Investment::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['deal.product', 'company', 'subscription.plan'])
            ->firstOrFail();

        $investment->current_value = $investment->getCurrentValueAttribute();
        $investment->unrealized_profit_loss = $investment->getUnrealizedProfitLossAttribute();
        $investment->profit_loss_percentage = $investment->getProfitLossPercentageAttribute();

        return response()->json([
            'success' => true,
            'investment' => $investment,
        ]);
    }

    /**
     * Cancel a pending investment
     * DELETE /api/v1/user/investments/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $investment = Investment::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $investment->update([
            'status' => 'cancelled',
            'notes' => 'Cancelled by user',
        ]);

        Log::info("Investment cancelled", [
            'user_id' => $user->id,
            'investment_id' => $investment->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Investment cancelled successfully.',
        ]);
    }
}
