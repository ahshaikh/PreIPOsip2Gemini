<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\ValueObjects\ComplianceSnapshot;

/**
 * IneligibleActionException
 *
 * Thrown when a user attempts an action they are not eligible for
 * based on their compliance state.
 *
 * This exception carries the compliance snapshot for diagnostic purposes.
 *
 * @package App\Exceptions\Domain
 */
final class IneligibleActionException extends DomainException
{
    private ComplianceSnapshot $complianceSnapshot;
    private string $attemptedAction;

    /**
     * Create exception for ineligible action
     *
     * @param string $attemptedAction Human-readable action name
     * @param ComplianceSnapshot $complianceSnapshot Current compliance state
     * @param array<string> $reasons List of blocking reasons
     */
    public function __construct(
        string $attemptedAction,
        ComplianceSnapshot $complianceSnapshot,
        array $reasons = []
    ) {
        $this->attemptedAction = $attemptedAction;
        $this->complianceSnapshot = $complianceSnapshot;

        $reasonsText = !empty($reasons)
            ? implode('; ', $reasons)
            : 'Compliance requirements not met';

        $message = "Cannot {$attemptedAction}: {$reasonsText}";

        parent::__construct($message);
    }

    /**
     * Get error code for API responses
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return 'INELIGIBLE_ACTION';
    }

    /**
     * Get compliance snapshot
     *
     * @return ComplianceSnapshot
     */
    public function getComplianceSnapshot(): ComplianceSnapshot
    {
        return $this->complianceSnapshot;
    }

    /**
     * Get attempted action name
     *
     * @return string
     */
    public function getAttemptedAction(): string
    {
        return $this->attemptedAction;
    }

    /**
     * Get additional context for API response
     *
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return [
            'attempted_action' => $this->attemptedAction,
            'blockers' => $this->complianceSnapshot->getBlockers(),
            'compliance_state' => $this->complianceSnapshot->toArray(),
        ];
    }
}
