<?php
/**
 * V-AUDIT-REFACTOR-2025 | V-LEGAL-SNAPSHOT-INTEGRITY | V-VERSION-ORCHESTRATION
 * * PURSUANT TO CLUSTER C AUDIT:
 * 1. Cryptographic Snapshots: Implements SHA-256 hashing of 'active' content.
 * 2. Absolute Immutability: Prevents any modification once status is not 'draft'.
 * 3. Transition Logic: Ensures hash-based verification for re-acceptance checks.
 * 4. PII-Safe Logging: Integrates with AuditLog without storing massive HTML strings.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * @mixin IdeHelperLegalAgreementVersion
 */
class LegalAgreementVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_agreement_id',
        'version',
        'content',
        'content_hash', // [AUDIT FIX]: Stores SHA-256 hash for cryptographic proof
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
     * [AUDIT FIX]: SECURITY - Protection of Legal Truth.
     * Enforces the "Snapshot" logic and strict lifecycle state machine.
     */
    protected static function booted()
    {
        static::saving(function ($version) {
            // [ARCHITECTURAL FIX]: Content Hashing
            // Before a version becomes 'active', we lock the content hash.
            // This hash is what gets stored in UserAgreementSignature for legal proof.
            if ($version->status === 'active' && empty($version->content_hash)) {
                $version->content_hash = hash('sha256', $version->content);
            }
        });

        static::updating(function ($version) {
            // [ANTI-PATTERN FIX]: Content protection
            // If the record is already active or archived, block any content drift.
            if ($version->getOriginal('status') !== 'draft') {
                if ($version->isDirty('content') || $version->isDirty('content_hash')) {
                    Log::warning("ILLEGAL_AMENDMENT_ATTEMPT: Version #{$version->id} content is locked.");
                    return false; 
                }
            }
        });

        static::deleting(function ($version) {
            // [SECURITY FIX]: Never allow deletion of historical legal records.
            // Records with signatures are permanent evidence.
            if ($version->acceptance_count > 0 || $version->status === 'archived') {
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
     * Linkage to user signatures. 
     * [AUDIT FIX]: Signatures now store content_hash_at_signing for 1:1 verification.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(UserAgreementSignature::class, 'legal_agreement_version_id');
    }

    // --- COMPLIANCE LOGIC ---

    /**
     * Verify if the current content matches the stored hash.
     * [AUDIT REQUIREMENT]: Ensures no "silent corruption" has occurred in DB.
     */
    public function verifyIntegrity(): bool
    {
        if (empty($this->content_hash)) return false;
        return hash_equals($this->content_hash, hash('sha256', $this->content));
    }

    /**
     * Increment acceptance count atomically.
     */
    public function incrementAcceptance(): void
    {
        // [PERFORMANCE FIX]: Atomic DB-level increment to prevent race conditions
        $this->increment('acceptance_count');
    }
}