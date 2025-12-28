<?php

namespace App\Services\Orchestration;

use App\Models\User;
use App\Models\Payment;
use App\Models\Investment;
use App\Models\Campaign;
use App\Services\Orchestration\Operations\OperationInterface;
use App\Services\Orchestration\Operations\OperationResult;
use App\Services\Orchestration\Saga\SagaCoordinator;
use App\Services\Orchestration\Compensation\CompensationService;
use App\Services\Accounting\AdminLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FinancialOrchestrator - Central Authority for All Financial Operations
 *
 * PROTOCOL:
 * 1. All financial operations MUST go through this orchestrator
 * 2. Orchestrator enforces ordering, atomicity, and failure recovery
 * 3. Every operation has a defined compensation path
 * 4. Failure-first semantics: system halts safely or enters recoverable state
 * 5. Admin solvency is provable at all times
 *
 * USAGE:
 * Do NOT call individual services directly for financial operations.
 * Instead:
 *   $orchestrator->executePaymentToInvestment($payment, $investment);
 *   $orchestrator->executeReferralBonus($referral);
 *   $orchestrator->executeWithdrawal($withdrawal);
 */
class FinancialOrchestrator
{
    private SagaCoordinator $sagaCoordinator;
    private CompensationService $compensationService;
    private AdminLedger $adminLedger;

    public function __construct(
        SagaCoordinator $sagaCoordinator,
        CompensationService $compensationService,
        AdminLedger $adminLedger
    ) {
        $this->sagaCoordinator = $sagaCoordinator;
        $this->compensationService = $compensationService;
        $this->adminLedger = $adminLedger;
    }

    /**
     * Execute Payment → Wallet Credit → Investment → Allocation
     *
     * This is the MASTER flow replacing:
     * - PaymentWebhookService::fulfillPayment()
     * - ProcessSuccessfulPaymentJob::handle()
     * - InvestmentController::store()
     * - ProcessAllocationJob::handle()
     *
     * FAILURE SEMANTICS:
     * - If ANY step fails, ALL previous steps are compensated
     * - Money is never trapped in limbo
     * - Admin balance remains consistent
     *
     * @return OperationResult Contains success/failure + provenance trail
     */
    public function executePaymentToInvestment(
        Payment $payment,
        Investment $investment,
        ?Campaign $campaign = null
    ): OperationResult {
        // Create saga context with full provenance
        $sagaContext = $this->sagaCoordinator->createContext([
            'payment_id' => $payment->id,
            'investment_id' => $investment->id,
            'user_id' => $payment->user_id,
            'campaign_id' => $campaign?->id,
            'original_amount' => $payment->amount,
        ]);

        try {
            return $this->sagaCoordinator->execute($sagaContext, [
                // Step 1: Verify compliance gates (KYC, limits, etc.)
                new \App\Services\Orchestration\Operations\VerifyComplianceOperation(
                    $payment->user,
                    'investment',
                    $payment->amount
                ),

                // Step 2: Calculate campaign benefit (if any)
                new \App\Services\Orchestration\Operations\CalculateCampaignBenefitOperation(
                    $campaign,
                    $payment->user,
                    $investment->total_amount
                ),

                // Step 3: Credit user wallet (ATOMIC)
                new \App\Services\Orchestration\Operations\CreditUserWalletOperation(
                    $payment->user,
                    $payment->amount,
                    $payment
                ),

                // Step 4: Record admin receipt in ledger
                new \App\Services\Orchestration\Operations\RecordAdminReceiptOperation(
                    $payment->amount,
                    $payment
                ),

                // Step 5: Debit user wallet for investment (ATOMIC)
                new \App\Services\Orchestration\Operations\DebitUserWalletOperation(
                    $payment->user,
                    $investment->final_amount, // After campaign discount
                    $investment
                ),

                // Step 6: Record campaign discount as admin liability (if any)
                new \App\Services\Orchestration\Operations\RecordCampaignLiabilityOperation(
                    $campaign,
                    $investment->total_amount - $investment->final_amount, // Discount amount
                    $investment
                ),

                // Step 7: Allocate shares from inventory (SYNC, not async!)
                new \App\Services\Orchestration\Operations\AllocateSharesOperation(
                    $investment,
                    $payment->user
                ),

                // Step 8: Mark investment as complete
                new \App\Services\Orchestration\Operations\CompleteInvestmentOperation(
                    $investment
                ),
            ]);
        } catch (\Throwable $e) {
            // Saga coordinator automatically compensates completed steps
            Log::critical('ORCHESTRATOR: Payment-to-Investment saga failed', [
                'saga_id' => $sagaContext->getId(),
                'payment_id' => $payment->id,
                'exception' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            // Return failure with compensation trail
            return OperationResult::failure(
                "Investment flow failed: {$e->getMessage()}",
                ['saga_id' => $sagaContext->getId(), 'compensated' => true]
            );
        }
    }

    /**
     * Execute Referral Bonus with Provenance
     *
     * REPLACES: ProcessReferralJob::handle()
     *
     * PROTOCOL:
     * - Referral bonus is admin LIABILITY (money owed to referrer)
     * - Must be recorded in admin ledger BEFORE wallet credit
     * - If wallet credit fails, admin ledger entry is reversed
     *
     * @return OperationResult
     */
    public function executeReferralBonus(
        User $referrer,
        User $referee,
        float $bonusAmount,
        $referral
    ): OperationResult {
        $sagaContext = $this->sagaCoordinator->createContext([
            'referrer_id' => $referrer->id,
            'referee_id' => $referee->id,
            'bonus_amount' => $bonusAmount,
            'referral_id' => $referral->id,
        ]);

        try {
            return $this->sagaCoordinator->execute($sagaContext, [
                // Step 1: Verify compliance (both KYC verified)
                new \App\Services\Orchestration\Operations\VerifyReferralComplianceOperation(
                    $referrer,
                    $referee
                ),

                // Step 2: Record as admin liability FIRST
                new \App\Services\Orchestration\Operations\RecordReferralLiabilityOperation(
                    $bonusAmount,
                    $referral
                ),

                // Step 3: Credit referrer wallet
                new \App\Services\Orchestration\Operations\CreditReferrerWalletOperation(
                    $referrer,
                    $bonusAmount,
                    $referral
                ),

                // Step 4: Update referral status
                new \App\Services\Orchestration\Operations\CompleteReferralOperation(
                    $referral
                ),
            ]);
        } catch (\Throwable $e) {
            Log::critical('ORCHESTRATOR: Referral bonus saga failed', [
                'saga_id' => $sagaContext->getId(),
                'referrer_id' => $referrer->id,
                'exception' => $e->getMessage(),
            ]);

            return OperationResult::failure(
                "Referral bonus failed: {$e->getMessage()}",
                ['saga_id' => $sagaContext->getId(), 'compensated' => true]
            );
        }
    }

    /**
     * Execute Withdrawal with Admin Solvency Check
     *
     * PROTOCOL:
     * - MUST verify admin has sufficient liquid cash BEFORE approval
     * - Records admin cash-out in ledger
     * - If bank transfer fails, ledger entry is reversed
     *
     * @return OperationResult
     */
    public function executeWithdrawal(
        User $user,
        float $amount,
        $withdrawal
    ): OperationResult {
        $sagaContext = $this->sagaCoordinator->createContext([
            'user_id' => $user->id,
            'amount' => $amount,
            'withdrawal_id' => $withdrawal->id,
        ]);

        try {
            return $this->sagaCoordinator->execute($sagaContext, [
                // Step 1: Verify compliance (KYC, limits)
                new \App\Services\Orchestration\Operations\VerifyComplianceOperation(
                    $user,
                    'withdrawal',
                    $amount
                ),

                // Step 2: Verify admin solvency (NEW - critical!)
                new \App\Services\Orchestration\Operations\VerifyAdminSolvencyOperation(
                    $amount
                ),

                // Step 3: Calculate TDS (BEFORE debit)
                new \App\Services\Orchestration\Operations\CalculateTdsOperation(
                    $amount,
                    'withdrawal'
                ),

                // Step 4: Debit user wallet
                new \App\Services\Orchestration\Operations\DebitUserWalletOperation(
                    $user,
                    $amount,
                    $withdrawal
                ),

                // Step 5: Record admin cash-out in ledger
                new \App\Services\Orchestration\Operations\RecordAdminCashOutOperation(
                    $amount,
                    $withdrawal
                ),

                // Step 6: Execute bank transfer
                new \App\Services\Orchestration\Operations\ExecuteBankTransferOperation(
                    $withdrawal
                ),

                // Step 7: Mark withdrawal as completed
                new \App\Services\Orchestration\Operations\CompleteWithdrawalOperation(
                    $withdrawal
                ),
            ]);
        } catch (\Throwable $e) {
            Log::critical('ORCHESTRATOR: Withdrawal saga failed', [
                'saga_id' => $sagaContext->getId(),
                'user_id' => $user->id,
                'amount' => $amount,
                'exception' => $e->getMessage(),
            ]);

            return OperationResult::failure(
                "Withdrawal failed: {$e->getMessage()}",
                ['saga_id' => $sagaContext->getId(), 'compensated' => true]
            );
        }
    }

    /**
     * Get Provenance Trail for Any Financial Operation
     *
     * Answers: "Why did this money move?"
     *
     * @param string $entityType 'payment', 'investment', 'withdrawal', 'bonus'
     * @param int $entityId
     * @return array Full provenance chain
     */
    public function getProvenance(string $entityType, int $entityId): array
    {
        return $this->sagaCoordinator->getProvenanceTrail($entityType, $entityId);
    }

    /**
     * Verify Admin Solvency (Can be called at any time)
     *
     * Returns:
     * - total_cash: Money admin has
     * - total_liabilities: Money admin owes (pending withdrawals, bonus commitments)
     * - total_inventory_cost: Money admin spent on inventory
     * - net_position: Cash - Liabilities - Inventory
     * - is_solvent: true if net_position >= 0
     */
    public function verifyAdminSolvency(): array
    {
        return $this->adminLedger->calculateSolvency();
    }

    /**
     * Force Reconciliation (Manual trigger or cron)
     *
     * Compares:
     * - Razorpay vs DB payments
     * - Wallet balances vs Transaction ledger
     * - Admin ledger vs calculated solvency
     * - Inventory remaining vs UserInvestment allocated
     *
     * Returns discrepancies for admin review
     */
    public function reconcile(): array
    {
        $reconciliationService = app(\App\Services\Orchestration\ReconciliationService::class);

        return $reconciliationService->executeFullReconciliation();
    }
}
