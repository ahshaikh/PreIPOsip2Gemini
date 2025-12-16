<?php
// V-AUDIT-MODULE2-001 (Created) - KYC Status Enum to replace magic strings
// Purpose: Centralized KYC status values to prevent typos and ensure consistency
// across Controllers, Services, Jobs, and Frontend API responses

namespace App\Enums;

/**
 * KYC Status Enumeration
 *
 * Represents all possible states of a KYC verification process.
 * This enum replaces hardcoded string values throughout the codebase.
 *
 * Status Flow:
 * PENDING → SUBMITTED → PROCESSING → VERIFIED/REJECTED
 *                    ↓
 *              RESUBMISSION_REQUIRED → (back to SUBMITTED)
 */
enum KycStatus: string
{
    /**
     * Initial state when KYC record is created for a new user
     */
    case PENDING = 'pending';

    /**
     * User has submitted KYC documents, awaiting processing
     */
    case SUBMITTED = 'submitted';

    /**
     * KYC is being processed (automated or manual review in progress)
     */
    case PROCESSING = 'processing';

    /**
     * KYC has been successfully verified and approved
     */
    case VERIFIED = 'verified';

    /**
     * KYC has been rejected due to invalid/incomplete documents
     */
    case REJECTED = 'rejected';

    /**
     * Admin has requested user to resubmit specific documents
     */
    case RESUBMISSION_REQUIRED = 'resubmission_required';

    /**
     * Get all status values as array
     * Useful for validation rules and dropdowns
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the status
     * Useful for display purposes in UI
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::SUBMITTED => 'Submitted',
            self::PROCESSING => 'Processing',
            self::VERIFIED => 'Verified',
            self::REJECTED => 'Rejected',
            self::RESUBMISSION_REQUIRED => 'Resubmission Required',
        };
    }

    /**
     * Get badge color class for frontend display
     *
     * @return string
     */
    public function badgeColor(): string
    {
        return match($this) {
            self::PENDING => 'gray',
            self::SUBMITTED => 'blue',
            self::PROCESSING => 'yellow',
            self::VERIFIED => 'green',
            self::REJECTED => 'red',
            self::RESUBMISSION_REQUIRED => 'orange',
        };
    }

    /**
     * Check if KYC is in a finalized state (no further action possible)
     *
     * @return bool
     */
    public function isFinalized(): bool
    {
        return in_array($this, [self::VERIFIED, self::REJECTED]);
    }

    /**
     * Check if KYC can be edited/resubmitted by user
     *
     * @return bool
     */
    public function canBeEdited(): bool
    {
        return in_array($this, [self::PENDING, self::REJECTED, self::RESUBMISSION_REQUIRED]);
    }

    /**
     * Check if KYC requires admin action
     *
     * @return bool
     */
    public function requiresAdminAction(): bool
    {
        return in_array($this, [self::SUBMITTED, self::PROCESSING]);
    }
}
