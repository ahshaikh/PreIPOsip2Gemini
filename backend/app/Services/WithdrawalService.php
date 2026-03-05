<?php
// V-FINAL-1730-359 (Created) | V-FINAL-1730-460 (WalletService Refactor) | V-FINAL-1730-499 (TDS Logic)
// V-FINAL-1730-582 (User Cancel Logic) | V-AUDIT-MODULE3-005 (Fixed float precision and added idempotency)
// V-MONETARY-INTEGRITY-2026: Removed redundant manual transaction creation (delegated to WalletService)

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\FundLock;
use App\Events\WithdrawalApproved;
use App\Services\WalletService;
use App\Services\DoubleEntryLedgerService;
use App\Notifications\WithdrawalRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalService
{
    protected $walletService;
    protected $ledgerService;

    public function __construct(WalletService $walletService, DoubleEntryLedgerService $ledgerService)
    {
        $this->walletService = $walletService;
        $this->ledgerService = $ledgerService;
    }
    
    /**
     * Rename createWithdrawalRecord to requestWithdrawal for test compatibility.
     * V-ORCHESTRATION-2026: Now routes through FinancialOrchestrator.
     */
    public function requestWithdrawal(User $user, $amount, array $bankDetails, ?string $idempotencyKey = null): Withdrawal
    {
        $amountPaise = (int) round((float)$amount * 100);
        return app(FinancialOrchestrator::class)->requestWithdrawal($user, $amountPaise, $bankDetails, $idempotencyKey);
    }

    /**
     * Creates the Withdrawal Record and calculates TDS.
     * V-ORCHESTRATION-2026: Mutation-free (ledger only, no wallet).
     * Called by FinancialOrchestrator within a transaction.
     */
    public function createWithdrawalRecordInternal(User $user, int $amountPaise, array $bankDetails, ?string $idempotencyKey = null): Withdrawal
    {
        // 1. Validations
        if ($user->kyc->status !== 'verified') throw new \Exception("KYC must be verified.");

        $minPaise = (int) setting('min_withdrawal_amount', 1000) * 100;
        if ($amountPaise < $minPaise) {
            throw new \Exception("Minimum withdrawal amount is ₹" . ($minPaise / 100));
        }

        if ($user->wallet->balance_paise < $amountPaise) {
            throw new \Exception("Insufficient wallet balance.");
        }

        // 2. Fees & TDS Calculation
        $tdsRate = (float) setting('tds_rate', 5);
        $tdsDeductedPaise = (int) round($amountPaise * ($tdsRate / 100));
        $netAmountPaise = $amountPaise - $tdsDeductedPaise;

        // 3. Auto-Approval Rules
        $autoApproveLimitPaise = (int) setting('auto_approval_max_amount', 5000) * 100;
        $isSmallAmount = $amountPaise <= $autoApproveLimitPaise;
        $isTrustedUser = $user->payments()->where('status', 'paid')->count() >= 5;
        $initialStatus = ($isSmallAmount && $isTrustedUser) ? 'approved' : 'pending';

        // 4. Create withdrawal record
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'amount_paise' => $amountPaise,
            'fee_paise' => 0,
            'tds_deducted_paise' => $tdsDeductedPaise,
            'net_amount_paise' => $netAmountPaise,
            'status' => $initialStatus,
            'bank_details' => $bankDetails,
            'idempotency_key' => $idempotencyKey,
        ]);

        $user->notify(new WithdrawalRequested($withdrawal));

        return $withdrawal;
    }

    /**
     * Admin approves the withdrawal.
     */
    public function approveWithdrawal(Withdrawal $withdrawal, User $admin)
    {
        if ($withdrawal->status !== 'pending') {
            throw new \Exception("Can only approve pending withdrawals.");
        }

        $withdrawal->update([
            'status' => 'approved',
            'admin_id' => $admin->id
        ]);

        event(new WithdrawalApproved($withdrawal));
        
        return $withdrawal;
    }

    /**
     * Admin rejects the withdrawal.
     * V-ORCHESTRATION-2026: Now routes through FinancialOrchestrator.
     */
    public function rejectWithdrawal(Withdrawal $withdrawal, User $admin, string $reason)
    {
        return app(FinancialOrchestrator::class)->rejectWithdrawal($withdrawal, $admin, $reason);
    }

    /**
     * Admin marks withdrawal as completed after bank transfer.
     * V-ORCHESTRATION-2026: Now routes through FinancialOrchestrator.
     */
    public function completeWithdrawal(Withdrawal $withdrawal, User $admin, string $utr)
    {
        return app(FinancialOrchestrator::class)->completeWithdrawal($withdrawal, $admin, $utr);
    }

    /**
     * User cancels their own pending withdrawal request.
     * V-ORCHESTRATION-2026: Now routes through FinancialOrchestrator.
     */
    public function cancelUserWithdrawal(User $user, Withdrawal $withdrawal)
    {
        return app(FinancialOrchestrator::class)->cancelWithdrawal($user, $withdrawal);
    }
}
