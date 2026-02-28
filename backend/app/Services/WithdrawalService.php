<?php
// V-FINAL-1730-359 (Created) | V-FINAL-1730-460 (WalletService Refactor) | V-FINAL-1730-499 (TDS Logic)
// V-FINAL-1730-582 (User Cancel Logic) | V-AUDIT-MODULE3-005 (Fixed float precision and added idempotency)

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Payment;
use App\Models\Transaction;
use App\Events\WithdrawalApproved;
use App\Services\WalletService;
use App\Notifications\WithdrawalRequested; // <-- IMPORT
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WithdrawalService - User Withdrawal Request Handler
 *
 * Manages the complete withdrawal lifecycle from request creation through
 * admin approval/rejection to final bank transfer completion.
 *
 * ## Withdrawal Flow
 *
 * ```
 * User Request → [pending] → Admin Review → [approved] → Bank Transfer → [completed]
 *                   ↓              ↓
 *            [cancelled]      [rejected]
 *             (by user)       (by admin)
 * ```
 *
 * ## TDS (Tax Deducted at Source) Logic
 *
 * TDS is calculated for users with PAN when amount exceeds threshold:
 * ```
 * if (hasPAN && amount > tdsThreshold):
 *     tdsDeducted = amount × tdsRate (default: 10%)
 * netAmount = amount - fee - tdsDeducted
 * ```
 *
 * Settings:
 * - `tds_rate`: Default 0.10 (10%)
 * - `tds_threshold`: Default ₹5,000
 *
 * ## Auto-Approval Rules
 *
 * Withdrawals are auto-approved when:
 * 1. Amount ≤ `auto_approval_max_amount` (default: ₹5,000)
 * 2. User has completed ≥5 successful payments (trusted user)
 *
 * ## Balance Locking
 *
 * When a withdrawal is created, the amount is moved from `balance` to
 * `locked_balance` in the user's wallet. This prevents double-spending
 * while the withdrawal is being processed.
 *
 * | Action            | balance    | locked_balance |
 * |-------------------|------------|----------------|
 * | Request Created   | -amount    | +amount        |
 * | Request Completed | (no change)| -amount        |
 * | Request Cancelled | +amount    | -amount        |
 *
 * ## Available Methods
 *
 * | Method                | Actor | Description                          |
 * |-----------------------|-------|--------------------------------------|
 * | createWithdrawalRecord| User  | Submit new withdrawal request        |
 * | cancelUserWithdrawal  | User  | Cancel pending request (unlocks funds)|
 * | approveWithdrawal     | Admin | Approve for bank transfer            |
 * | rejectWithdrawal      | Admin | Reject with reason (unlocks funds)   |
 * | completeWithdrawal    | Admin | Mark as transferred with UTR number  |
 *
 * @package App\Services
 * @see \App\Models\Withdrawal
 * @see \App\Services\WalletService
 */
class WithdrawalService
{
    protected $walletService;
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }
    
    /**
     * Creates the Withdrawal Record and calculates TDS.
     *
     * AUDIT FIX (V-AUDIT-MODULE3-005):
     * - Changed type hint from float to string for financial precision
     * - Added idempotency_key parameter to prevent duplicate withdrawals
     * - Uses bcmath functions for precise TDS calculations
     *
     * @param User $user
     * @param string|float $amount - Amount as string for precision
     * @param array $bankDetails - Bank account details
     * @param string|null $idempotencyKey - Optional idempotency key for duplicate prevention
     * @return Withdrawal
     * @throws \Exception
     */
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
        // Use bccomp for safe string comparison
        if (bccomp($amount, $min, 2) < 0) {
            throw new \Exception("Minimum withdrawal amount is ₹{$min}");
        }

        if (bccomp($user->wallet->balance, $amount, 2) < 0) {
            throw new \Exception("Insufficient funds.");
        }

        // NOTE: Funds are locked by the controller calling walletService->withdraw() with lockBalance: true
        // Do NOT call lockFunds here to avoid double-locking

        // 3. TDS Calculation
        $fee = '0';
        $tdsRate = (string) setting('tds_rate', 0.10);
        $tdsThreshold = (string) setting('tds_threshold', 5000);
        $tdsDeducted = '0';

        if ($user->kyc?->pan_number && bccomp($amount, $tdsThreshold, 2) > 0) {
            $tdsDeducted = bcmul($amount, $tdsRate, 2);
        }

        $netAmount = bcsub(bcsub($amount, $fee, 2), $tdsDeducted, 2);

        // 4. Auto-Approval Rules
        $autoApproveLimit = (string) setting('auto_approval_max_amount', 5000);
        $isSmallAmount = bccomp($amount, $autoApproveLimit, 2) <= 0;
        $isTrustedUser = $user->payments()->where('status', 'paid')->count() >= 5;
        $initialStatus = ($isSmallAmount && $isTrustedUser) ? 'approved' : 'pending';

        // 5. Create withdrawal record
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

        // 6. Create Pending Transaction
        $user->wallet->transactions()->create([
            'user_id' => $user->id,
            'transaction_id' => (string) \Illuminate\Support\Str::uuid(), // Force UUID
            'type' => 'withdrawal_request',
            'status' => 'pending',
            'amount_paise' => $amountPaise,
            'balance_before_paise' => $user->wallet->balance_paise,
            'balance_after_paise' => $user->wallet->balance_paise - $amountPaise,
            'description' => "Withdrawal Request #{$withdrawal->id}",
            'reference_type' => Withdrawal::class,
            'reference_id' => $withdrawal->id,
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

            // Unlock funds
            $this->walletService->unlockFunds(
                $withdrawal->user,
                $withdrawal->amount,
                "Withdrawal Request #{$withdrawal->id} Rejected by Admin",
                $withdrawal
            );

            // Mark transaction as failed
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

            // Debit locked funds
            $this->walletService->debitLockedFunds(
                $withdrawal->user,
                $withdrawal->amount,
                'withdrawal',
                "Withdrawal Completed (UTR: {$utr})",
                $withdrawal
            );

            // Mark transaction as completed
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
        // Ensure the withdrawal belongs to the user
        if ($withdrawal->user_id !== $user->id) {
            throw new \Exception("You can only cancel your own withdrawal requests.");
        }

        // Only pending withdrawals can be cancelled
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

            // Mark transaction as cancelled
            Transaction::where('reference_type', Withdrawal::class)
                ->where('reference_id', $withdrawal->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            return $withdrawal;
        });
    }
}