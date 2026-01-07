<?php

namespace App\Observers;

use App\Models\Withdrawal;
use App\Models\FundLock;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * FIX 18: Withdrawal Observer
 *
 * Automatically locks funds when withdrawal is created
 * Automatically unlocks funds when withdrawal is approved/rejected/cancelled
 *
 * CRITICAL: Prevents users from spending funds reserved for pending withdrawals
 */
class WithdrawalObserver
{
    /**
     * Handle the Withdrawal "created" event.
     *
     * Lock funds immediately when withdrawal request is created
     */
    public function created(Withdrawal $withdrawal): void
    {
        if ($withdrawal->status === 'pending') {
            try {
                DB::beginTransaction();

                // Lock funds in user's wallet
                $lock = $withdrawal->user->wallet->lockFunds(
                    $withdrawal->amount,
                    'withdrawal',
                    $withdrawal,
                    [
                        'withdrawal_id' => $withdrawal->id,
                        'requested_amount' => $withdrawal->amount,
                        'fee' => $withdrawal->fee,
                        'net_amount' => $withdrawal->net_amount,
                    ]
                );

                // Mark withdrawal as having funds locked
                $withdrawal->update([
                    'funds_locked' => true,
                    'funds_locked_at' => now(),
                ]);

                DB::commit();

                Log::info('Withdrawal funds locked', [
                    'withdrawal_id' => $withdrawal->id,
                    'user_id' => $withdrawal->user_id,
                    'amount' => $withdrawal->amount,
                    'lock_id' => $lock->id,
                ]);

            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('Failed to lock funds for withdrawal', [
                    'withdrawal_id' => $withdrawal->id,
                    'user_id' => $withdrawal->user_id,
                    'error' => $e->getMessage(),
                ]);

                // Cancel withdrawal if fund locking fails
                $withdrawal->update([
                    'status' => 'cancelled',
                    'rejection_reason' => 'Insufficient funds or fund locking error',
                ]);

                throw $e;
            }
        }
    }

    /**
     * Handle the Withdrawal "updated" event.
     *
     * Unlock funds when status changes to approved/rejected/cancelled/completed
     */
    public function updated(Withdrawal $withdrawal): void
    {
        // Check if status changed
        if (!$withdrawal->wasChanged('status')) {
            return;
        }

        $oldStatus = $withdrawal->getOriginal('status');
        $newStatus = $withdrawal->status;

        // If status changed from pending to terminal state, unlock funds
        if ($oldStatus === 'pending' && in_array($newStatus, ['approved', 'rejected', 'cancelled', 'completed'])) {
            $this->unlockFunds($withdrawal, $newStatus);
        }

        // If approved withdrawal is completed, ensure funds are unlocked
        if ($oldStatus === 'approved' && $newStatus === 'completed') {
            $this->unlockFunds($withdrawal, $newStatus);
        }
    }

    /**
     * Unlock funds for withdrawal
     */
    protected function unlockFunds(Withdrawal $withdrawal, string $reason): void
    {
        try {
            // Find active lock for this withdrawal
            $lock = FundLock::where('lockable_type', Withdrawal::class)
                ->where('lockable_id', $withdrawal->id)
                ->where('status', 'active')
                ->first();

            if ($lock) {
                DB::beginTransaction();

                // Release the lock
                $lock->release(auth()->id(), "Withdrawal {$reason}");

                // Mark withdrawal as funds unlocked
                $withdrawal->update([
                    'funds_locked' => false,
                    'funds_unlocked_at' => now(),
                ]);

                DB::commit();

                Log::info('Withdrawal funds unlocked', [
                    'withdrawal_id' => $withdrawal->id,
                    'user_id' => $withdrawal->user_id,
                    'amount' => $withdrawal->amount,
                    'reason' => $reason,
                    'lock_id' => $lock->id,
                ]);
            } else {
                Log::warning('No active fund lock found for withdrawal', [
                    'withdrawal_id' => $withdrawal->id,
                    'status' => $reason,
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to unlock funds for withdrawal', [
                'withdrawal_id' => $withdrawal->id,
                'user_id' => $withdrawal->user_id,
                'error' => $e->getMessage(),
            ]);

            // Don't throw - withdrawal state change should proceed
            // Admin can manually unlock if needed
        }
    }

    /**
     * Handle the Withdrawal "deleted" event.
     *
     * Ensure funds are unlocked if withdrawal is deleted
     */
    public function deleted(Withdrawal $withdrawal): void
    {
        $this->unlockFunds($withdrawal, 'deleted');
    }
}
