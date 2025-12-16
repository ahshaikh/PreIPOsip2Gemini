<?php
// V-AUDIT-MODULE2-002 (Created) - KYC Document Type Enum
// Purpose: Centralized document type values to prevent typos and ensure consistency

namespace App\Enums;

/**
 * KYC Document Type Enumeration
 *
 * Represents all valid KYC document types that users can upload
 */
enum KycDocumentType: string
{
    /**
     * Front side of Aadhaar card
     */
    case AADHAAR_FRONT = 'aadhaar_front';

    /**
     * Back side of Aadhaar card
     */
    case AADHAAR_BACK = 'aadhaar_back';

    /**
     * PAN card document
     */
    case PAN = 'pan';

    /**
     * Bank account proof (passbook/statement)
     */
    case BANK_PROOF = 'bank_proof';

    /**
     * Demat account proof
     */
    case DEMAT_PROOF = 'demat_proof';

    /**
     * Get all document type values as array
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get required document types (mandatory for KYC submission)
     *
     * @return array<self>
     */
    public static function required(): array
    {
        return [
            self::AADHAAR_FRONT,
            self::AADHAAR_BACK,
            self::PAN,
            self::BANK_PROOF,
            self::DEMAT_PROOF,
        ];
    }

    /**
     * Get human-readable label for the document type
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::AADHAAR_FRONT => 'Aadhaar Front',
            self::AADHAAR_BACK => 'Aadhaar Back',
            self::PAN => 'PAN Card',
            self::BANK_PROOF => 'Bank Account Proof',
            self::DEMAT_PROOF => 'Demat Account Proof',
        };
    }
}
