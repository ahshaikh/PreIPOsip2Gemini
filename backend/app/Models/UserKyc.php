<?php
// V-PHASE1-1730-010 (Created) | V-FINAL-1730-327 (Logic Upgraded)
// V-AUDIT-MODULE2-006 (Updated) - Added granular verification columns and casts

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\KycStatus; // ADDED: Import KycStatus enum

class UserKyc extends Model
{
    use HasFactory;

    protected $table = 'user_kyc';

    // Centralized Validation Rules for Reuse
    const PAN_REGEX = '/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/';
    const AADHAAR_REGEX = '/^[2-9]{1}[0-9]{3}\\s[0-9]{4}\\s[0-9]{4}$|^[2-9]{1}[0-9]{11}$/'; // Matches "1234 5678 9012" or "123456789012"

    protected $fillable = [
        'user_id',
        'pan_number',
        'aadhaar_number',
        'demat_account',
        'bank_account',
        'bank_ifsc',
        'bank_name',
        'status',
        'rejection_reason',
        'resubmission_instructions', // ADDED: For resubmission flow
        'verified_by',
        'verified_at',
        'submitted_at',
        'verification_checklist', // ADDED: Admin verification checklist

        // ADDED: Granular verification flags and timestamps
        'is_aadhaar_verified',
        'aadhaar_verified_at',
        'aadhaar_verification_source',
        'is_pan_verified',
        'pan_verified_at',
        'pan_verification_source',
        'is_bank_verified',
        'bank_verified_at',
        'bank_verification_source',
        'is_demat_verified',
        'demat_verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'submitted_at' => 'datetime',
        // ADDED: Cast new datetime columns
        'aadhaar_verified_at' => 'datetime',
        'pan_verified_at' => 'datetime',
        'bank_verified_at' => 'datetime',
        'demat_verified_at' => 'datetime',
        // ADDED: Cast boolean flags
        'is_aadhaar_verified' => 'boolean',
        'is_pan_verified' => 'boolean',
        'is_bank_verified' => 'boolean',
        'is_demat_verified' => 'boolean',
        // ADDED: Cast JSON checklist
        'verification_checklist' => 'array',
    ];

    // --- RELATIONSHIPS ---

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(KycDocument::class, 'user_kyc_id');
    }

    /**
     * ADDED: Relationship for verification notes (used in Admin panel)
     * Requires kyc_verification_notes table to exist
     */
    public function verificationNotes(): HasMany
    {
        return $this->hasMany(\App\Models\KycVerificationNote::class, 'user_kyc_id');
    }

    // --- LOGIC & ACCESSORS ---

    /**
     * Calculate completion percentage based on fields and docs.
     * UPDATED: Now considers granular verification status
     */
    public function getCompletionPercentageAttribute(): int
    {
        $totalSteps = 6; // PAN, Aadhaar, Bank, Demat, PAN Doc, Aadhaar Doc
        $completedSteps = 0;

        // 1. Text Fields
        if (!empty($this->pan_number)) $completedSteps++;
        if (!empty($this->aadhaar_number)) $completedSteps++;
        if (!empty($this->bank_account)) $completedSteps++;
        if (!empty($this->demat_account)) $completedSteps++;

        // 2. Documents (Check for existence of key types)
        // We assume documents relation is loaded or we load it
        $docs = $this->documents;

        if ($docs->where('doc_type', 'pan')->count() > 0) $completedSteps++;
        if ($docs->where('doc_type', 'aadhaar_front')->count() > 0) $completedSteps++;

        if ($totalSteps == 0) return 0;

        return (int) round(($completedSteps / $totalSteps) * 100);
    }

    /**
     * ADDED: Get verification progress percentage based on actual verifications
     * This is more accurate than completion percentage as it shows actual verified components
     *
     * @return int
     */
    public function getVerificationProgressAttribute(): int
    {
        $isDematRequired = (bool) setting('kyc_require_demat', false);
        $totalComponents = $isDematRequired ? 4 : 3;

        $verifiedCount = 0;
        if ($this->is_aadhaar_verified) $verifiedCount++;
        if ($this->is_pan_verified) $verifiedCount++;
        if ($this->is_bank_verified) $verifiedCount++;
        if ($isDematRequired && $this->is_demat_verified) $verifiedCount++;

        return (int) round(($verifiedCount / $totalComponents) * 100);
    }

    /**
     * ADDED: Get structured verification status for API responses
     * Returns detailed status of each component verification
     *
     * @return array
     */
    public function getVerificationStatusAttribute(): array
    {
        return [
            'aadhaar' => [
                'verified' => (bool) $this->is_aadhaar_verified,
                'verified_at' => $this->aadhaar_verified_at?->toISOString(),
                'source' => $this->aadhaar_verification_source,
            ],
            'pan' => [
                'verified' => (bool) $this->is_pan_verified,
                'verified_at' => $this->pan_verified_at?->toISOString(),
                'source' => $this->pan_verification_source,
            ],
            'bank' => [
                'verified' => (bool) $this->is_bank_verified,
                'verified_at' => $this->bank_verified_at?->toISOString(),
                'source' => $this->bank_verification_source,
            ],
            'demat' => [
                'verified' => (bool) $this->is_demat_verified,
                'verified_at' => $this->demat_verified_at?->toISOString(),
                'required' => (bool) setting('kyc_require_demat', false),
            ],
        ];
    }

    /**
     * Check if all required documents are present.
     */
    public function hasAllDocuments(): bool
    {
        $requiredTypes = ['pan', 'aadhaar_front', 'aadhaar_back', 'bank_proof'];
        $uploadedTypes = $this->documents->pluck('doc_type')->toArray();

        foreach ($requiredTypes as $type) {
            if (!in_array($type, $uploadedTypes)) {
                return false;
            }
        }
        return true;
    }

    /**
     * ADDED: Get status as KycStatus enum
     * Useful for type-safe status comparisons
     *
     * @return KycStatus
     */
    public function getStatusEnum(): KycStatus
    {
        return KycStatus::from($this->status);
    }
}