<?php
// V-FINAL-1730-359 (Created) | V-FINAL-1730-460 (WalletService Refactor) | V-FINAL-1730-499 (TDS Logic) | V-FINAL-1730-582 (User Cancel Logic)

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

class WithdrawalService
{
    protected $walletService;
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }
    
    /**
     * Creates the Withdrawal Record and calculates TDS.
     */
    public function createWithdrawalRecord(User $user, float $amount, array $bankDetails): Withdrawal
    {
        // 1. Validations (KYC, Min Amount, Balance)
        if ($user->kyc->status !== 'verified') throw new \Exception("KYC must be verified.");
        $min = setting('min_withdrawal_amount', 1000);
        if ($amount < $min) throw new \Exception("Minimum withdrawal is ₹{$min}.");
        if ($user->wallet->balance < $amount) throw new \Exception("Insufficient funds.");

        // 2. TDS Calculation
        $fee = 0;
        $tdsRate = (float) setting('tds_rate', 0.10);
        $tdsThreshold = (float) setting('tds_threshold', 5000);
        $tdsDeducted = 0;
        if ($user->kyc?->pan_number && $amount > $tdsThreshold) {
            $tdsDeducted = $amount * $tdsRate;
        }
        $netAmount = $amount - $fee - $tdsDeducted;

        // 3. Check Auto-Approval Rules
        $autoApproveLimit = setting('auto_approval_max_amount', 5000);
        $isSmallAmount = $amount <= $autoApproveLimit;
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