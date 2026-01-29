<?php
/**
 * STORY 4.2: Bulk Purchase Provenance Exception
 *
 * GOVERNANCE INVARIANT:
 * - All bulk purchases must have verifiable provenance
 * - Manual entries require explicit justification (manual_entry_reason)
 * - Manual entries require supporting documentation (source_documentation)
 *
 * This exception is thrown when a bulk purchase violates provenance requirements.
 * These are HARD FAILURES - inventory without provenance cannot be accepted.
 */

namespace App\Exceptions;

use DomainException;

class BulkPurchaseProvenanceException extends DomainException
{
    public function __construct(
        string $message,
        public readonly string $sourceType,
        public readonly ?string $missingField,
    ) {
        parent::__construct($message);
    }

    /**
     * Manual entry requires a reason.
     */
    public static function manualEntryRequiresReason(): self
    {
        return new self(
            message: "Manual bulk purchase blocked: 'manual_entry_reason' is required for manual inventory entries. " .
                     "Explain why this inventory is being entered manually instead of through a company listing. " .
                     "This is required for audit trail and compliance.",
            sourceType: 'manual_entry',
            missingField: 'manual_entry_reason',
        );
    }

    /**
     * Manual entry requires supporting documentation.
     */
    public static function manualEntryRequiresDocumentation(): self
    {
        return new self(
            message: "Manual bulk purchase blocked: 'source_documentation' is required for manual inventory entries. " .
                     "Provide supporting documents (purchase agreements, invoices, certificates) that verify this inventory. " .
                     "This is required for audit trail and compliance.",
            sourceType: 'manual_entry',
            missingField: 'source_documentation',
        );
    }

    /**
     * Company listing source requires listing ID.
     */
    public static function listingSourceRequiresListingId(): self
    {
        return new self(
            message: "Bulk purchase blocked: 'company_share_listing_id' is required when source_type is 'company_listing'. " .
                     "The inventory must be linked to an approved company share listing.",
            sourceType: 'company_listing',
            missingField: 'company_share_listing_id',
        );
    }

    /**
     * Source type is required.
     */
    public static function sourceTypeRequired(): self
    {
        return new self(
            message: "Bulk purchase blocked: 'source_type' is required. " .
                     "Must be either 'company_listing' (from approved listing) or 'manual_entry' (with justification).",
            sourceType: 'unknown',
            missingField: 'source_type',
        );
    }

    /**
     * Get structured context for logging/auditing.
     */
    public function context(): array
    {
        return [
            'source_type' => $this->sourceType,
            'missing_field' => $this->missingField,
            'violation' => 'provenance_requirement',
        ];
    }
}
