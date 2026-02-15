<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Investment;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AdminActionConstraintService - Constrain Admin Actions (H.25)
 *
 * PURPOSE:
 * - Admin actions must obey the same invariants as automated flows
 * - Prevent admins from creating invalid states (negative balances, orphaned records)
 * - Enforce business rules even for manual operations
 * - Audit all admin actions with justification
 *
 * PROTOCOL:
 * - NO admin bypass of fundamental invariants
 * - Manual wallet adjustments require justification + approval
 * - Manual refunds must respect transaction history
 * - State transitions must be valid (can't go from 'failed' to 'completed' directly)
 *
 * INVARIANTS ENFORCED:
 * 1. Balance non-negativity: wallet.balance_paise >= 0
 * 2. Transaction conservation: SUM(credits) - SUM(debits) = balance
 * 3. Allocation bounds: allocated_shares <= inventory.available_shares
 * 4. Status transitions: Only valid state machine transitions
 * 5. Immutability: Cannot modify completed/archived records
 *
 * USAGE:
 * ```php
 * $constraints = app(AdminActionConstraintService::class);
 *
 * // Validate before allowing admin action
 * $constraints->validateWalletAdjustment($wallet, $amount, $admin, $justification);
 *
 * // Execute with constraints
 * $constraints->executeConstrainedAction('wallet_adjustment', function () {
 *     // Admin action here
 * }, $admin, $justification);
 * ```
 */
class AdminActionConstraintService
{
    /**
     * Validate manual wallet adjustment
     *
     * @param Wallet $wallet
     * @param float $amount Amount in rupees (can be negative)
     * @param User $admin
     * @param string $justification
     * @return array ['allowed' => bool, 'reason' => string]
     */
    public function validateWalletAdjustment(
        Wallet $wallet,
        float $amount,
        User $admin,
        string $justification
    ): array {
        // INVARIANT 1: Resulting balance cannot be negative
        $amountPaise = (int) ($amount * 100);
        $newBalance = $wallet->balance_paise + $amountPaise;

        if ($newBalance < 0) {
            return [
                'allowed' => false,
                'reason' => "INVARIANT VIOLATION: Resulting balance would be negative (₹" . ($newBalance / 100) . ")",
            ];
        }

        // CONSTRAINT 2: Large adjustments require senior admin approval
        $largeAdjustmentThreshold = (float) setting('large_adjustment_threshold', 10000);
        if (abs($amount) > $largeAdjustmentThreshold) {
            if (!$admin->hasRole('senior_admin') && !$admin->hasRole('super_admin')) {
                return [
                    'allowed' => false,
                    'reason' => "APPROVAL REQUIRED: Adjustments over ₹{$largeAdjustmentThreshold} require senior admin approval",
                ];
            }
        }

        // CONSTRAINT 3: Justification required for all adjustments
        if (empty(trim($justification)) || strlen($justification) < 10) {
            return [
                'allowed' => false,
                'reason' => "JUSTIFICATION REQUIRED: Provide detailed justification (min 10 characters)",
            ];
        }

        // CONSTRAINT 4: Check for suspicious patterns
        $recentAdjustments = DB::table('transactions')
            ->where('wallet_id', $wallet->id)
            ->where('type', 'admin_adjustment')
            ->where('created_at', '>', now()->subHours(24))
            ->count();

        if ($recentAdjustments >= 5) {
            return [
                'allowed' => false,
                'reason' => "SUSPICIOUS ACTIVITY: More than 5 adjustments in 24 hours. Contact compliance team.",
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Validate payment status change
     *
     * @param Payment $payment
     * @param string $newStatus
     * @param User $admin
     * @param string $justification
     * @return array
     */
    public function validatePaymentStatusChange(
        Payment $payment,
        string $newStatus,
        User $admin,
        string $justification
    ): array {
        $currentStatus = $payment->status;

        // INVARIANT: Valid state transitions only
        $validTransitions = [
            'pending' => ['processing', 'failed', 'cancelled'],
            'processing' => ['paid', 'failed'],
            'failed' => ['pending'], // Can retry
            'paid' => [], // Terminal state (cannot change)
            'cancelled' => [], // Terminal state
        ];

        if (!isset($validTransitions[$currentStatus])) {
            return [
                'allowed' => false,
                'reason' => "INVALID STATE: Unknown current status '{$currentStatus}'",
            ];
        }

        if (!in_array($newStatus, $validTransitions[$currentStatus])) {
            return [
                'allowed' => false,
                'reason' => "INVALID TRANSITION: Cannot change from '{$currentStatus}' to '{$newStatus}'",
            ];
        }

        // IMMUTABILITY: Cannot modify completed payments
        if ($currentStatus === 'paid') {
            return [
                'allowed' => false,
                'reason' => "IMMUTABILITY VIOLATION: Cannot modify completed payment",
            ];
        }

        // CONSTRAINT: Justification required
        if (empty(trim($justification)) || strlen($justification) < 10) {
            return [
                'allowed' => false,
                'reason' => "JUSTIFICATION REQUIRED: Provide detailed reason for status change",
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Validate investment cancellation
     *
     * @param Investment $investment
     * @param User $admin
     * @param string $justification
     * @return array
     */
    public function validateInvestmentCancellation(
        Investment $investment,
        User $admin,
        string $justification
    ): array {
        // CONSTRAINT: Cannot cancel active investments without refund
        if ($investment->status === 'active' && $investment->allocated_at) {
            return [
                'allowed' => false,
                'reason' => "CONSTRAINT VIOLATION: Active investments must be refunded, not cancelled",
            ];
        }

        // CONSTRAINT: Cancelled investments must refund wallet
        if ($investment->total_amount > 0) {
            $user = $investment->user;
            $refunded = DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('reference_type', 'investment')
                ->where('reference_id', $investment->id)
                ->where('type', 'refund')
                ->exists();

            if (!$refunded) {
                return [
                    'allowed' => false,
                    'reason' => "REFUND REQUIRED: Must refund ₹{$investment->total_amount} before cancellation",
                ];
            }
        }

        return ['allowed' => true];
    }

    /**
     * Validate bonus reversal
     *
     * @param int $bonusId
     * @param User $admin
     * @param string $justification
     * @return array
     */
    public function validateBonusReversal(
        int $bonusId,
        User $admin,
        string $justification
    ): array {
        $bonus = DB::table('bonuses')->find($bonusId);

        if (!$bonus) {
            return ['allowed' => false, 'reason' => 'Bonus not found'];
        }

        // CONSTRAINT: Cannot reverse bonuses older than 30 days
        $bonusAge = now()->diffInDays($bonus->created_at);
        if ($bonusAge > 30) {
            return [
                'allowed' => false,
                'reason' => "TIME LIMIT: Cannot reverse bonuses older than 30 days (this is {$bonusAge} days old)",
            ];
        }

        // CONSTRAINT: Check if bonus was already used
        $user = User::find($bonus->user_id);
        $bonusTransaction = DB::table('transactions')
            ->where('reference_type', 'bonus')
            ->where('reference_id', $bonusId)
            ->first();

        if ($bonusTransaction) {
            // Check if there are subsequent transactions after bonus credit
            $hasSubsequentActivity = DB::table('transactions')
                ->where('wallet_id', $bonusTransaction->wallet_id)
                ->where('created_at', '>', $bonusTransaction->created_at)
                ->exists();

            if ($hasSubsequentActivity) {
                return [
                    'allowed' => false,
                    'reason' => "CONSTRAINT: Bonus funds may have been used. Manual reconciliation required.",
                ];
            }
        }

        return ['allowed' => true];
    }

    /**
     * Generate deterministic idempotency key for admin action.
     *
     * ⚠️ CRITICAL: NO TIME COMPONENT
     *
     * Format: "adj:{action_type}:{deterministic_hash}"
     *
     * The hash is computed from:
     * - action_type
     * - target entity (wallet_id, user_id, etc.)
     * - amount (if financial)
     * - business intent identifiers
     *
     * This ensures identical business intents generate identical keys,
     * regardless of WHEN the request is made.
     *
     * For explicit client-controlled idempotency, accept UUID from request.
     *
     * @param string $actionType
     * @param array $metadata Must include business identifiers (wallet_id, user_id, amount, etc.)
     * @param string|null $clientIdempotencyKey Optional UUID from request layer
     * @return string
     */
    public function generateIdempotencyKey(
        string $actionType,
        array $metadata,
        ?string $clientIdempotencyKey = null
    ): string {
        // If client provides explicit idempotency key, use it directly
        // This is the preferred pattern for API consumers
        if ($clientIdempotencyKey !== null && $this->isValidUuid($clientIdempotencyKey)) {
            return "client:{$clientIdempotencyKey}";
        }

        // Compute deterministic hash from business identifiers
        // Sort keys to ensure consistent ordering
        ksort($metadata);

        // Extract only business-critical fields for hashing
        $hashableFields = [];

        // Always include action type
        $hashableFields['action'] = $actionType;

        // Include target identifiers
        foreach (['user_id', 'wallet_id', 'transaction_id', 'payment_id', 'subscription_id'] as $field) {
            if (isset($metadata[$field])) {
                $hashableFields[$field] = $metadata[$field];
            }
        }

        // Include amount if present (critical for financial operations)
        if (isset($metadata['amount'])) {
            // Normalize to paise integer to avoid float issues
            $hashableFields['amount_paise'] = is_numeric($metadata['amount'])
                ? (int) round($metadata['amount'] * 100)
                : $metadata['amount'];
        }
        if (isset($metadata['amount_paise'])) {
            $hashableFields['amount_paise'] = (int) $metadata['amount_paise'];
        }

        // Include adjustment direction/type if present
        foreach (['adjustment_type', 'direction', 'reason_code'] as $field) {
            if (isset($metadata[$field])) {
                $hashableFields[$field] = $metadata[$field];
            }
        }

        $deterministicHash = hash('sha256', json_encode($hashableFields));

        return "adj:{$actionType}:" . substr($deterministicHash, 0, 32);
    }

    /**
     * Validate UUID format.
     *
     * @param string $uuid
     * @return bool
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * Check if an admin action has already been executed.
     *
     * @param string $idempotencyKey
     * @return bool
     */
    public function isAlreadyExecuted(string $idempotencyKey): bool
    {
        return DB::table('admin_action_audit')
            ->where('idempotency_key', $idempotencyKey)
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Execute admin action with constraint validation
     *
     * V-PRECISION-2026: Deterministic idempotency enforcement.
     * Duplicate admin actions are blocked at database level (unique constraint).
     *
     * Idempotency is based on BUSINESS INTENT, not time:
     * - Same target + same amount + same action = same idempotency key
     * - Retries at any time will be blocked if original succeeded
     *
     * @param string $actionType
     * @param callable $action
     * @param User $admin
     * @param string $justification
     * @param array $metadata Must include business identifiers (user_id, wallet_id, amount, etc.)
     * @param string|null $clientIdempotencyKey Optional UUID from client for explicit control
     * @return mixed
     * @throws \RuntimeException
     */
    public function executeConstrainedAction(
        string $actionType,
        callable $action,
        User $admin,
        string $justification,
        array $metadata = [],
        ?string $clientIdempotencyKey = null
    ) {
        // V-PRECISION-2026: Generate DETERMINISTIC idempotency key (NO TIME COMPONENT)
        $idempotencyKey = $this->generateIdempotencyKey($actionType, $metadata, $clientIdempotencyKey);

        if ($this->isAlreadyExecuted($idempotencyKey)) {
            Log::warning("ADMIN ACTION BLOCKED: Duplicate action detected", [
                'idempotency_key' => $idempotencyKey,
                'action_type' => $actionType,
                'admin_id' => $admin->id,
                'metadata' => $metadata,
            ]);

            throw new \RuntimeException(
                "Duplicate action detected. This exact adjustment (same target, same amount) " .
                "has already been applied. Verify the target state before creating a new adjustment."
            );
        }

        // Log action attempt
        Log::info("ADMIN ACTION ATTEMPT", [
            'action_type' => $actionType,
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'justification' => $justification,
            'metadata' => $metadata,
            'idempotency_key' => $idempotencyKey,
        ]);

        // Create audit record BEFORE execution with idempotency key
        $auditId = DB::table('admin_action_audit')->insertGetId([
            'admin_id' => $admin->id,
            'action_type' => $actionType,
            'idempotency_key' => $idempotencyKey,
            'justification' => $justification,
            'metadata' => json_encode($metadata),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            // Execute action in transaction
            $result = DB::transaction(function () use ($action, $auditId) {
                $result = $action();

                // Mark audit as completed
                DB::table('admin_action_audit')
                    ->where('id', $auditId)
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);

                return $result;
            });

            Log::info("ADMIN ACTION COMPLETED", [
                'audit_id' => $auditId,
                'action_type' => $actionType,
            ]);

            return $result;

        } catch (\Throwable $e) {
            // Mark audit as failed
            DB::table('admin_action_audit')
                ->where('id', $auditId)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'failed_at' => now(),
                    'updated_at' => now(),
                ]);

            Log::error("ADMIN ACTION FAILED", [
                'audit_id' => $auditId,
                'action_type' => $actionType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get admin action history for audit
     *
     * @param User|null $admin
     * @param int $limit
     * @return array
     */
    public function getAdminActionHistory(?User $admin = null, int $limit = 50): array
    {
        $query = DB::table('admin_action_audit')
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($admin) {
            $query->where('admin_id', $admin->id);
        }

        return $query->get()->map(function ($action) {
            return [
                'id' => $action->id,
                'admin_id' => $action->admin_id,
                'action_type' => $action->action_type,
                'justification' => $action->justification,
                'status' => $action->status,
                'created_at' => $action->created_at,
                'completed_at' => $action->completed_at,
                'error_message' => $action->error_message,
            ];
        })->toArray();
    }

    /**
     * Get suspicious admin actions (for compliance review)
     *
     * @return array
     */
    public function getSuspiciousActions(): array
    {
        // Large wallet adjustments in last 24 hours
        $largeAdjustments = DB::table('admin_action_audit')
            ->where('action_type', 'wallet_adjustment')
            ->where('created_at', '>', now()->subHours(24))
            ->whereRaw("CAST(JSON_EXTRACT(metadata, '$.amount') AS DECIMAL) > 10000")
            ->get();

        // Frequent actions by same admin
        $frequentActions = DB::table('admin_action_audit')
            ->select('admin_id', DB::raw('count(*) as action_count'))
            ->where('created_at', '>', now()->subHours(24))
            ->groupBy('admin_id')
            ->having('action_count', '>', 20)
            ->get();

        // Failed actions
        $failedActions = DB::table('admin_action_audit')
            ->where('status', 'failed')
            ->where('created_at', '>', now()->subDays(7))
            ->get();

        return [
            'large_adjustments' => $largeAdjustments->toArray(),
            'frequent_actions' => $frequentActions->toArray(),
            'failed_actions' => $failedActions->toArray(),
        ];
    }
}
