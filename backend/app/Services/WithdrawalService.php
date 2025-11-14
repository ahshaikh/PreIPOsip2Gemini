<?php
// V-FINAL-1730-359 (Created) | V-FINAL-1730-460 (WalletService Refactor)

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdrawal;
use App\Models\Payment;
use App\Models\Transaction;
use App\Events\WithdrawalApproved;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawalService
{
    // Inject WalletService
    protected $walletService;
    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }
    
    /**
     * Creates the Withdrawal Record.
     * Does NOT move money (WalletController does that now).
     */
    public function createWithdrawalRecord(User $user, float $amount, array $bankDetails): Withdrawal
    {
        if ($user->kyc->status !== 'verified') {
            throw new \Exception("KYC must be verified.");
        }
        $min = setting('min_withdrawal_amount', 1000);
        if ($amount < $min) {
            throw new \Exception("Minimum withdrawal is ₹{$min}.");
        }
        if ($user->wallet->balance < $amount) {
            throw new \Exception("Insufficient funds.");
        }

        $autoApproveLimit = setting('auto_approval_max_amount', 5000);
        $isSmallAmount = $amount <= $autoApproveLimit;
        $isTrustedUser = $user->payments()->where('status', 'paid')->count() >= 5;
        $initialStatus = ($isSmallAmount && $isTrustedUser) ? 'approved' : 'pending';

        return Withdrawal::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'amount' => $amount,
            'fee' => 0,
            'net_amount' => $amount,
            'status' => $initialStatus,
            'bank_details' => $bankDetails,
        ]);
    }

    public function approveWithdrawal(Withdrawal $withdrawal, User $admin)
    {
        if ($withdrawal->status !== 'pending') throw new \Exception("Not pending.");
        $withdrawal->update(['status' => 'approved', 'admin_id' => $admin->id]);
        event(new WithdrawalApproved($withdrawal));
        return $withdrawal;
    }

    /**
     * Rejects a withdrawal and unlocks funds using WalletService.
     */
    public function rejectWithdrawal(Withdrawal $withdrawal, User $admin, string $reason)
    {
        if ($withdrawal->status !== 'pending') throw new \Exception("Not pending.");

        DB::transaction(function () use ($withdrawal, $admin, $reason) {
            $withdrawal->update([
                'status' => 'rejected',
                'admin_id' => $admin->id,
                'rejection_reason' => $reason
            ]);
            
            // 1. Use WalletService to safely unlock funds
            $this->walletService->unlockFunds(
                $withdrawal->user,
                $withdrawal->amount,
                'reversal',
                "Withdrawal Rejected: {$reason}",
                $withdrawal
            );

            // 2. Mark original 'pending' transaction as 'failed'
            Transaction::where('reference_type', Withdrawal::class)
                ->where('reference_id', $withdrawal->id)
                ->where('status', 'pending')
                ->update(['status' => 'failed']);
        });
    }

    public function completeWithdrawal(Withdrawal $withdrawal, User $admin, string $utr)
    {
        if ($withdrawal->status !== 'approved') {
            throw new \Exception("Not approved.");
        }

        DB::transaction(function () use ($withdrawal, $admin, $utr) {
            $withdrawal->update([
                'status' => 'completed',
                'admin_id' => $admin->id,
                'utr_number' => $utr
            ]);
            
            // 1. Deduct locked balance
            $withdrawal->wallet->decrement('locked_balance', $withdrawal->amount);

            // 2. Mark original transaction as completed
            Transaction::where('reference_type', Withdrawal::class)
                ->where('reference_id', $withdrawal->id)
                ->where('status', 'pending')
                ->update(['status' => 'completed']);
        });
    }
}