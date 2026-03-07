<?php
// V-ORCHESTRATION-2026: Single Financial Orchestration Boundary
// All financial mutations occur within ONE atomic transaction.
// Only this class may open DB transactions and acquire row locks for payment lifecycle.

namespace App\Services;

use App\Models\User;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Wallet;
use App\Models\Product;
use App\Models\BulkPurchase;
use App\Models\Referral;
use App\Models\Investment;
use App\Models\Campaign;
use App\Services\WalletService;
use App\Services\AllocationService;
use App\Services\BonusCalculatorService;
use App\Services\Orchestration\Operations\OperationResult;
use App\Services\Orchestration\Saga\SagaCoordinator;
use App\Services\Orchestration\Compensation\CompensationService;
use App\Services\Accounting\AdminLedger;
use App\Enums\TransactionType;
use App\Enums\ReversalSource;
use App\Jobs\SendPaymentConfirmationEmailJob;
use App\Jobs\GenerateLuckyDrawEntryJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * FinancialOrchestrator - Central Authority for All Financial Operations
 *
 * V-ORCHESTRATION-2026: SINGLE TRANSACTION BOUNDARY
 */
class FinancialOrchestrator
{
    /*
    |--------------------------------------------------------------------------
    | Financial Lifecycle Guard
    |--------------------------------------------------------------------------
    |
    | Rule 1: Jobs must never call lifecycle methods that represent a different
    | financial phase (payment -> allocation -> bonus -> refund).
    |
    | Rule 2: Jobs may only call the orchestrator method that corresponds to
    | the lifecycle stage they represent.
    |
    | Rule 3: Services must never call orchestrator lifecycle methods.
    | Lifecycle entrypoints must originate only from Controllers, Webhooks,
    | or Jobs.
    |
    | Violating these rules causes:
    | - duplicate ledger entries
    | - wallet double mutations
    | - broken financial invariants
    |
    */
    private ?SagaCoordinator $sagaCoordinator;
    private ?CompensationService $compensationService;
    private ?AdminLedger $adminLedger;

    private WalletService $walletService;
    private AllocationService $allocationService;
    private BonusCalculatorService $bonusService;
    private TdsCalculationService $tdsService;
    private DoubleEntryLedgerService $ledgerService;

    public function __construct(
        ?SagaCoordinator $sagaCoordinator = null,
        ?CompensationService $compensationService = null,
        ?AdminLedger $adminLedger = null,
        ?WalletService $walletService = null,
        ?AllocationService $allocationService = null,
        ?BonusCalculatorService $bonusService = null,
        ?TdsCalculationService $tdsService = null,
        ?DoubleEntryLedgerService $ledgerService = null
    ) {
        $this->sagaCoordinator = $sagaCoordinator;
        $this->compensationService = $compensationService;
        $this->adminLedger = $adminLedger;
        $this->walletService = $walletService ?? app(WalletService::class);
        $this->allocationService = $allocationService ?? app(AllocationService::class);
        $this->bonusService = $bonusService ?? app(BonusCalculatorService::class);
        $this->tdsService = $tdsService ?? app(TdsCalculationService::class);
        $this->ledgerService = $ledgerService ?? app(DoubleEntryLedgerService::class);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // V-ORCHESTRATION-2026: SINGLE TRANSACTION BOUNDARY METHODS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Settle a dispute with proper locking and ledger integrity.
     */
    public function settleDispute(
        \App\Models\Dispute $dispute,
        string $action,
        ?int $amountPaise = null,
        array $details = [],
        User $executor = null
    ): array {
        return DB::transaction(function () use ($dispute, $action, $amountPaise, $details, $executor) {
            $user = $dispute->user;
            $wallet = $this->acquireLockedWallet($user->id);

            $result = match ($action) {
                \App\Models\Dispute::SETTLEMENT_REFUND => $this->executeDisputeRefund($dispute, $wallet, $amountPaise, $details),
                \App\Models\Dispute::SETTLEMENT_CREDIT => $this->executeDisputeCredit($dispute, $wallet, $amountPaise, $details),
                \App\Models\Dispute::SETTLEMENT_ALLOCATION_CORRECTION => ['type' => 'allocation_correction', 'status' => 'pending_manual'],
                \App\Models\Dispute::SETTLEMENT_NONE => ['type' => 'none'],
            };

            // Update dispute
            $dispute->update([
                'settlement_action' => $action,
                'settlement_amount_paise' => $amountPaise,
                'settlement_details' => array_merge($details, ['result' => $result]),
                'status' => \App\Models\Dispute::STATUS_RESOLVED_APPROVED,
            ]);

            // Create timeline entry
            \App\Models\DisputeTimeline::create([
                'dispute_id' => $dispute->id,
                'event_type' => \App\Models\DisputeTimeline::EVENT_SETTLEMENT,
                'actor_user_id' => $executor?->id,
                'actor_role' => $executor ? \App\Models\DisputeTimeline::ROLE_ADMIN : \App\Models\DisputeTimeline::ROLE_SYSTEM,
                'title' => "Settlement: {$action}",
                'description' => "Dispute #{$dispute->id} settled with action: {$action}",
                'metadata' => ['result' => $result],
                'visible_to_investor' => true,
            ]);

            Log::info("ORCHESTRATOR: Dispute #{$dispute->id} settled via {$action}.");

            return $result;
        });
    }

    protected function executeDisputeRefund($dispute, Wallet $wallet, int $amountPaise, array $details): array
    {
        if ($amountPaise <= 0) throw new \Exception('Refund amount must be positive');

        $this->walletService->deposit($wallet, $amountPaise, TransactionType::REFUND, "Dispute #{$dispute->id} refund", $dispute);
        $this->debugFinancialInvariant($wallet);
        $this->ledgerService->recordDisputeSettlement($dispute->id, $amountPaise / 100, 'refund', "Dispute #{$dispute->id} refund");

        return ['type' => 'refund', 'amount_paise' => $amountPaise];
    }

    protected function executeDisputeCredit($dispute, Wallet $wallet, int $amountPaise, array $details): array
    {
        if ($amountPaise <= 0) throw new \Exception('Credit amount must be positive');

        $this->walletService->deposit($wallet, $amountPaise, TransactionType::BONUS_CREDIT, "Dispute #{$dispute->id} goodwill credit", $dispute);
        $this->debugFinancialInvariant($wallet);
        $this->ledgerService->recordDisputeSettlement($dispute->id, $amountPaise / 100, 'goodwill_credit', "Dispute #{$dispute->id} goodwill");

        return ['type' => 'credit', 'amount_paise' => $amountPaise];
    }

    /**
     * Execute company share allocation with proper locking and ledger integrity.
     */
    public function executeCompanyAllocation(
        \App\Models\CompanyInvestment $investment,
        \App\Models\Company $company,
        User $user,
        ?int $adminLedgerEntryId = null
    ): array {
        return DB::transaction(function () use ($investment, $company, $user, $adminLedgerEntryId) {
            $batches = BulkPurchase::where('company_id', $company->id)
                ->where('value_remaining', '>', 0)
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();

            $companyService = app(\App\Services\CompanyShareAllocationService::class);
            $result = $companyService->executeAllocationLogic($investment, $company, $user, $batches, $adminLedgerEntryId);

            Log::info("ORCHESTRATOR: Company investment #{$investment->id} allocated successfully.");

            return $result;
        });
    }

    /**
     * Reverse company share allocation with proper locking.
     */
    public function reverseCompanyAllocation(\App\Models\CompanyInvestment $investment, string $reason): void
    {
        DB::transaction(function () use ($investment, $reason) {
            $allocationLogs = \App\Models\ShareAllocationLog::where('allocatable_type', \App\Models\CompanyInvestment::class)
                ->where('allocatable_id', $investment->id)
                ->where('is_reversed', false)
                ->get();

            if ($allocationLogs->isEmpty()) return;

            foreach ($allocationLogs as $log) {
                $batch = BulkPurchase::where('id', $log->bulk_purchase_id)->lockForUpdate()->first();
                if ($batch) {
                    $batch->increment('value_remaining', $log->value_allocated);
                }

                $log->update([
                    'is_reversed' => true,
                    'reversed_at' => now(),
                    'reversal_reason' => $reason,
                ]);
            }

            $investment->update([
                'allocation_status' => 'unallocated',
                'allocated_value' => 0,
            ]);

            Log::info("ORCHESTRATOR: Reversed company allocation for investment #{$investment->id}.");
        });
    }

    /**
     * Execute complete payment allocation saga via single transaction.
     */
    public function executePaymentAllocationSaga(Payment $payment): \App\Models\SagaExecution
    {
        $saga = \App\Models\SagaExecution::create([
            'saga_id' => "payment-allocation-{$payment->id}-" . time(),
            'status' => 'processing',
            'steps_total' => 3,
            'steps_completed' => 0,
            'metadata' => [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'amount' => $payment->amount,
            ],
            'initiated_at' => now(),
        ]);

        try {
            DB::transaction(function () use ($payment, $saga) {
                $payment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
                $wallet = $this->acquireLockedWallet($payment->user_id);
                $subscription = $payment->subscription()->lockForUpdate()->first();

                // Step 1: Credit Wallet
                $this->walletService->deposit(
                    $wallet,
                    $payment->getAmountPaiseStrict(),
                    TransactionType::DEPOSIT,
                    "Payment #{$payment->id} credited (Saga)",
                    $payment
                );
                $this->debugFinancialInvariant($wallet);
                $this->markSagaStep($saga, 'credit_wallet', ['payment_id' => $payment->id]);

                // Step 2: Bonus
                if ($subscription) {
                    $bonusResultPaise = $this->bonusService->calculateAndAwardBonuses($payment, $subscription, $wallet);

                    if ($bonusResultPaise > 0) {
                        $bonusTransaction = \App\Models\BonusTransaction::where('payment_id', $payment->id)
                            ->where('user_id', $payment->user_id)
                            ->latest()
                            ->first();

                        if ($bonusTransaction) {
                            $this->walletService->deposit(
                                $wallet,
                                $bonusResultPaise,
                                TransactionType::BONUS_CREDIT,
                                $bonusTransaction->description,
                                $bonusTransaction
                            );
                            $this->debugFinancialInvariant($wallet);
                        }
                    }

                    if ($bonusResultPaise > 0) {
                        $this->markSagaStep($saga, 'credit_bonus', ['amount_paise' => $bonusResultPaise]);
                    }
                }

                // Step 3: Allocate Shares
                if ($subscription && $this->requiresAllocation($payment, $subscription)) {
                    $inventoryLocks = $this->acquireInventoryLocks($subscription);
                    $lockedBatches = $inventoryLocks['batches'];

                    if ($lockedBatches->isNotEmpty()) {
                        $allocationResult = $this->allocationService->allocateSharesLegacy(
                            $payment,
                            $lockedBatches,
                            (float) $payment->amount
                        );
                        
                        $this->markSagaStep($saga, 'allocate_shares', [
                            'refund_due' => $allocationResult['refund_due'] ?? 0
                        ]);

                        if (($allocationResult['refund_due'] ?? 0) > 0) {
                            $this->walletService->deposit(
                                $wallet,
                                (int) round($allocationResult['refund_due'] * 100),
                                TransactionType::REFUND,
                                "Fractional refund (Saga #{$payment->id})",
                                $payment
                            );
                            $this->debugFinancialInvariant($wallet);
                        }
                    }
                }

                $saga->update(['status' => 'completed', 'completed_at' => now()]);
            });

            Log::info("ORCHESTRATOR: Payment allocation saga completed for payment #{$payment->id}");
            return $saga;

        } catch (\Exception $e) {
            Log::error("ORCHESTRATOR: Payment allocation saga failed for payment #{$payment->id}: " . $e->getMessage());
            $saga->update(['status' => 'failed', 'failure_reason' => $e->getMessage(), 'failed_at' => now()]);
            $saga->update(['status' => 'compensated', 'compensated_at' => now()]);
            throw $e;
        }
    }

    protected function markSagaStep(\App\Models\SagaExecution $saga, string $step, array $data): void
    {
        $metadata = $saga->metadata ?? [];
        $metadata['steps'][$step] = array_merge($data, ['completed_at' => now()->toDateTimeString()]);
        
        $saga->update([
            'steps_completed' => $saga->steps_completed + 1,
            'metadata' => $metadata
        ]);
    }

    /**
     * Distribute profit share to user wallets with proper locking.
     */
    public function distributeProfitShare(\App\Models\ProfitShare $profitShare, User $admin): void
    {
        DB::transaction(function () use ($profitShare, $admin) {
            $lockedPeriod = \App\Models\ProfitShare::where('id', $profitShare->id)->lockForUpdate()->firstOrFail();
            
            if ($lockedPeriod->status !== 'calculated') {
                throw new \Exception("Distribution aborted: Period status is '{$lockedPeriod->status}' (Expected: calculated).");
            }

            $distributions = $lockedPeriod->distributions()->with('user.wallet', 'user.kyc')->get();
            if ($distributions->isEmpty()) throw new \Exception('No distributions to process.');

            $profitShareService = app(\App\Services\ProfitShareService::class);

            foreach ($distributions as $dist) {
                $user = $dist->user;
                if (!$user) continue;

                $wallet = $this->acquireLockedWallet($user->id);
                $result = $profitShareService->prepareDistributionRecord($dist, $lockedPeriod);
                $bonus = $result['bonus'];
                $tdsResult = $result['tds_result'];

                $this->ledgerService->recordProfitShareWithTds($dist->id, $tdsResult->grossAmount, $tdsResult->tdsAmount, $user->id);

                if ($tdsResult->netAmount > 0) {
                    $this->walletService->deposit(
                        $wallet,
                        (int) round($tdsResult->netAmount * 100),
                        TransactionType::BONUS_CREDIT,
                        "Profit Share: {$lockedPeriod->period_name}",
                        $bonus
                    );
                    $this->debugFinancialInvariant($wallet);
                }
                $dist->update(['bonus_transaction_id' => $bonus->id]);
            }
            
            $lockedPeriod->update(['status' => 'distributed', 'admin_id' => $admin->id]);
            Log::info("ORCHESTRATOR: Profit share period #{$profitShare->id} distributed.");
        });
    }

    /**
     * Reverse a single profit share distribution with locking.
     */
    public function reverseProfitShareDistribution(\App\Models\UserProfitShare $dist, string $reason): void
    {
        DB::transaction(function () use ($dist, $reason) {
            $dist = \App\Models\UserProfitShare::where('id', $dist->id)->lockForUpdate()->firstOrFail();
            $user = $dist->user;
            $wallet = $this->acquireLockedWallet($user->id);
            $bonus = $dist->bonusTransaction;

            if (!$bonus) throw new \Exception("No bonus transaction found for distribution #{$dist->id}");

            $netAmountPaise = (int) round(($bonus->amount - $bonus->tds_deducted) * 100);

            if ($wallet->balance_paise < $netAmountPaise) {
                throw new \Exception("User #{$user->id} has insufficient balance to reverse profit share.");
            }

            $this->walletService->withdraw($wallet, $netAmountPaise, TransactionType::CHARGEBACK, "Reversal: {$reason}", $dist);
            $this->debugFinancialInvariant($wallet);
            $bonus->update(['description' => $bonus->description . " [REVERSED]"]);
            Log::info("ORCHESTRATOR: Reversed profit share distribution #{$dist->id} for user #{$user->id}");
        });
    }

    /**
     * Award lucky draw prize with proper locking.
     */
    public function awardPrize(\App\Models\LuckyDraw $draw, int $winnerId, int $rank, int $grossAmountPaise): void
    {
        DB::transaction(function () use ($draw, $winnerId, $rank, $grossAmountPaise) {
            $user = User::findOrFail($winnerId);
            $wallet = $this->acquireLockedWallet($user->id);

            $luckyDrawService = app(\App\Services\LuckyDrawService::class);
            $result = $luckyDrawService->preparePrizeWinner($draw, $winnerId, $rank, $grossAmountPaise);
            $bonus = $result['bonus'];
            $tdsResult = $result['tds_result'];

            $this->ledgerService->recordBonusWithTds($bonus, $tdsResult->grossAmount, $tdsResult->tdsAmount);

            $this->walletService->deposit(
                $wallet,
                (int) round($tdsResult->netAmount * 100),
                TransactionType::BONUS_CREDIT,
                $tdsResult->getDescription("Lucky Draw Prize (Rank {$rank})"),
                $bonus
            );
            $this->debugFinancialInvariant($wallet);

            Log::info("ORCHESTRATOR: Lucky draw prize awarded to user #{$winnerId}. Rank: {$rank}, Gross: {$grossAmountPaise}");
        });
    }

    /**
     * Award bulk bonus with proper locking and ledger integrity.
     */
    public function awardBulkBonus(User $user, int $amountPaise, string $reason, string $type): void
    {
        DB::transaction(function () use ($user, $amountPaise, $reason, $type) {
            $wallet = $this->acquireLockedWallet($user->id);
            $result = $this->bonusService->prepareBulkBonus($user, $amountPaise, $reason, $type);
            $bonusTransaction = $result['bonus'];
            $tdsResult = $result['tds_result'];

            $this->ledgerService->recordBonusWithTds($bonusTransaction, $tdsResult->grossAmount, $tdsResult->tdsAmount);

            $this->walletService->deposit(
                $wallet,
                (int) round($tdsResult->netAmount * 100),
                TransactionType::BONUS_CREDIT,
                $tdsResult->getDescription("Bulk Bonus: {$reason}"),
                $bonusTransaction
            );
            $this->debugFinancialInvariant($wallet);

            Log::info("ORCHESTRATOR: Bulk Bonus awarded to user #{$user->id}. Gross: {$tdsResult->grossAmount}, Net: {$tdsResult->netAmount}");
        });
    }

    /**
     * Award referral bonus from job with proper locking.
     */
    public function awardReferralBonusFromJob(User $referredUser): void
    {
        DB::transaction(function () use ($referredUser) {
            $referral = Referral::where('referred_id', $referredUser->id)->where('status', 'pending')->lockForUpdate()->first();
            if (!$referral) return;

            $referrer = $referral->referrer;
            $wallet = $this->acquireLockedWallet($referrer->id);

            $referralService = app(\App\Services\ReferralService::class);
            $activeCampaign = \App\Models\ReferralCampaign::running()->first();
            $campaignToUse = $referral->referral_campaign_id ? \App\Models\ReferralCampaign::find($referral->referral_campaign_id) : $activeCampaign;

            $updateData = ['status' => 'completed', 'completed_at' => now()];
            if (!$referral->referral_campaign_id && $activeCampaign) $updateData['referral_campaign_id'] = $activeCampaign->id;
            $referral->update($updateData);

            $bonusData = $referralService->calculateReferralBonus($referredUser, $campaignToUse);
            $grossBonus = $bonusData['amount'];
            $tdsResult = $this->tdsService->calculate($grossBonus, 'referral');

            $bonus = \App\Models\BonusTransaction::create([
                'user_id' => $referrer->id,
                'subscription_id' => $referrer->subscription?->id,
                'type' => 'referral',
                'amount' => $tdsResult->grossAmount,
                'tds_deducted' => $tdsResult->tdsAmount,
                'base_amount' => $grossBonus,
                'multiplier_applied' => 1.0,
                'description' => $bonusData['description'],
            ]);

            $this->ledgerService->recordBonusWithTds($bonus, $tdsResult->grossAmount, $tdsResult->tdsAmount);

            $this->walletService->deposit(
                $wallet,
                (int) round($tdsResult->netAmount * 100),
                TransactionType::BONUS_CREDIT,
                $tdsResult->getDescription($bonusData['description']),
                $bonus
            );
            $this->debugFinancialInvariant($wallet);

            $referralService->updateReferrerMultiplier($referrer);
            Log::info("ORCHESTRATOR: Referral bonus awarded for referred user #{$referredUser->id} to referrer #{$referrer->id}");
        });
    }

    /**
     * Process withdrawal request with proper locking.
     */
    public function requestWithdrawal(User $user, int $amountPaise, array $bankDetails, ?string $idempotencyKey = null): \App\Models\Withdrawal
    {
        return DB::transaction(function () use ($user, $amountPaise, $bankDetails, $idempotencyKey) {
            $wallet = $this->acquireLockedWallet($user->id);
            $withdrawalService = app(\App\Services\WithdrawalService::class);
            $withdrawal = $withdrawalService->createWithdrawalRecordInternal($user, $amountPaise, $bankDetails, $idempotencyKey);

            if ($withdrawal->status === 'pending') {
                $this->walletService->lockFunds($wallet, $amountPaise, "Withdrawal Request #{$withdrawal->id}", $withdrawal);
                $this->debugFinancialInvariant($wallet);
                \App\Models\FundLock::create([
                    'user_id' => $user->id,
                    'lock_type' => 'withdrawal',
                    'lockable_type' => \App\Models\Withdrawal::class,
                    'lockable_id' => $withdrawal->id,
                    'amount_paise' => $amountPaise,
                    'status' => 'active',
                    'locked_at' => now(),
                    'locked_by' => auth()->id(),
                ]);
                $withdrawal->update(['funds_locked' => true, 'funds_locked_at' => now()]);
            }
            return $withdrawal;
        });
    }

    /**
     * Reject withdrawal with proper locking.
     */
    public function rejectWithdrawal(\App\Models\Withdrawal $withdrawal, User $admin, string $reason): \App\Models\Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $reason) {
            $withdrawal = \App\Models\Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();
            if (!in_array($withdrawal->status, ['pending', 'approved'])) throw new \Exception("Cannot reject a withdrawal in '{$withdrawal->status}' state.");

            $wallet = $this->acquireLockedWallet($withdrawal->user_id);
            $withdrawal->update(['status' => 'rejected', 'admin_id' => $admin->id, 'rejection_reason' => $reason]);

            $this->walletService->unlockFunds($wallet, $withdrawal->amount_paise, "Withdrawal Request #{$withdrawal->id} Rejected by Admin: {$reason}", $withdrawal);
            $this->debugFinancialInvariant($wallet);

            $lock = \App\Models\FundLock::where('lockable_type', \App\Models\Withdrawal::class)->where('lockable_id', $withdrawal->id)->where('status', 'active')->first();
            if ($lock) $lock->update(['status' => 'released', 'released_at' => now(), 'released_by' => $admin->id]);

            return $withdrawal;
        });
    }

    /**
     * Complete withdrawal with proper locking.
     */
    public function completeWithdrawal(\App\Models\Withdrawal $withdrawal, User $admin, string $utr): \App\Models\Withdrawal
    {
        return DB::transaction(function () use ($withdrawal, $admin, $utr) {
            $withdrawal = \App\Models\Withdrawal::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();
            if ($withdrawal->status !== 'approved') throw new \Exception("Only approved withdrawals can be completed.");

            $wallet = $this->acquireLockedWallet($withdrawal->user_id);
            $withdrawal->update(['status' => 'completed', 'admin_id' => $admin->id, 'utr_number' => $utr, 'completed_at' => now()]);

            $this->walletService->debitLockedFunds($wallet, $withdrawal->amount_paise, TransactionType::WITHDRAWAL, "Withdrawal Completed (UTR: {$utr})", $withdrawal);
            $this->debugFinancialInvariant($wallet);

            $lock = \App\Models\FundLock::where('lockable_type', \App\Models\Withdrawal::class)->where('lockable_id', $withdrawal->id)->where('status', 'active')->first();
            if ($lock) $lock->update(['status' => 'released', 'released_at' => now(), 'released_by' => $admin->id]);

            return $withdrawal;
        });
    }

    /**
     * Cancel withdrawal with proper locking.
     */
    public function cancelWithdrawal(User $user, \App\Models\Withdrawal $withdrawal): void
    {
        DB::transaction(function () use ($user, $withdrawal) {
            $withdrawal = \App\Models\Withdrawal::where('id', $withdrawal->id)->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            if ($withdrawal->status !== 'pending') throw new \Exception("Only pending withdrawals can be cancelled.");

            $wallet = $this->acquireLockedWallet($user->id);
            $withdrawal->update(['status' => 'cancelled']);

            $this->walletService->unlockFunds($wallet, $withdrawal->amount_paise, "Withdrawal Request #{$withdrawal->id} cancelled by user", $withdrawal);
            $this->debugFinancialInvariant($wallet);

            $lock = \App\Models\FundLock::where('lockable_type', \App\Models\Withdrawal::class)->where('lockable_id', $withdrawal->id)->where('status', 'active')->first();
            if ($lock) $lock->update(['status' => 'released', 'released_at' => now(), 'released_by' => $user->id]);
        });
    }

    /**
     * Credit user wallet with proper locking.
     */
    public function creditUserWallet(User $user, int $amountPaise, TransactionType $type, string $description, $reference = null): void
    {
        DB::transaction(function () use ($user, $amountPaise, $type, $description, $reference) {
            $wallet = $this->acquireLockedWallet($user->id);
            $this->walletService->deposit($wallet, $amountPaise, $type, $description, $reference);
            $this->debugFinancialInvariant($wallet);
            Log::info("ORCHESTRATOR: Credited user #{$user->id} with {$amountPaise} paise. Type: {$type->value}");
        });
    }

    /**
     * Debit user wallet with proper locking.
     */
    public function debitUserWallet(User $user, int $amountPaise, TransactionType $type, string $description, $reference = null, bool $allowOverdraft = false): void
    {
        DB::transaction(function () use ($user, $amountPaise, $type, $description, $reference, $allowOverdraft) {
            $wallet = $this->acquireLockedWallet($user->id);
            $this->walletService->withdraw($wallet, $amountPaise, $type, $description, $reference, false, $allowOverdraft);
            $this->debugFinancialInvariant($wallet);
            Log::info("ORCHESTRATOR: Debited user #{$user->id} for {$amountPaise} paise. Type: {$type->value}");
        });
    }

    public function allocateInvestmentShares($investment, $company, User $user, $adminLedgerEntryId = null): array
    {
        return $this->allocationService->allocateForInvestment($investment, $company, $user, $adminLedgerEntryId);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // V-ORCHESTRATION-2026: HELPER METHODS
    // ═══════════════════════════════════════════════════════════════════════════

    private function acquireLockedWallet(int $userId): Wallet
    {
        $wallet = Wallet::firstOrCreate(['user_id' => $userId], ['balance_paise' => 0, 'locked_balance_paise' => 0]);
        return Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();
    }
    private function debugFinancialInvariant(\App\Models\Wallet $wallet): void
    {
        $walletTotal = \App\Models\Wallet::sum('balance_paise');

        $account = \App\Models\LedgerAccount::byCode(
            \App\Models\LedgerAccount::CODE_USER_WALLET_LIABILITY
        );

        $ledgerTotal = (int) round($account->balance * 100);

        if ($walletTotal !== $ledgerTotal) {
            throw new \RuntimeException(
                "FINANCIAL INVARIANT BROKEN: ".
                "WalletTotal={$walletTotal} LedgerTotal={$ledgerTotal}"
            );
        }
    }

    private function requiresAllocation(Payment $payment, Subscription $subscription): bool
    {
        if (!$subscription) return false;
        if (property_exists($subscription, 'auto_allocate') && $subscription->auto_allocate === false) return false;
        $plan = $subscription->plan;
        return $plan ? (bool) ($plan->auto_allocate_shares ?? false) : false;
    }

    private function acquireInventoryLocks(Subscription $subscription): array
    {
        $productId = $subscription->product_id ?? null;
        if (!$productId) return ['product' => null, 'batches' => collect()];
        $product = Product::where('id', $productId)->lockForUpdate()->first();
        if (!$product) return ['product' => null, 'batches' => collect()];
        $batches = BulkPurchase::where('product_id', $product->id)->where('value_remaining', '>', 0)->orderBy('id', 'asc')->lockForUpdate()->get();
        return ['product' => $product, 'batches' => $batches];
    }

    private function isFirstPayment(Subscription $subscription): bool
    {
        return $subscription->payments()->where('status', Payment::STATUS_PAID)->whereNotNull('fulfilled_at')->count() === 0;
    }

    private function processReferralBonus(Payment $payment, Subscription $subscription, Wallet $userWallet): void
    {
        $user = $payment->user;
        $referral = Referral::where('referred_id', $user->id)->where('status', 'pending')->first();
        if (!$referral) return;

        $userIds = collect([$user->id, $referral->referrer_id])->sort()->values()->all();
        $wallets = [];
        foreach ($userIds as $userId) {
            $wallets[$userId] = ($userId === $user->id) ? $userWallet : $this->acquireLockedWallet($userId);
        }

        $awardedPaise = $this->bonusService->awardReferralBonus($payment, [], $wallets[$referral->referrer_id]);
        if ($awardedPaise > 0) {
            $referralBonus = \App\Models\BonusTransaction::where('payment_id', $payment->id)
                ->where('user_id', $referral->referrer_id)
                ->where('type', 'referral_bonus')
                ->orderByDesc('id')
                ->first();

            if ($referralBonus) {
                $this->walletService->deposit(
                    $wallets[$referral->referrer_id],
                    (int) round(($referralBonus->amount - ($referralBonus->tds_deducted ?? 0)) * 100),
                    TransactionType::BONUS_CREDIT,
                    $referralBonus->description,
                    $referralBonus
                );
                $this->debugFinancialInvariant($wallets[$referral->referrer_id]);
            }
        }
        Log::info("Referral bonus processed for Payment #{$payment->id}, Referrer #{$referral->referrer_id}");
    }

    private function reverseBonuses(Payment $payment, Wallet $wallet, string $reason): array
    {
        $bonuses = $payment->bonuses()->where('type', '!=', 'reversal')->whereDoesntHave('reversal')->get();
        if ($bonuses->isEmpty()) return ['reversed_bonuses' => [], 'total_debited_paise' => 0];

        $reversedBonuses = [];
        $totalNetToRecoverPaise = 0;
        foreach ($bonuses as $bonus) {
            $netAmount = $bonus->amount - ($bonus->tds_deducted ?? 0);
            $totalNetToRecoverPaise += (int) round($netAmount * 100);
            $reversalBonus = $bonus->reverse($reason);
            $reversedBonuses[] = ['original_id' => $bonus->id, 'reversal_id' => $reversalBonus->id, 'net_amount' => $netAmount];
        }

        if ($totalNetToRecoverPaise > 0) {
            $actualDebit = min($wallet->balance_paise, $totalNetToRecoverPaise);
            if ($actualDebit > 0) {
                $this->walletService->withdraw($wallet, $actualDebit, TransactionType::CHARGEBACK, "Bonus recovery for Payment #{$payment->id}: {$reason}", $payment, false, true);
                $this->debugFinancialInvariant($wallet);
            }
        }
        return ['reversed_bonuses' => $reversedBonuses, 'total_debited_paise' => $totalNetToRecoverPaise];
    }

    private function reverseBonusesWithReconciliation(Payment $payment, Wallet $wallet, string $reason): array
    {
        return $this->reverseBonuses($payment, $wallet, $reason);
    }

    public function processSuccessfulPayment(Payment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $payment = Payment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->fulfilled_at !== null || $payment->status !== Payment::STATUS_PAID) return;

            $subscription = Subscription::where('id', $payment->subscription_id)->lockForUpdate()->firstOrFail();
            $wallet = $this->acquireLockedWallet($payment->user_id);

            $lockedBatches = null;
            if ($this->requiresAllocation($payment, $subscription)) {
                $inventoryLocks = $this->acquireInventoryLocks($subscription);
                $lockedBatches = $inventoryLocks['batches'];
            }

            $amountPaise = $payment->getAmountPaiseStrict();
            $this->walletService->deposit($wallet, $amountPaise, TransactionType::DEPOSIT, "Payment received for SIP installment #{$payment->id}", $payment);
            $this->debugFinancialInvariant($wallet);

            if ($lockedBatches !== null && $lockedBatches->isNotEmpty()) {
                $allocationResult = $this->allocationService->allocateSharesLegacy($payment, $lockedBatches, (float) $payment->amount);
                if (($allocationResult['refund_due'] ?? 0) > 0) {
                    $this->walletService->deposit($wallet, (int) round($allocationResult['refund_due'] * 100), TransactionType::REFUND, "Refund for fractional remainder (Payment #{$payment->id})", $payment);
                    $this->debugFinancialInvariant($wallet);
                }
            }

            $totalBonusPaise = $this->bonusService->calculateAndAwardBonuses($payment, $subscription, $wallet);

            if ($totalBonusPaise > 0) {
                $bonusTransaction = \App\Models\BonusTransaction::where('payment_id', $payment->id)
                    ->where('user_id', $payment->user_id)
                    ->latest()
                    ->first();

                if ($bonusTransaction) {
                    $this->walletService->deposit(
                        $wallet,
                        $totalBonusPaise,
                        TransactionType::BONUS_CREDIT,
                        $bonusTransaction->description,
                        $bonusTransaction
                    );
                    $this->debugFinancialInvariant($wallet);
                }
            }

            if ($this->isFirstPayment($subscription)) $this->processReferralBonus($payment, $subscription, $wallet);

            $payment->update(['fulfilled_at' => now()]);
            Log::channel('financial_contract')->info('PAYMENT LIFECYCLE COMPLETE', ['payment_id' => $payment->id, 'user_id' => $payment->user_id, 'amount_paise' => $amountPaise, 'bonus_awarded_paise' => $totalBonusPaise]);
        });

        $payment->refresh();
        if ($payment->fulfilled_at !== null) {
            SendPaymentConfirmationEmailJob::dispatch($payment);
            GenerateLuckyDrawEntryJob::dispatch($payment);
        }
    }

    public function verifyAdminSolvency(): array { return $this->adminLedger->calculateSolvency(); }
    public function reconcile(): array { return app(\App\Services\Orchestration\ReconciliationService::class)->executeFullReconciliation(); }
}
