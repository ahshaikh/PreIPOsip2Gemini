<?php
// V-FINAL-1730-583 (Created) | [PROTOCOL 1 MERGE]: Added Declarative Preview

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WithdrawalController extends Controller
{
    protected $withdrawalService;

    public function __construct(WithdrawalService $withdrawalService)
    {
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * Test: testUserCanViewWithdrawalHistory
     */
    public function index(Request $request)
    {
        $history = $request->user()->withdrawals()
            ->latest()
            ->paginate(20);
            
        return response()->json($history);
    }

    /**
     * [PROTOCOL 1] DECLARATIVE PREVIEW
     * UI asks "What happens if I withdraw X?" -> Server answers with Fees, Taxes, Net.
     * This eliminates client-side math and hardcoded thresholds.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $amount = (float) $validated['amount'];
        
        // 1. Calculate Fees & Taxes (Centralized Logic)
        // In a real scenario, this should come from a FeeCalculationService
        // For now, we enforce a standard logic or 0 if free.
        $fee = 0; 
        $tds = 0; // Future: $this->taxService->calculateTds($amount);

        // 2. Compliance Checks (Source of Truth)
        // We check the global setting or default to 50k
        $autoApproveThreshold = (int) setting('finance.auto_approve_threshold', 50000);
        $requiresManualReview = $amount > $autoApproveThreshold;

        $net = $amount - $fee - $tds;

        // 3. Return the "Quote"
        return response()->json([
            'data' => [
                'requested_amount' => $amount,
                'breakdown' => [
                    'fee' => $fee,
                    'tds' => $tds,
                ],
                'net_amount' => $net,
                'workflow' => [
                    'requires_manual_review' => $requiresManualReview,
                    'estimated_settlement' => 'T+1 Days', // Could be dynamic based on bank holidays
                ],
                'disclaimer' => 'Fees and TDS are subject to RBI/Income Tax regulations.',
            ]
        ]);
    }

    /**
     * Test: testUserCanCancelPendingWithdrawal
     */
    public function cancel(Request $request, Withdrawal $withdrawal)
    {
        try {
            $this->withdrawalService->cancelUserWithdrawal($request->user(), $withdrawal);
            return response()->json(['message' => 'Withdrawal request cancelled successfully.']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}