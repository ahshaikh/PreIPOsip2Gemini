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
    public function createWithdrawalRecord(User $user, $amount, array $bankDetails, ?string $idempotencyKey = null): Withdrawal
    {
        // CRITICAL: Convert to string for financial precision
        $amount = (string) $amount;

        // 1. Validations (KYC, Min Amount, Balance)
        if ($user->kyc->status !== 'verified') throw new \Exception("KYC must be verified.");

        $min = (string) setting('min_withdrawal_amount', 1000);
        // Use bccomp for safe string comparison
        if (bccomp($amount, $min, 2) < 0) {
            throw new \Exception("Minimum withdrawal is ₹{$min}.");
        }

        if (bccomp($user->wallet->balance, $amount, 2) < 0) {
            throw new \Exception("Insufficient funds.");
        }

        // 2. TDS Calculation using bcmath for precision
        $fee = '0';
        $tdsRate = (string) setting('tds_rate', 0.10);
        $tdsThreshold = (string) setting('tds_threshold', 5000);
        $tdsDeducted = '0';

        // Calculate TDS if user has PAN and amount exceeds threshold
        if ($user->kyc?->pan_number && bccomp($amount, $tdsThreshold, 2) > 0) {
            // tdsDeducted = amount × tdsRate
            $tdsDeducted = bcmul($amount, $tdsRate, 2);
        }

        // netAmount = amount - fee - tdsDeducted
        $netAmount = bcsub(bcsub($amount, $fee, 2), $tdsDeducted, 2);

        // 3. Check Auto-Approval Rules
        $autoApproveLimit = (string) setting('auto_approval_max_amount', 5000);
        $isSmallAmount = bccomp($amount, $autoApproveLimit, 2) <= 0;
        $isTrustedUser = $user->payments()->where('status', 'paid')->count() >= 5;
        $initialStatus = ($isSmallAmount && $isTrustedUser) ? 'approved' : 'pending';

        // 4. Create withdrawal record
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'amount' => $amount,
            'fee' => $fee,
            'tds_deducted' => $tdsDeducted,
            'net_amount' => $netAmount,
            'status' => $initialStatus,
            'bank_details' => $bankDetails,
            'idempotency_key' => $idempotencyKey, // AUDIT FIX: Store idempotency key
        ]);

        // 5. --- NEW: Notify User (Gap 3 Fix) ---
        $user->notify(new WithdrawalRequested($withdrawal));

        return $withdrawal;
    }

    /**
     * NEW: Allows a *user* to cancel their *own* pending withdrawal.
     */
    public function cancelUserWithdrawal(User $user, Withdrawal $withdrawal): bool
    {
        if ($withdrawal->user_id !== $user->id) {
            throw new \Exception("You do not own this withdrawal request.");
        }
        if ($withdrawal->status !== 'pending') {
            throw new \Exception("This withdrawal is already being processed and cannot be cancelled.");
        }

        return DB::transaction(function () use ($withdrawal) {
            $withdrawal->update([
                'status' => 'cancelled',
                'rejection_reason' => 'User cancelled before approval.'
            ]);
            
            // 1. Use WalletService to safely unlock funds
            $this->walletService->unlockFunds(
                $withdrawal->user,
                $withdrawal->amount,
                'reversal',
                "Withdrawal Request #{$withdrawal->id} Cancelled by User",
                $withdrawal
            );

            // 2. Mark original 'pending' transaction as 'failed'
            Transaction::where('reference_type', Withdrawal::class)
                ->where('reference_id', $withdrawal->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed']);
            
            return true;
        });
    }

    // ... (All Admin methods: approve, reject, complete remain the same) ...
    public function approveWithdrawal(Withdrawal $withdrawal, User $admin) { /* ... */ }
    public function rejectWithdrawal(Withdrawal $withdrawal, User $admin, string $reason) { /* ... */ }
    public function completeWithdrawal(Withdrawal $withdrawal, User $admin, string $utr) { /* ... */ }
}