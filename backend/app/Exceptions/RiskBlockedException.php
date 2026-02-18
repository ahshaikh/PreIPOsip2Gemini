<?php

// V-DISPUTE-RISK-2026-006: Risk Blocked Exception
// Thrown when a user with is_blocked=true attempts a financial operation.
// This is a SECURITY GATE - operations must NOT proceed.

namespace App\Exceptions;

use Exception;
use App\Models\User;

/**
 * RiskBlockedException
 *
 * Thrown when a risk-blocked user attempts to:
 * - Make a payment
 * - Create a new investment
 * - Allocate shares
 *
 * This exception enforces the Investment Guard - no financial operations
 * are allowed for users with is_blocked=true.
 *
 * RESPONSE PROTOCOL:
 * 1. REJECT the operation immediately
 * 2. Log to risk audit channel
 * 3. Return user-friendly error message
 * 4. NO ledger mutations may occur
 *
 * HTTP RESPONSE: 403 Forbidden
 * LOG CHANNEL: financial_contract (risk events)
 */
class RiskBlockedException extends Exception
{
    protected $code = 403;

    protected int $userId;
    protected int $riskScore;
    protected ?string $blockedReason;
    protected string $attemptedOperation;
    protected array $operationContext;

    public function __construct(
        User $user,
        string $attemptedOperation,
        array $operationContext = []
    ) {
        $this->userId = $user->id;
        $this->riskScore = $user->risk_score ?? 0;
        $this->blockedReason = $user->blocked_reason;
        $this->attemptedOperation = $attemptedOperation;
        $this->operationContext = $operationContext;

        parent::__construct(
            "[RISK BLOCKED] User #{$user->id} is blocked due to risk policy. " .
            "Attempted operation: {$attemptedOperation}. " .
            "Risk score: {$this->riskScore}. " .
            "Reason: " . ($this->blockedReason ?? 'No reason provided')
        );
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getRiskScore(): int
    {
        return $this->riskScore;
    }

    public function getBlockedReason(): ?string
    {
        return $this->blockedReason;
    }

    public function getAttemptedOperation(): string
    {
        return $this->attemptedOperation;
    }

    public function getOperationContext(): array
    {
        return $this->operationContext;
    }

    /**
     * Return structured context for audit logging.
     */
    public function reportContext(): array
    {
        return [
            'exception_type' => 'RiskBlockedException',
            'alert_level' => 'HIGH',
            'user_id' => $this->userId,
            'risk_score' => $this->riskScore,
            'blocked_reason' => $this->blockedReason,
            'attempted_operation' => $this->attemptedOperation,
            'operation_context' => $this->operationContext,
            'action_taken' => 'Operation REJECTED - user is risk-blocked',
            'action_required' => 'Review user risk profile if block should be lifted',
            'financial_impact' => 'No ledger mutation occurred',
        ];
    }

    /**
     * Get user-friendly error message for API response.
     */
    public function getUserMessage(): string
    {
        return 'Your account is currently restricted. Please contact support for assistance.';
    }
}
