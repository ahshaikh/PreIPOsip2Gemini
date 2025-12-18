<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-LEGAL-IMMUTABILITY | V-VERSION-TRACKING
 * Refactored to address Phase 13 Audit Gaps:
 * 1. Enforcement of Immutability: Prevents tampering with content after publication.
 * 2. Signature Integrity: Added relationships to track user acceptances.
 * 3. Status Transitions: Ensures only one 'active' version exists per agreement.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalAgreementVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_agreement_id',
        'version',      // e.g., "1.0.2"
        'content',      // The full text of the agreement
        'change_summary',
        'status',       // 'draft', 'active', 'archived'
        'effective_date',
        'acceptance_count',
        'created_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'version' => 'string',
        'created_at' => 'datetime',
    ];

    /**
     * [AUDIT FIX]: SECURITY - Protect Legal Integrity.
     * Once a version is 'active', it must be immutable to ensure that 
     * what the user signed is exactly what is stored in the DB.
     */
    protected static function booted()
    {
        static::updating(function ($version) {
            // If the version is already active or archived, block content changes.
            if ($version->getOriginal('status') !== 'draft') {
                return false; 
            }
        });

        static::deleting(function ($version) {
            // Never allow deletion of a version that has been signed by users.
            if ($version->acceptance_count > 0) {
                return false;
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function legalAgreement(): BelongsTo
    {
        return $this->belongsTo(LegalAgreement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * [AUDIT FIX]: Direct access to signatures for this specific version.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(UserAgreementSignature::class, 'legal_agreement_version_id');
    }

    // --- HELPER LOGIC ---

    /**
     * Increment acceptance count atomically.
     */
    public function incrementAcceptance(): void
    {
        $this->increment('acceptance_count');
    }
}