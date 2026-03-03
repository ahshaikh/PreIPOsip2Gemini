<?php

namespace App\Services;

use App\Models\Dispute;
use App\Models\DisputeSnapshot;
use App\Models\User;
use App\Models\Payment;
use App\Models\Investment;
use App\Models\Withdrawal;
use App\Models\BonusTransaction;
use App\Models\Allocation;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * DisputeSnapshotService - Creates immutable state snapshots at dispute filing
 *
 * At dispute filing, this service captures:
 * - Complete state of the disputed entity (payment, investment, etc.)
 * - User's wallet state
 * - Related transactions (ledger entries, bonuses)
 * - System configuration at that moment
 *
 * The snapshot is immutable (protected by database trigger) and hash-verified.
 *
 * @see App\Models\DisputeSnapshot
 */
class DisputeSnapshotService
{
    /**
     * Create a snapshot for a dispute at filing time.
     *
     * @throws \RuntimeException If snapshot already exists
     */
    public function captureAtFiling(Dispute $dispute, ?User $capturedBy = null): DisputeSnapshot
    {
        // Prevent duplicate snapshots
        if ($dispute->snapshot()->exists()) {
            throw new \RuntimeException(
                "Dispute #{$dispute->id} already has a snapshot. Snapshots are immutable."
            );
        }

        return $this->capture(
            $dispute,
            DisputeSnapshot::TRIGGER_DISPUTE_FILED,
            $capturedBy
        );
    }

    /**
     * Create a snapshot for admin request (additional snapshot capture).
     */
    public function captureForAdminRequest(Dispute $dispute, User $admin): DisputeSnapshot
    {
        return $this->capture(
            $dispute,
            DisputeSnapshot::TRIGGER_ADMIN_REQUEST,
            $admin
        );
    }

    /**
     * Create a snapshot when auto-escalation triggers.
     */
    public function captureAtAutoEscalation(Dispute $dispute): DisputeSnapshot
    {
        return $this->capture(
            $dispute,
            DisputeSnapshot::TRIGGER_AUTO_ESCALATION,
            null
        );
    }

    /**
     * Core snapshot capture logic.
     */
    private function capture(
        Dispute $dispute,
        string $trigger,
        ?User $capturedBy
    ): DisputeSnapshot {
        return DB::transaction(function () use ($dispute, $trigger, $capturedBy) {
            $snapshot = new DisputeSnapshot([
                'dispute_id' => $dispute->id,
                'disputable_snapshot' => $this->captureDisputableState($dispute),
                'wallet_snapshot' => $this->captureWalletState($dispute),
                'related_transactions_snapshot' => $this->captureRelatedTransactions($dispute),
                'system_state_snapshot' => $this->captureSystemState($dispute),
                'captured_by_user_id' => $capturedBy?->id,
                'capture_trigger' => $trigger,
            ]);

            // Hash is computed in model's creating hook
            $snapshot->save();

            Log::channel('financial_contract')->info('Dispute snapshot captured', [
                'dispute_id' => $dispute->id,
                'snapshot_id' => $snapshot->id,
                'trigger' => $trigger,
                'captured_by' => $capturedBy?->id,
                'integrity_hash' => $snapshot->integrity_hash,
            ]);

            return $snapshot;
        });
    }

    /**
     * Capture complete state of the disputed entity.
     */
    private function captureDisputableState(Dispute $dispute): array
    {
        if (!$dispute->disputable_type || !$dispute->disputable_id) {
            return ['type' => null, 'id' => null, 'data' => null];
        }

        $disputable = $dispute->disputable;
        if (!$disputable) {
            return [
                'type' => $dispute->disputable_type,
                'id' => $dispute->disputable_id,
                'data' => null,
                'error' => 'Entity not found',
            ];
        }

        $data = $this->extractEntityData($disputable);

        return [
            'type' => get_class($disputable),
            'id' => $disputable->getKey(),
            'data' => $data,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Extract relevant data from disputable entity.
     */
    private function extractEntityData($entity): array
    {
        // Common fields to always include
        $data = [
            'id' => $entity->id,
            'created_at' => $entity->created_at?->toIso8601String(),
            'updated_at' => $entity->updated_at?->toIso8601String(),
        ];

        // Entity-specific fields
        if ($entity instanceof Payment) {
            $data = array_merge($data, [
                'user_id' => $entity->user_id,
                'amount' => $entity->amount,
                'status' => $entity->status,
                'gateway_order_id' => $entity->gateway_order_id,
                'gateway_payment_id' => $entity->gateway_payment_id,
                'gateway' => $entity->gateway,
                'subscription_id' => $entity->subscription_id,
                'payment_method' => $entity->payment_method,
                'metadata' => $entity->metadata,
            ]);
        } elseif ($entity instanceof Investment) {
            $data = array_merge($data, [
                'user_id' => $entity->user_id,
                'product_id' => $entity->product_id,
                'payment_id' => $entity->payment_id,
                'value_allocated' => $entity->value_allocated,
                'units_allocated' => $entity->units_allocated,
                'price_per_unit' => $entity->price_per_unit,
                'is_reversed' => $entity->is_reversed,
                'status' => $entity->status ?? null,
            ]);
        } elseif ($entity instanceof Withdrawal) {
            $data = array_merge($data, [
                'user_id' => $entity->user_id,
                'amount' => $entity->amount,
                'status' => $entity->status,
                'bank_details' => $entity->bank_details,
                'admin_notes' => $entity->admin_notes,
                'processed_at' => $entity->processed_at?->toIso8601String(),
            ]);
        } elseif ($entity instanceof BonusTransaction) {
            $data = array_merge($data, [
                'user_id' => $entity->user_id,
                'amount' => $entity->amount,
                'type' => $entity->type,
                'payment_id' => $entity->payment_id,
                'description' => $entity->description,
            ]);
        } elseif ($entity instanceof Allocation) {
            $data = array_merge($data, [
                'user_id' => $entity->user_id,
                'product_id' => $entity->product_id,
                'bulk_purchase_id' => $entity->bulk_purchase_id,
                'units_allocated' => $entity->units_allocated,
                'value_allocated' => $entity->value_allocated,
            ]);
        }

        return $data;
    }

    /**
     * Capture user's wallet state at dispute time.
     */
    private function captureWalletState(Dispute $dispute): array
    {
        $user = $dispute->user;
        if (!$user) {
            return ['error' => 'No user associated with dispute'];
        }

        $wallet = $user->wallet;
        if (!$wallet) {
            return ['error' => 'User has no wallet'];
        }

        return [
            'wallet_id' => $wallet->id,
            'user_id' => $wallet->user_id,
            'balance_paise' => $wallet->balance_paise,
            'locked_balance_paise' => $wallet->locked_balance_paise,
            'balance' => $wallet->balance,
            'available_balance' => $wallet->available_balance,
            'locked_balance' => $wallet->locked_balance,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Capture related transactions (ledger entries, bonuses, etc.).
     */
    private function captureRelatedTransactions(Dispute $dispute): array
    {
        $transactions = [
            'ledger_entries' => [],
            'bonus_transactions' => [],
            'related_payments' => [],
        ];

        // If disputable is a Payment, get related ledger entries
        if ($dispute->disputable instanceof Payment) {
            $payment = $dispute->disputable;

            // Get ledger entries for this payment
            $ledgerEntries = LedgerEntry::where('reference_type', Payment::class)
                ->where('reference_id', $payment->id)
                ->with('lines')
                ->get();

            $transactions['ledger_entries'] = $ledgerEntries->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'type' => $entry->type,
                    'description' => $entry->description,
                    'total_amount' => $entry->total_amount,
                    'lines' => $entry->lines->map(function ($line) {
                        return [
                            'account_code' => $line->account_code,
                            'debit' => $line->debit,
                            'credit' => $line->credit,
                        ];
                    })->toArray(),
                ];
            })->toArray();

            // Get related bonus transactions
            $bonuses = BonusTransaction::where('payment_id', $payment->id)->get();
            $transactions['bonus_transactions'] = $bonuses->map(function ($bonus) {
                return [
                    'id' => $bonus->id,
                    'type' => $bonus->type,
                    'amount' => $bonus->amount,
                ];
            })->toArray();
        }

        // If disputable is an Investment, get the payment
        if ($dispute->disputable instanceof Investment) {
            $investment = $dispute->disputable;
            if ($investment->payment) {
                $transactions['related_payments'][] = [
                    'id' => $investment->payment->id,
                    'amount' => $investment->payment->amount,
                    'status' => $investment->payment->status,
                ];
            }
        }

        return $transactions;
    }

    /**
     * Capture relevant system state (settings, configs).
     */
    private function captureSystemState(Dispute $dispute): array
    {
        return [
            'platform_version' => config('app.version', '1.0.0'),
            'env' => config('app.env'),
            'withdrawal_settings' => [
                'min_withdrawal_amount' => setting('min_withdrawal_amount'),
                'max_withdrawal_amount' => setting('max_withdrawal_amount'),
            ],
            'dispute_settings' => [
                'auto_escalation_enabled' => setting('dispute_auto_escalation_enabled', true),
                'sla_hours_default' => setting('dispute_sla_hours_default', 48),
            ],
            'captured_at' => now()->toIso8601String(),
        ];
    }
}
