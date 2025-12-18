<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-FORENSIC-SIGNATURE | V-COMPLIANCE-BINDING
 * Refactored to address Phase 13 Audit Gaps:
 * 1. Legal Binding: Captures immutable snapshots of who signed what and when.
 * 2. Forensic Metadata: Stores IP and User Agent to verify the identity of the signer.
 * 3. Version Linkage: Links directly to a specific LegalAgreementVersion ID.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAgreementSignature extends Model
{
    use HasFactory;

    /**
     * Disable UPDATED_AT as legal signatures must be immutable records.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'legal_agreement_id',
        'legal_agreement_version_id', // [AUDIT FIX]: Link to the specific version text
        'version_signed',             // Redundant string for quick reporting
        'ip_address',                 // Forensic tracking
        'user_agent',                 // Device/Browser fingerprinting
        'signed_at',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * [AUDIT FIX]: SECURITY - Prevent Tampering.
     * Legal signatures should never be updated or deleted through the application.
     */
    protected static function booted()
    {
        static::updating(function ($signature) {
            return false; // Signatures are immutable
        });

        static::deleting(function ($signature) {
            return false; // Signatures must be retained for compliance
        });
    }

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(LegalAgreement::class, 'legal_agreement_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(LegalAgreementVersion::class, 'legal_agreement_version_id');
    }
}