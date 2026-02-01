<?php

namespace App\Exceptions;

use RuntimeException;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 1 AUDIT: Disclosure Authority Violation Exception
 *
 * PURPOSE:
 * Thrown when disclosure authority invariants are violated:
 * - Approved disclosure lacks a valid immutable version
 * - Attempt to modify approved/locked disclosure
 * - Mixed-version read attempted
 * - Fallback to draft/previous disclosure attempted
 *
 * HANDLING:
 * This exception indicates a CRITICAL data integrity issue.
 * It should NEVER be caught and silently ignored.
 * It should trigger immediate investigation by platform team.
 *
 * INVARIANTS PROTECTED:
 * 1. Approved disclosures MUST have a locked DisclosureVersion
 * 2. Investors MUST see immutable version data, not mutable disclosure data
 * 3. Snapshots MUST capture immutable version data
 * 4. No fallback to draft/rejected/previous versions
 */
class DisclosureAuthorityViolationException extends RuntimeException
{
    protected string $violationType;
    protected ?int $disclosureId;
    protected ?int $companyId;
    protected ?int $versionId;
    protected array $context;

    /**
     * Create a new exception instance
     *
     * @param string $message Human-readable error message
     * @param string $violationType Type of violation (e.g., 'missing_version', 'unlocked_version', 'immutability_breach')
     * @param int|null $disclosureId The disclosure ID involved
     * @param int|null $companyId The company ID involved
     * @param int|null $versionId The version ID involved (if any)
     * @param array $context Additional context for debugging
     */
    public function __construct(
        string $message,
        string $violationType,
        ?int $disclosureId = null,
        ?int $companyId = null,
        ?int $versionId = null,
        array $context = []
    ) {
        parent::__construct($message);

        $this->violationType = $violationType;
        $this->disclosureId = $disclosureId;
        $this->companyId = $companyId;
        $this->versionId = $versionId;
        $this->context = $context;

        // Log immediately - these are critical
        $this->logViolation();
    }

    /**
     * Log the violation for audit trail
     */
    protected function logViolation(): void
    {
        Log::emergency('DISCLOSURE AUTHORITY VIOLATION', [
            'violation_type' => $this->violationType,
            'disclosure_id' => $this->disclosureId,
            'company_id' => $this->companyId,
            'version_id' => $this->versionId,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'timestamp' => now()->toIso8601String(),
            'actor_id' => auth()->id(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'stack_trace' => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10))
                ->map(fn($frame) => ($frame['class'] ?? '') . '::' . ($frame['function'] ?? ''))
                ->filter()
                ->values()
                ->toArray(),
        ]);
    }

    // =========================================================================
    // FACTORY METHODS
    // =========================================================================

    /**
     * Approved disclosure lacks current_version_id
     */
    public static function missingVersion(int $disclosureId, int $companyId): self
    {
        return new self(
            "Approved disclosure {$disclosureId} lacks current_version_id. " .
            "This indicates the approval process did not create an immutable version.",
            'missing_version',
            $disclosureId,
            $companyId
        );
    }

    /**
     * Version record doesn't exist
     */
    public static function versionNotFound(int $disclosureId, int $versionId, int $companyId): self
    {
        return new self(
            "Version {$versionId} for disclosure {$disclosureId} not found in database. " .
            "This indicates data corruption or deletion of immutable records.",
            'version_not_found',
            $disclosureId,
            $companyId,
            $versionId
        );
    }

    /**
     * Version exists but is not locked
     */
    public static function unlockedVersion(int $disclosureId, int $versionId, int $companyId): self
    {
        return new self(
            "Version {$versionId} for disclosure {$disclosureId} is not locked. " .
            "All approved versions MUST be locked for immutability.",
            'unlocked_version',
            $disclosureId,
            $companyId,
            $versionId
        );
    }

    /**
     * Attempt to modify immutable data
     */
    public static function immutabilityBreach(int $disclosureId, int $companyId, array $attemptedChanges): self
    {
        return new self(
            "Attempt to modify immutable disclosure {$disclosureId}. " .
            "Attempted changes: " . implode(', ', array_keys($attemptedChanges)),
            'immutability_breach',
            $disclosureId,
            $companyId,
            null,
            ['attempted_changes' => $attemptedChanges]
        );
    }

    /**
     * Hash mismatch detected - possible tampering
     */
    public static function hashMismatch(int $versionId, string $storedHash, string $computedHash): self
    {
        return new self(
            "Hash mismatch for version {$versionId}. " .
            "Stored: {$storedHash}, Computed: {$computedHash}. " .
            "This indicates possible data tampering.",
            'hash_mismatch',
            null,
            null,
            $versionId,
            ['stored_hash' => $storedHash, 'computed_hash' => $computedHash]
        );
    }

    /**
     * Attempt to access non-approved disclosure as investor
     */
    public static function nonApprovedAccess(int $disclosureId, string $actualStatus, int $companyId): self
    {
        return new self(
            "Attempt to access non-approved disclosure {$disclosureId} (status: {$actualStatus}) " .
            "in investor context. Only approved disclosures are visible to investors.",
            'non_approved_access',
            $disclosureId,
            $companyId,
            null,
            ['actual_status' => $actualStatus]
        );
    }

    // =========================================================================
    // GETTERS
    // =========================================================================

    public function getViolationType(): string
    {
        return $this->violationType;
    }

    public function getDisclosureId(): ?int
    {
        return $this->disclosureId;
    }

    public function getCompanyId(): ?int
    {
        return $this->companyId;
    }

    public function getVersionId(): ?int
    {
        return $this->versionId;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get structured data for audit logging
     */
    public function toAuditArray(): array
    {
        return [
            'exception_type' => 'DisclosureAuthorityViolationException',
            'violation_type' => $this->violationType,
            'message' => $this->getMessage(),
            'disclosure_id' => $this->disclosureId,
            'company_id' => $this->companyId,
            'version_id' => $this->versionId,
            'context' => $this->context,
            'severity' => 'emergency',
            'requires_investigation' => true,
        ];
    }
}
