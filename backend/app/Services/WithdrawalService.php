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
use App\Events\WithdrawalApproved;
use App\Services\WalletService;
use App\Notifications\WithdrawalRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalService
{
    protected $walletService;
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }
    
    /**
     * Rename createWithdrawalRecord to requestWithdrawal for test compatibility.
     */
    public function requestWithdrawal(User $user, $amount, array $bankDetails, ?string $idempotencyKey = null): Withdrawal
    {
        return $this->createWithdrawalRecord($user, $amount, $bankDetails, $idempotencyKey);
    }

    /**
     * Creates the Withdrawal Record and calculates TDS.
     */
    public function createWithdrawalRecord(User $user, $amount, array $bankDetails, ?string $idempotencyKey = null): Withdrawal
    {
        // CRITICAL: Convert to string for financial precision
        $amount = (string) $amount;

        // 1. Validations (KYC, Min Amount, Balance)
        if ($user->kyc->status !== 'verified') throw new \Exception("KYC must be verified.");

        $min = (string) setting('min_withdrawal_amount', 1000);
        if (bccomp($amount, $min, 2) < 0) {
            throw new \Exception("Minimum withdrawal amount is ₹{$min}");
        }

        if (bccomp($user->wallet->balance, $amount, 2) < 0) {
            throw new \Exception("Insufficient funds.");
        }

        // Check available balance (not just total balance)
        $availableBalance = $user->wallet->available_balance;
        if (bccomp($availableBalance, $amount, 2) < 0) {
            throw new \Exception("Insufficient available funds. Some funds are locked.");
        }

        // 2. TDS Calculation
        $fee = '0';
        $tdsRate = (string) setting('tds_rate', 0.10);
        $tdsThreshold = (string) setting('tds_threshold', 5000);
        $tdsDeducted = '0';

        if ($user->kyc?->pan_number && bccomp($amount, $tdsThreshold, 2) > 0) {
            $tdsDeducted = bcmul($amount, $tdsRate, 2);
        }

        $netAmount = bcsub(bcsub($amount, $fee, 2), $tdsDeducted, 2);

        // 3. Auto-Approval Rules
        $autoApproveLimit = (string) setting('auto_approval_max_amount', 5000);
        $isSmallAmount = bccomp($amount, $autoApproveLimit, 2) <= 0;
        $isTrustedUser = $user->payments()->where('status', 'paid')->count() >= 5;
        $initialStatus = ($isSmallAmount && $isTrustedUser) ? 'approved' : 'pending';

        // 4. Create withdrawal record
        $amountPaise = (int) bcmul($amount, '100', 0);
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'amount_paise' => $amountPaise,
            'fee_paise' => 0,
            'tds_deducted_paise' => (int) bcmul($tdsDeducted, '100', 0),
            'net_amount_paise' => (int) bcmul($netAmount, '100', 0),
            'status' => $initialStatus,
            'bank_details' => $bankDetails,
            'idempotency_key' => $idempotencyKey,
        ]);

        // NOTE: Wallet locking and transaction creation are handled by the controller
        // via WalletService::withdraw(..., lockBalance: true).

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

        event(new \App\Events\WithdrawalApproved($withdrawal));
        
        return $withdrawal;
    }

    /**
     * Admin rejects the withdrawal.
     */
    public function rejectWithdrawal(Withdrawal $withdrawal, User $admin, string $reason)
    {
        if (!in_array($withdrawal->status, ['pending', 'approved'])) {
            throw new \Exception("Cannot reject a withdrawal in '{$withdrawal->status}' state.");
        }

        return DB::transaction(function () use ($withdrawal, $admin, $reason) {
            $withdrawal->update([
                'status' => 'rejected',
                'admin_id' => $admin->id,
                'rejection_reason' => $reason
            ]);

            // Unlock funds via WalletService
            $this->walletService->unlockFunds(
                $withdrawal->user,
                $withdrawal->amount,
                "Withdrawal Request #{$withdrawal->id} Rejected by Admin: {$reason}",
                $withdrawal
            );

            // Mark the PENDING transaction as failed
            Transaction::where('reference_type', Withdrawal::class)
                ->where('reference_id', $withdrawal->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed']);

            return $withdrawal;
        });
    }

    /**
     * Admin marks withdrawal as completed after bank transfer.
     */
    public function completeWithdrawal(Withdrawal $withdrawal, User $admin, string $utr)
    {
        if ($withdrawal->status !== 'approved') {
            throw new \Exception("Only approved withdrawals can be marked as completed.");
        }

        return DB::transaction(function () use ($withdrawal, $admin, $utr) {
            $withdrawal->update([
                'status' => 'completed',
                'admin_id' => $admin->id,
                'utr_number' => $utr
            ]);

            // Debit the ALREADY LOCKED funds via WalletService
            $this->walletService->debitLockedFunds(
                $withdrawal->user,
                $withdrawal->amount,
                \App\Enums\TransactionType::WITHDRAWAL,
                "Withdrawal Completed (UTR: {$utr})",
                $withdrawal
            );

            // Mark the PENDING transaction as completed
            Transaction::where('reference_type', Withdrawal::class)
                ->where('reference_id', $withdrawal->id)
                ->where('status', 'pending')
                ->update(['status' => 'completed']);

            return $withdrawal;
        });
    }

    /**
     * User cancels their own pending withdrawal request.
     */
    public function cancelUserWithdrawal(User $user, Withdrawal $withdrawal)
    {
        if ($withdrawal->user_id !== $user->id) {
            throw new \Exception("You can only cancel your own withdrawal requests.");
        }

        if ($withdrawal->status !== 'pending') {
            throw new \Exception("Only pending withdrawals can be cancelled. Current status: {$withdrawal->status}");
        }

        return DB::transaction(function () use ($withdrawal) {
            $withdrawal->update([
                'status' => 'cancelled',
                'cancelled_at' => now()
            ]);

            // Unlock funds and restore balance
            $this->walletService->unlockFunds(
                $withdrawal->user,
                $withdrawal->amount,
                "Withdrawal Request #{$withdrawal->id} Cancelled by User",
                $withdrawal
            );

            // Mark the PENDING transaction as cancelled
            Transaction::where('reference_type', Withdrawal::class)
                ->where('reference_id', $withdrawal->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            return $withdrawal;
        });
    }
}
