<?php
// V-CONTRACT-HARDENING-FINAL: Contract integrity exception
// Thrown when subscription snapshot integrity verification fails.
// This is a CRITICAL FAILURE - financial calculations must halt.

namespace App\Exceptions;

use Exception;

/**
 * ContractIntegrityException
 *
 * Thrown when a subscription's bonus contract snapshot fails integrity verification.
 * This indicates potential tampering or data corruption.
 *
 * RESPONSE PROTOCOL:
 * 1. Halt all financial calculations for this subscription
 * 2. Log to financial_contract audit channel
 * 3. Alert platform administrators
 * 4. Do NOT proceed with any bonus awards
 *
 * ALERT LEVEL: CRITICAL
 * LOG CHANNEL: financial_contract
 */
class ContractIntegrityException extends Exception
{
    protected $code = 500;

    protected int $subscriptionId;
    protected string $expectedHash;
    protected string $actualHash;

    public function __construct(int $subscriptionId, string $expectedHash, string $actualHash)
    {
        $this->subscriptionId = $subscriptionId;
        $this->expectedHash = $expectedHash;
        $this->actualHash = $actualHash;

        parent::__construct(
            "[CONTRACT INTEGRITY FAILURE] Subscription #{$subscriptionId} snapshot verification failed. " .
            "Expected hash: {$expectedHash}, Actual hash: {$actualHash}. " .
            "Bonus calculation HALTED. Investigate for potential tampering."
        );
    }

    public function getSubscriptionId(): int
    {
        return $this->subscriptionId;
    }

    public function getExpectedHash(): string
    {
        return $this->expectedHash;
    }

    public function getActualHash(): string
    {
        return $this->actualHash;
    }

    /**
     * V-CONTRACT-HARDENING-FINAL: Return structured context for logging
     */
    public function reportContext(): array
    {
        return [
            'exception_type' => 'ContractIntegrityException',
            'alert_level' => 'CRITICAL',
            'subscription_id' => $this->subscriptionId,
            'expected_hash' => $this->expectedHash,
            'actual_hash' => $this->actualHash,
            'action_required' => 'Investigate for potential tampering',
            'financial_impact' => 'Bonus calculation halted',
        ];
    }
}
