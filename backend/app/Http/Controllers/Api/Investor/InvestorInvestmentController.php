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
use App\Services\Accounting\AdminLedger;
use App\Services\CompanyShareAllocationService;
use App\Services\InvestorJourneyStateMachine;
use App\Services\InvestmentSecurityGuard;
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
    protected AdminLedger $adminLedger;
    protected CompanyShareAllocationService $allocationService;
    protected InvestorJourneyStateMachine $journeyStateMachine;
    protected InvestmentSecurityGuard $securityGuard;

    public function __construct(
        BuyEnablementGuardService $buyGuard,
        InvestmentSnapshotService $snapshotService,
        WalletService $walletService,
        PlatformSupremacyGuard $platformGuard,
        AdminLedger $adminLedger,
        CompanyShareAllocationService $allocationService,
        InvestorJourneyStateMachine $journeyStateMachine,
        InvestmentSecurityGuard $securityGuard
    ) {
        $this->buyGuard = $buyGuard;
        $this->snapshotService = $snapshotService;
        $this->walletService = $walletService;
        $this->platformGuard = $platformGuard;
        $this->adminLedger = $adminLedger;
        $this->allocationService = $allocationService;
        $this->journeyStateMachine = $journeyStateMachine;
        $this->securityGuard = $securityGuard;
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

        // DEBUG: Log incoming request data
        \Log::info('[INVESTMENT] Incoming request data', [
            'user_id' => $user->id,
            'allocations' => $request->input('allocations'),
            'idempotency_key' => $request->input('idempotency_key'),
        ]);

        // 1. INPUT VALIDATION
        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.company_id' => 'required|integer|exists:companies,id',
            'allocations.*.amount' => 'required|numeric|min:1',
            'allocations.*.acknowledged_risks' => 'required|array|min:4',
            'allocations.*.acknowledged_risks.*' => 'required|string|in:illiquidity,no_guarantee,platform_non_advisory,material_changes',
            'allocations.*.journey_token' => 'required|string|size:64', // P0 FIX (GAP 18): Journey token required
            'allocations.*.snapshot_id' => 'required|integer|min:1', // P0 FIX (GAP 21): Snapshot ID for freshness validation
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

        // 3. P0 FIX (GAP 18-20): VALIDATE INVESTOR JOURNEY STATE MACHINE
        // CRITICAL: Proves investor followed required sequence before investment
        $journeyValidations = [];
        foreach ($validated['allocations'] as $allocation) {
            $journeyValidation = $this->journeyStateMachine->validateInvestmentRequest(
                $user->id,
                $allocation['company_id'],
                $allocation['journey_token']
            );

            if (!$journeyValidation['valid']) {
                Log::warning('[INVESTMENT] Journey validation failed', [
                    'user_id' => $user->id,
                    'company_id' => $allocation['company_id'],
                    'journey_token' => substr($allocation['journey_token'], 0, 10) . '...',
                    'message' => $journeyValidation['message'],
                    'proof' => $journeyValidation['proof'] ?? null,
                ]);

                return $this->errorResponse(
                    'JOURNEY_VALIDATION_FAILED',
                    $journeyValidation['message'],
                    400,
                    [
                        'company_id' => $allocation['company_id'],
                        'journey_state' => $journeyValidation['journey']?->current_state,
                        'violations' => $journeyValidation['proof']['violations'] ?? [],
                    ]
                );
            }

            $journeyValidations[$allocation['company_id']] = $journeyValidation;
        }

        Log::info('[INVESTMENT] All journey validations passed', [
            'user_id' => $user->id,
            'company_count' => count($journeyValidations),
        ]);

        // 4. P0 FIX (GAP 21): SNAPSHOT FRESHNESS VALIDATION
        // CRITICAL: Prevents stale snapshot attacks where context changed after page load
        foreach ($validated['allocations'] as $allocation) {
            $freshnessCheck = $this->securityGuard->validateSnapshotFreshness(
                $allocation['company_id'],
                $allocation['snapshot_id']
            );

            if (!$freshnessCheck['valid']) {
                Log::warning('[INVESTMENT] SECURITY: Stale snapshot detected', [
                    'user_id' => $user->id,
                    'company_id' => $allocation['company_id'],
                    'provided_snapshot' => $allocation['snapshot_id'],
                    'current_snapshot' => $freshnessCheck['current_snapshot_id'],
                    'stale_fields' => $freshnessCheck['stale_fields'],
                ]);

                return $this->errorResponse(
                    'STALE_SNAPSHOT',
                    $freshnessCheck['message'],
                    409, // Conflict - state has changed
                    [
                        'company_id' => $allocation['company_id'],
                        'current_snapshot_id' => $freshnessCheck['current_snapshot_id'],
                        'stale_fields' => $freshnessCheck['stale_fields'],
                        'requires_refresh' => true,
                    ]
                );
            }
        }

        Log::info('[INVESTMENT] All snapshot freshness checks passed', [
            'user_id' => $user->id,
        ]);

        // 5. P0 FIX (GAP 23): DUPLICATE ATTEMPT DETECTION
        // Prevents rapid-fire duplicate submissions
        foreach ($validated['allocations'] as $allocation) {
            if ($this->securityGuard->isDuplicateAttempt(
                $user->id,
                $allocation['company_id'],
                $allocation['amount']
            )) {
                Log::warning('[INVESTMENT] SECURITY: Duplicate attempt blocked', [
                    'user_id' => $user->id,
                    'company_id' => $allocation['company_id'],
                    'amount' => $allocation['amount'],
                ]);

                return $this->errorResponse(
                    'DUPLICATE_ATTEMPT',
                    'This investment was recently submitted. Please wait before trying again.',
                    429, // Too Many Requests
                    [
                        'company_id' => $allocation['company_id'],
                        'retry_after_seconds' => 60,
                    ]
                );
            }
        }

        // 6. WALLET BALANCE CHECK (CRITICAL - GAP 1)
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

        // 5. VALIDATE EACH ALLOCATION
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

                // 5. RECORD RISK ACKNOWLEDGEMENTS FIRST (for snapshot capture)
                // P0 FIX: Record acknowledgements before snapshot so they can be included
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

                // 6. CREATE INVESTMENT RECORD FIRST (P0 FIX: Snapshot needs investment ID)
                // Investment created with null snapshot_id initially
                $investment = CompanyInvestment::create([
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'amount' => $amount,
                    'disclosure_snapshot_id' => null, // Will be updated after snapshot capture
                    'status' => 'pending', // Pending until snapshot captured
                    'invested_at' => now(),
                    'idempotency_key' => $validated['idempotency_key'] ?? null,
                ]);

                // 7. CAPTURE SNAPSHOT (IMMUTABLE BINDING)
                // P0 FIX: Correct method signature - captureAtPurchase(investmentId, User, Company, acknowledgements)
                try {
                    $snapshotId = $this->snapshotService->captureAtPurchase(
                        $investment->id,
                        $user,
                        $company,
                        $acknowledgedRisks
                    );
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('[INVESTMENT] Snapshot capture failed', [
                        'investment_id' => $investment->id,
                        'company_id' => $companyId,
                        'error' => $e->getMessage(),
                    ]);
                    return $this->errorResponse(
                        'SNAPSHOT_FAILED',
                        'Failed to capture investment snapshot: ' . $e->getMessage(),
                        500,
                        ['company_id' => $companyId]
                    );
                }

                $snapshotIds[] = $snapshotId;

                // 8. UPDATE INVESTMENT WITH SNAPSHOT ID
                $investment->update([
                    'disclosure_snapshot_id' => $snapshotId,
                    'status' => 'active',
                ]);

                // 9. DEBIT WALLET (ATOMIC)
                $walletResult = $this->walletService->debit(
                    $user->id,
                    $amount,
                    "Investment in {$company->name}",
                    'company_investment',
                    ['company_id' => $companyId, 'snapshot_id' => $snapshotId, 'investment_id' => $investment->id]
                );

                if (!$walletResult['success']) {
                    DB::rollBack();
                    return $this->errorResponse(
                        'WALLET_DEBIT_FAILED',
                        'Wallet debit failed: ' . ($walletResult['error'] ?? 'Unknown error'),
                        500,
                        ['company_id' => $companyId, 'wallet_error' => $walletResult['error'] ?? null]
                    );
                }

                // 10. P0 FIX: RECORD SHARE SALE IN ADMIN LEDGER
                // CRITICAL: This creates the platform cash credit entry
                // Without this, platform cannot prove it received money for shares sold
                $adminLedgerEntryId = null;
                try {
                    $ledgerEntries = $this->adminLedger->recordShareSale(
                        saleAmount: $amount,
                        investmentId: $investment->id,
                        companyId: $companyId,
                        bulkPurchaseId: null, // Will be updated by allocation service
                        description: "Share sale: User #{$user->id} invested ₹{$amount} in {$company->name}"
                    );

                    // Capture credit entry ID for allocation tracking
                    $adminLedgerEntryId = $ledgerEntries[1]->id ?? null;

                    Log::info('[INVESTMENT] Admin ledger entry created for share sale', [
                        'investment_id' => $investment->id,
                        'amount' => $amount,
                        'debit_entry_id' => $ledgerEntries[0]->id ?? null,
                        'credit_entry_id' => $adminLedgerEntryId,
                    ]);
                } catch (\Exception $e) {
                    // Log but don't fail the transaction - ledger is secondary to investment
                    // In production, this should alert admins for manual reconciliation
                    Log::error('[INVESTMENT] CRITICAL: Admin ledger entry failed', [
                        'investment_id' => $investment->id,
                        'amount' => $amount,
                        'error' => $e->getMessage(),
                    ]);
                    // Note: We continue despite ledger failure to not block user investment
                    // Reconciliation job should catch and fix these gaps
                }

                // 11. P0 FIX: ALLOCATE SHARES FROM INVENTORY (PROVENANCE CHAIN)
                // CRITICAL: This creates the immutable audit trail proving:
                // BulkPurchase (platform inventory) → CompanyInvestment (subscriber ownership)
                // Without this, we cannot prove which inventory lot funded which investment
                try {
                    $allocationResult = $this->allocationService->allocateForInvestment(
                        investment: $investment,
                        company: $company,
                        user: $user,
                        adminLedgerEntryId: $adminLedgerEntryId
                    );

                    if ($allocationResult['success']) {
                        Log::info('[INVESTMENT] Share allocation successful', [
                            'investment_id' => $investment->id,
                            'allocation_status' => $allocationResult['allocation_status'],
                            'allocated_value' => $allocationResult['allocated_value'],
                            'batches_used' => $allocationResult['batches_used'],
                        ]);
                    } else {
                        // Allocation failed but investment can still proceed
                        // Admin will need to manually allocate or add inventory
                        Log::warning('[INVESTMENT] Share allocation incomplete - needs admin review', [
                            'investment_id' => $investment->id,
                            'message' => $allocationResult['message'] ?? 'Unknown allocation issue',
                            'partial_allocation' => $allocationResult['allocated_value'] ?? 0,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Allocation failure should not block investment
                    // Creates reconciliation gap that admin must resolve
                    Log::error('[INVESTMENT] Share allocation failed - requires manual intervention', [
                        'investment_id' => $investment->id,
                        'company_id' => $companyId,
                        'amount' => $amount,
                        'error' => $e->getMessage(),
                    ]);
                }

                // 12. P0 FIX (GAP 18-20): COMPLETE INVESTOR JOURNEY
                // CRITICAL: Marks journey as successfully completed with investment
                // This creates the audit proof that investor followed the required sequence
                $journeyValidation = $journeyValidations[$companyId] ?? null;
                if ($journeyValidation && $journeyValidation['journey']) {
                    try {
                        $journeyCompletion = $this->journeyStateMachine->completeWithInvestment(
                            journey: $journeyValidation['journey'],
                            investment: $investment,
                            investmentSnapshotId: $snapshotId
                        );

                        if ($journeyCompletion['success']) {
                            Log::info('[INVESTMENT] Journey completed successfully', [
                                'investment_id' => $investment->id,
                                'journey_id' => $journeyValidation['journey']->id,
                                'journey_token' => $journeyValidation['journey']->journey_token,
                            ]);
                        } else {
                            // Journey completion failed but investment succeeded
                            // This is a reconciliation gap that should be investigated
                            Log::warning('[INVESTMENT] Journey completion failed post-investment', [
                                'investment_id' => $investment->id,
                                'journey_id' => $journeyValidation['journey']->id,
                                'message' => $journeyCompletion['message'],
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Don't fail the investment due to journey completion issues
                        Log::error('[INVESTMENT] Journey completion error', [
                            'investment_id' => $investment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $investmentRecords[] = $investment;

                Log::info('[INVESTMENT] Investment created successfully', [
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'amount' => $amount,
                    'investment_id' => $investment->id,
                    'snapshot_id' => $snapshotId,
                    'wallet_transaction_id' => $walletResult['transaction_id'] ?? null,
                    'ledger_recorded' => isset($ledgerEntries),
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
