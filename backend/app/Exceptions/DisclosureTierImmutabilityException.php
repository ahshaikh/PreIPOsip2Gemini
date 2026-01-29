<?php
/**
 * STORY 3.1: Disclosure Tier Immutability Exception
 *
 * GOVERNANCE INVARIANT:
 * Direct modification of disclosure_tier is FORBIDDEN.
 * All tier changes MUST go through CompanyDisclosureTierService::promote().
 *
 * This exception is thrown when:
 * 1. Code attempts to directly modify disclosure_tier via fill(), update(), save()
 * 2. Code attempts to downgrade a tier
 * 3. Code attempts to skip tiers
 * 4. Code attempts to promote without authorization
 *
 * COMPLIANCE NOTE:
 * This exception is designed for forensic/audit review.
 * It captures full context of the violation attempt.
 */

namespace App\Exceptions;

use App\Enums\DisclosureTier;
use Exception;

class DisclosureTierImmutabilityException extends Exception
{
    protected string $companyId;
    protected ?string $currentTier;
    protected ?string $attemptedTier;
    protected string $violationType;
    protected array $context;

    /**
     * Violation types for classification.
     */
    public const VIOLATION_DIRECT_MODIFICATION = 'direct_modification';
    public const VIOLATION_DOWNGRADE_ATTEMPT = 'downgrade_attempt';
    public const VIOLATION_TIER_SKIP = 'tier_skip';
    public const VIOLATION_UNAUTHORIZED_PROMOTION = 'unauthorized_promotion';
    public const VIOLATION_INVALID_TIER = 'invalid_tier';

    public function __construct(
        string $companyId,
        ?string $currentTier,
        ?string $attemptedTier,
        string $violationType,
        array $context = []
    ) {
        $this->companyId = $companyId;
        $this->currentTier = $currentTier;
        $this->attemptedTier = $attemptedTier;
        $this->violationType = $violationType;
        $this->context = $context;

        $message = $this->buildMessage();

        parent::__construct($message, 403);
    }

    /**
     * Build human-readable error message based on violation type.
     */
    protected function buildMessage(): string
    {
        $base = "Disclosure tier immutability violation on Company ID: {$this->companyId}. ";

        return match ($this->violationType) {
            self::VIOLATION_DIRECT_MODIFICATION => $base .
                "Direct modification of disclosure_tier is forbidden. " .
                "Use CompanyDisclosureTierService::promote() instead. " .
                "Attempted: '{$this->currentTier}' → '{$this->attemptedTier}'.",

            self::VIOLATION_DOWNGRADE_ATTEMPT => $base .
                "Tier downgrade is forbidden. Tiers are monotonically progressive. " .
                "Current: '{$this->currentTier}', Attempted: '{$this->attemptedTier}'.",

            self::VIOLATION_TIER_SKIP => $base .
                "Tier skipping is forbidden. Must promote one tier at a time. " .
                "Current: '{$this->currentTier}', Attempted: '{$this->attemptedTier}'.",

            self::VIOLATION_UNAUTHORIZED_PROMOTION => $base .
                "Unauthorized promotion attempt. Actor lacks permission to promote companies. " .
                "Actor: " . ($this->context['actor_id'] ?? 'unknown') . ".",

            self::VIOLATION_INVALID_TIER => $base .
                "Invalid tier value: '{$this->attemptedTier}'. " .
                "Valid tiers: " . implode(', ', DisclosureTier::values()) . ".",

            default => $base . "Unknown violation type: {$this->violationType}.",
        };
    }

    /**
     * Create exception for direct modification attempts.
     */
    public static function directModification(
        string $companyId,
        ?string $currentTier,
        ?string $attemptedTier
    ): self {
        return new self(
            $companyId,
            $currentTier,
            $attemptedTier,
            self::VIOLATION_DIRECT_MODIFICATION
        );
    }

    /**
     * Create exception for downgrade attempts.
     */
    public static function downgradeAttempt(
        string $companyId,
        string $currentTier,
        string $attemptedTier
    ): self {
        return new self(
            $companyId,
            $currentTier,
            $attemptedTier,
            self::VIOLATION_DOWNGRADE_ATTEMPT
        );
    }

    /**
     * Create exception for tier skip attempts.
     */
    public static function tierSkip(
        string $companyId,
        string $currentTier,
        string $attemptedTier
    ): self {
        return new self(
            $companyId,
            $currentTier,
            $attemptedTier,
            self::VIOLATION_TIER_SKIP
        );
    }

    /**
     * Create exception for unauthorized promotion attempts.
     */
    public static function unauthorizedPromotion(
        string $companyId,
        ?string $currentTier,
        ?string $attemptedTier,
        $actorId = null
    ): self {
        return new self(
            $companyId,
            $currentTier,
            $attemptedTier,
            self::VIOLATION_UNAUTHORIZED_PROMOTION,
            ['actor_id' => $actorId]
        );
    }

    /**
     * Create exception for invalid tier values.
     */
    public static function invalidTier(
        string $companyId,
        ?string $attemptedTier
    ): self {
        return new self(
            $companyId,
            null,
            $attemptedTier,
            self::VIOLATION_INVALID_TIER
        );
    }

    /**
     * Get the company ID.
     */
    public function getCompanyId(): string
    {
        return $this->companyId;
    }

    /**
     * Get the current tier.
     */
    public function getCurrentTier(): ?string
    {
        return $this->currentTier;
    }

    /**
     * Get the attempted tier.
     */
    public function getAttemptedTier(): ?string
    {
        return $this->attemptedTier;
    }

    /**
     * Get the violation type.
     */
    public function getViolationType(): string
    {
        return $this->violationType;
    }

    /**
     * Get the context array.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Render the exception as JSON response.
     */
    public function render($request)
    {
        return response()->json([
            'error' => 'disclosure_tier_immutability_violation',
            'violation_type' => $this->violationType,
            'message' => $this->getMessage(),
            'company_id' => $this->companyId,
            'current_tier' => $this->currentTier,
            'attempted_tier' => $this->attemptedTier,
            'context' => $this->context,
        ], $this->getCode());
    }
}
