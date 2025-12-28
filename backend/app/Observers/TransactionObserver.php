<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * TransactionObserver - Enforce Transaction Immutability
 *
 * [E.16]: Make all financial records append-only
 *
 * PROTOCOL:
 * - Transactions are IMMUTABLE after creation
 * - NO updates allowed (except internal reversal flags)
 * - NO deletes allowed
 * - Changes happen via reversal/compensating transactions
 *
 * EXCEPTIONS:
 * - System can mark transactions as reversed (is_reversed flag)
 * - System can link reversal transactions (reversed_by_transaction_id)
 * - These are append-only operations, not modifications
 */
class TransactionObserver
{
    /**
     * Handle the Transaction "updating" event.
     *
     * CRITICAL: Prevent ALL updates except reversal flags
     *
     * @param Transaction $transaction
     * @return bool
     */
    public function updating(Transaction $transaction): bool
    {
        // Get what's being changed
        $dirtyAttributes = $transaction->getDirty();

        // ALLOWED changes (reversal-only):
        $allowedChanges = [
            'is_reversed',
            'reversed_by_transaction_id',
            'reversed_at',
            'reversal_reason',
            'updated_at', // Laravel automatically updates this
        ];

        // Check if any disallowed attributes are being changed
        $disallowedChanges = array_diff(array_keys($dirtyAttributes), $allowedChanges);

        if (!empty($disallowedChanges)) {
            Log::error("IMMUTABILITY VIOLATION: Attempt to modify immutable transaction", [
                'transaction_id' => $transaction->id,
                'uuid' => $transaction->transaction_id,
                'disallowed_changes' => $disallowedChanges,
                'attempted_values' => array_intersect_key($dirtyAttributes, array_flip($disallowedChanges)),
                'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ]);

            throw new \RuntimeException(
                "IMMUTABILITY VIOLATION: Transactions are append-only. " .
                "Cannot update fields: " . implode(', ', $disallowedChanges) . ". " .
                "Use reversal transactions instead."
            );
        }

        // Allow reversal flag updates
        return true;
    }

    /**
     * Handle the Transaction "deleting" event.
     *
     * CRITICAL: Prevent ALL deletes
     *
     * @param Transaction $transaction
     * @return bool
     */
    public function deleting(Transaction $transaction): bool
    {
        Log::error("IMMUTABILITY VIOLATION: Attempt to delete immutable transaction", [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->transaction_id,
            'type' => $transaction->type,
            'amount_paise' => $transaction->amount_paise,
            'stack_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
        ]);

        throw new \RuntimeException(
            "IMMUTABILITY VIOLATION: Transactions are append-only. " .
            "Cannot delete transaction #{$transaction->id}. " .
            "Use reversal transactions instead."
        );
    }

    /**
     * Handle the Transaction "created" event.
     *
     * Log transaction creation for audit trail
     *
     * @param Transaction $transaction
     * @return void
     */
    public function created(Transaction $transaction): void
    {
        Log::info("TRANSACTION CREATED", [
            'transaction_id' => $transaction->id,
            'uuid' => $transaction->transaction_id,
            'type' => $transaction->type,
            'amount_paise' => $transaction->amount_paise,
            'user_id' => $transaction->user_id,
            'wallet_id' => $transaction->wallet_id,
            'balance_before_paise' => $transaction->balance_before_paise,
            'balance_after_paise' => $transaction->balance_after_paise,
            'is_reversed' => $transaction->is_reversed,
        ]);
    }
}
