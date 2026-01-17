<?php

namespace App\Http\Controllers\Api\Investor;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\CompanyInvestment;
use App\Models\RiskAcknowledgement;
use App\Services\BuyEnablementGuardService;
use App\Services\InvestmentSnapshotService;
use App\Services\WalletService;
use App\Services\PlatformSupremacyGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * PHASE 5 - Investor Investment Controller
 *
 * PURPOSE:
 * Handle company investment submissions with comprehensive defensive validation.
 *
 * CRITICAL: All frontend checks are re-validated on backend
 * - Wallet balance sufficiency
 * - Risk acknowledgements complete and recorded
 * - Company buy eligibility (suspension, freeze, lifecycle)
 * - Allocation amount positive and within limits
 * - Idempotency to prevent duplicate submissions
 *
 * AUDIT GAP FIX: This controller addresses GAP 1 from Protocol-1 audit
 */
class InvestorInvestmentController extends Controller
{
    protected BuyEnablementGuardService $buyGuard;
    protected InvestmentSnapshotService $snapshotService;
    protected WalletService $walletService;
    protected PlatformSupremacyGuard $platformGuard;

    public function __construct(
        BuyEnablementGuardService $buyGuard,
        InvestmentSnapshotService $snapshotService,
        WalletService $walletService,
        PlatformSupremacyGuard $platformGuard
    ) {
        $this->buyGuard = $buyGuard;
        $this->snapshotService = $snapshotService;
        $this->walletService = $walletService;
        $this->platformGuard = $platformGuard;
    }

    /**
     * Submit investment with comprehensive backend validation
     *
     * POST /api/investor/investments
     *
     * Request Body:
     * {
     *   "allocations": [
     *     {
     *       "company_id": 1,
     *       "amount": 10000,
     *       "acknowledged_risks": ["illiquidity", "no_guarantee", "platform_non_advisory", "material_changes"]
     *     }
     *   ]
     * }
     *
     * DEFENSIVE VALIDATION (GAP 1 FIX):
     * 1. Wallet balance sufficiency
     * 2. All 4 risk acknowledgements present
     * 3. Company buy eligibility (6-layer guard)
     * 4. Platform supremacy (suspension, freeze)
     * 5. Allocation amount > 0 and <= wallet balance
     * 6. Idempotency check
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // 1. INPUT VALIDATION
        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.company_id' => 'required|integer|exists:companies,id',
            'allocations.*.amount' => 'required|numeric|min:1',
            'allocations.*.acknowledged_risks' => 'required|array|min:4',
            'allocations.*.acknowledged_risks.*' => 'required|string|in:illiquidity,no_guarantee,platform_non_advisory,material_changes',
            'idempotency_key' => 'nullable|string|max:255', // GAP 3: Idempotency support
        ]);

        // 2. IDEMPOTENCY CHECK (GAP 3 FIX)
        if (!empty($validated['idempotency_key'])) {
            $existing = CompanyInvestment::where('user_id', $user->id)
                ->where('idempotency_key', $validated['idempotency_key'])
                ->first();

            if ($existing) {
                Log::info('[INVESTMENT] Idempotency key matched, returning existing investment', [
                    'user_id' => $user->id,
                    'idempotency_key' => $validated['idempotency_key'],
                    'investment_id' => $existing->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Investment already submitted',
                    'data' => [
                        'investment_ids' => [$existing->id],
                        'snapshot_ids' => [$existing->disclosure_snapshot_id],
                    ],
                ], 200);
            }
        }

        // 3. WALLET BALANCE CHECK (CRITICAL - GAP 1)
        $wallet = $user->wallet;
        if (!$wallet) {
            return $this->errorResponse('WALLET_NOT_FOUND', 'Wallet not found. Please contact support.', 500);
        }

        $totalAmount = collect($validated['allocations'])->sum('amount');

        if ($wallet->balance < $totalAmount) {
            return $this->errorResponse(
                'INSUFFICIENT_BALANCE',
                "Insufficient wallet balance. Required: ₹{$totalAmount}, Available: ₹{$wallet->balance}",
                400
            );
        }

        // 4. VALIDATE EACH ALLOCATION
        $allocations = $validated['allocations'];
        $companies = [];
        $investmentRecords = [];
        $snapshotIds = [];

        try {
            DB::beginTransaction();

            foreach ($allocations as $allocation) {
                $companyId = $allocation['company_id'];
                $amount = $allocation['amount'];
                $acknowledgedRisks = $allocation['acknowledged_risks'];

                // 4a. LOAD COMPANY
                $company = Company::findOrFail($companyId);
                $companies[$companyId] = $company;

                // 4b. PLATFORM SUPREMACY CHECK (CRITICAL - GAP 1)
                $platformCheck = $this->platformGuard->canPerformAction($company, 'create_investment', $user);

                if (!$platformCheck['allowed']) {
                    DB::rollBack();
                    return $this->errorResponse(
                        'PLATFORM_RESTRICTION',
                        "Investment blocked by platform: {$platformCheck['reason']}",
                        403,
                        [
                            'company_id' => $companyId,
                            'blocking_state' => $platformCheck['blocking_state'] ?? null,
                        ]
                    );
                }

                // 4c. BUY ELIGIBILITY CHECK (6-LAYER GUARD - CRITICAL - GAP 1)
                $buyCheck = $this->buyGuard->canInvest($companyId, $user->id);

                if (!$buyCheck['allowed']) {
                    DB::rollBack();

                    // Find first critical blocker
                    $criticalBlocker = collect($buyCheck['blockers'])->first(fn($b) => $b['severity'] === 'critical');
                    $message = $criticalBlocker ? $criticalBlocker['message'] : 'Investment not allowed';

                    return $this->errorResponse(
                        'BUY_ELIGIBILITY_FAILED',
                        $message,
                        403,
                        [
                            'company_id' => $companyId,
                            'blockers' => $buyCheck['blockers'],
                        ]
                    );
                }

                // 4d. RISK ACKNOWLEDGEMENT CHECK (CRITICAL - GAP 1)
                $requiredRisks = ['illiquidity', 'no_guarantee', 'platform_non_advisory', 'material_changes'];
                $missingRisks = array_diff($requiredRisks, $acknowledgedRisks);

                if (!empty($missingRisks)) {
                    DB::rollBack();
                    return $this->errorResponse(
                        'ACKNOWLEDGEMENT_MISSING',
                        'All 4 required risk acknowledgements must be provided',
                        400,
                        [
                            'company_id' => $companyId,
                            'missing_risks' => $missingRisks,
                            'required_risks' => $requiredRisks,
                        ]
                    );
                }

                // 4e. AMOUNT VALIDATION (CRITICAL - GAP 1)
                if ($amount <= 0) {
                    DB::rollBack();
                    return $this->errorResponse(
                        'INVALID_AMOUNT',
                        'Investment amount must be greater than zero',
                        400,
                        ['company_id' => $companyId, 'amount' => $amount]
                    );
                }

                if ($amount > $wallet->balance) {
                    DB::rollBack();
                    return $this->errorResponse(
                        'AMOUNT_EXCEEDS_BALANCE',
                        "Amount exceeds wallet balance for company {$company->name}",
                        400,
                        ['company_id' => $companyId, 'amount' => $amount, 'balance' => $wallet->balance]
                    );
                }

                // 5. CAPTURE SNAPSHOT (IMMUTABLE BINDING)
                $snapshotResult = $this->snapshotService->captureAtPurchase($companyId, $user->id);

                if (!$snapshotResult['success']) {
                    DB::rollBack();
                    return $this->errorResponse(
                        'SNAPSHOT_FAILED',
                        'Failed to capture investment snapshot',
                        500,
                        ['company_id' => $companyId]
                    );
                }

                $snapshotId = $snapshotResult['snapshot_id'];
                $snapshotIds[] = $snapshotId;

                // 6. RECORD RISK ACKNOWLEDGEMENTS (IMMUTABLE)
                foreach ($acknowledgedRisks as $riskType) {
                    RiskAcknowledgement::create([
                        'user_id' => $user->id,
                        'company_id' => $companyId,
                        'risk_type' => $riskType,
                        'acknowledged_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                }

                // 7. DEBIT WALLET (ATOMIC)
                $walletResult = $this->walletService->debit(
                    $user->id,
                    $amount,
                    "Investment in {$company->name}",
                    'company_investment',
                    ['company_id' => $companyId, 'snapshot_id' => $snapshotId]
                );

                if (!$walletResult['success']) {
                    DB::rollBack();
                    return $this->errorResponse(
                        'WALLET_DEBIT_FAILED',
                        'Wallet debit failed',
                        500,
                        ['company_id' => $companyId]
                    );
                }

                // 8. CREATE INVESTMENT RECORD
                $investment = CompanyInvestment::create([
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'amount' => $amount,
                    'disclosure_snapshot_id' => $snapshotId,
                    'status' => 'active',
                    'invested_at' => now(),
                    'idempotency_key' => $validated['idempotency_key'] ?? null,
                ]);

                $investmentRecords[] = $investment;

                Log::info('[INVESTMENT] Investment created successfully', [
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'amount' => $amount,
                    'investment_id' => $investment->id,
                    'snapshot_id' => $snapshotId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Investment submitted successfully',
                'data' => [
                    'investment_ids' => collect($investmentRecords)->pluck('id')->toArray(),
                    'snapshot_ids' => $snapshotIds,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[INVESTMENT] Investment submission failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                'INTERNAL_ERROR',
                'Investment submission failed. Please try again.',
                500
            );
        }
    }

    /**
     * Structured error response (GAP 4 FIX)
     *
     * @param string $errorCode Machine-readable error code
     * @param string $message Human-readable message
     * @param int $statusCode HTTP status code
     * @param array $context Additional context
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse(string $errorCode, string $message, int $statusCode, array $context = [])
    {
        return response()->json([
            'success' => false,
            'error_code' => $errorCode,
            'message' => $message,
            'context' => $context,
        ], $statusCode);
    }
}
