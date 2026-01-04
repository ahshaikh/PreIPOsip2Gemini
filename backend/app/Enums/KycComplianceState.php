<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * KYC Compliance State Enum
 *
 * Represents the derived compliance state for KYC verification.
 * This is NOT persisted - it's derived from UserKyc.status
 *
 * @package App\Enums
 */
enum KycComplianceState: string
{
    case UNVERIFIED = 'unverified';           // No KYC record exists
    case PENDING = 'pending';                  // KYC submitted or processing
    case REJECTED = 'rejected';                // KYC rejected
    case APPROVED = 'approved';                // KYC verified
    case RESUBMISSION_REQUIRED = 'resubmission_required'; // Needs resubmission

    /**
     * Derive KYC compliance state from KycStatus
     *
     * @param KycStatus|null $kycStatus
     * @return self
     */
    public static function fromKycStatus(?KycStatus $kycStatus): self
    {
        if ($kycStatus === null) {
            return self::UNVERIFIED;
        }

        return match ($kycStatus) {
            KycStatus::PENDING, KycStatus::SUBMITTED, KycStatus::PROCESSING => self::PENDING,
            KycStatus::VERIFIED => self::APPROVED,
            KycStatus::REJECTED => self::REJECTED,
            KycStatus::RESUBMISSION_REQUIRED => self::RESUBMISSION_REQUIRED,
        };
    }

    /**
     * Check if KYC is fully approved
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this === self::APPROVED;
    }

    /**
     * Check if KYC can be edited/submitted
     *
     * @return bool
     */
    public function canSubmit(): bool
    {
        return in_array($this, [
            self::UNVERIFIED,
            self::REJECTED,
            self::RESUBMISSION_REQUIRED
        ], true);
    }

    /**
     * Check if KYC is in a pending/processing state
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Get human-readable label
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::UNVERIFIED => 'Not Started',
            self::PENDING => 'Under Review',
            self::REJECTED => 'Rejected',
            self::APPROVED => 'Verified',
            self::RESUBMISSION_REQUIRED => 'Resubmission Required',
        };
    }
}
