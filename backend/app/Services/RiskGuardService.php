<?php

/**
 * V-DISPUTE-RISK-2026-006: Risk Guard Service
 *
 * Central guard for enforcing risk-based access control on financial operations.
 *
 * USAGE:
 * Call assertUserCanInvest() or assertUserCanTransact() before any financial operation.
 * These methods will throw RiskBlockedException if the user is blocked.
 *
 * ENFORCEMENT POINTS:
 * - AllocationService: Before share allocation
 * - WalletService: Before any debit for share purchase
 * - PaymentController: Before initiating payment
 * - SubscriptionController: Before creating/renewing subscriptions
 */

namespace App\Services;

use App\Models\User;
use App\Exceptions\RiskBlockedException;
use Illuminate\Support\Facades\Log;

class RiskGuardService
{
    /**
     * Assert that a user can make investments.
     *
     * This is the primary guard for investment-related operations:
     * - New share purchases
     * - SIP payments
     * - Bonus share allocations
     *
     * @param User $user The user attempting the operation
     * @param string $operation Description of the attempted operation
     * @param array $context Additional context for logging
     * @return void
     * @throws RiskBlockedException If user is blocked
     */
    public function assertUserCanInvest(User $user, string $operation = 'investment', array $context = []): void
    {
        // Refresh to get latest blocked status
        $user->refresh();

        if ($user->is_blocked) {
            Log::channel(config('risk.audit.log_channel', 'financial_contract'))->warning('INVESTMENT BLOCKED: User is risk-blocked', [
                'user_id' => $user->id,
                'email' => $user->email,
                'risk_score' => $user->risk_score,
                'blocked_reason' => $user->blocked_reason,
                'attempted_operation' => $operation,
                'context' => $context,
            ]);

            throw new RiskBlockedException($user, $operation, $context);
        }

        // Log successful pass (debug level)
        Log::debug('Risk guard passed for investment', [
            'user_id' => $user->id,
            'operation' => $operation,
            'risk_score' => $user->risk_score,
        ]);
    }

    /**
     * Assert that a user can perform general transactions.
     *
     * This is for non-investment financial operations:
     * - Withdrawals
     * - Refund requests
     *
     * Note: Blocked users CAN still receive refunds/reversals.
     * They just can't initiate new investments.
     *
     * @param User $user The user attempting the operation
     * @param string $operation Description of the attempted operation
     * @param array $context Additional context for logging
     * @return void
     * @throws RiskBlockedException If user is blocked and operation requires it
     */
    public function assertUserCanTransact(User $user, string $operation, array $context = []): void
    {
        // For now, use same logic as investment guard
        // This can be expanded for different transaction types
        $this->assertUserCanInvest($user, $operation, $context);
    }

    /**
     * Check if a user is blocked (without throwing).
     *
     * Use this for conditional logic where you need to know status
     * but don't want to throw an exception.
     *
     * @param User $user
     * @return bool True if user is blocked
     */
    public function isUserBlocked(User $user): bool
    {
        $user->refresh();
        return (bool) $user->is_blocked;
    }

    /**
     * Check if a user can invest (without throwing).
     *
     * @param User $user
     * @return bool True if user can invest
     */
    public function canUserInvest(User $user): bool
    {
        return !$this->isUserBlocked($user);
    }

    /**
     * Get the blocking details for a user.
     *
     * @param User $user
     * @return array|null Null if not blocked, array with details if blocked
     */
    public function getBlockingDetails(User $user): ?array
    {
        $user->refresh();

        if (!$user->is_blocked) {
            return null;
        }

        return [
            'is_blocked' => true,
            'risk_score' => $user->risk_score,
            'blocked_reason' => $user->blocked_reason,
            'last_risk_update_at' => $user->last_risk_update_at?->toIso8601String(),
        ];
    }
}
