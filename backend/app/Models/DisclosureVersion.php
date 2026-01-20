<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PHASE 1 - MODEL 3/5: DisclosureVersion
 *
 * PURPOSE:
 * Represents an immutable historical snapshot of a company disclosure at approval time.
 * Provides audit trail for regulatory compliance and investor protection.
 *
 * KEY RESPONSIBILITIES:
 * - Store complete snapshot of disclosure data at approval
 * - Enforce immutability (no updates/deletes allowed)
 * - Track investor visibility and view counts
 * - Link to SEBI filings and certifications
 * - Provide tamper detection via SHA-256 hash
 *
 * IMMUTABILITY ENFORCEMENT:
 * - is_locked always true
 * - Observer prevents updates/deletes
 * - NO softDeletes (permanent retention)
 * - version_hash for tamper detection
 *
 * @property int $id
 * @property int $company_disclosure_id Parent disclosure
 * @property int $company_id Denormalized company ID
 * @property int $disclosure_module_id Denormalized module ID
 * @property int $version_number Sequential version (1, 2, 3...)
 * @property string $version_hash SHA-256 hash of disclosure_data
 * @property array $disclosure_data IMMUTABLE: Full snapshot
 * @property array|null $attachments IMMUTABLE: Supporting documents
 * @property array|null $changes_summary What changed from previous version
 * @property string|null $change_reason Company-provided reason
 * @property bool $is_locked IMMUTABILITY FLAG: Always true
 * @property \Illuminate\Support\Carbon|null $locked_at
 * @property \Illuminate\Support\Carbon $approved_at
 * @property int $approved_by Admin who approved
 * @property string|null $approval_notes Admin notes
 * @property bool $was_investor_visible Whether investors saw this version
 * @property \Illuminate\Support\Carbon|null $first_investor_view_at
 * @property int $investor_view_count How many times viewed
 * @property array|null $linked_transactions Investor purchases
 * @property string|null $sebi_filing_reference SEBI filing reference
 * @property \Illuminate\Support\Carbon|null $sebi_filed_at
 * @property array|null $certification Digital signature
 * @property string|null $created_by_ip
 * @property string|null $created_by_user_agent
 * @property int|null $created_by CompanyUser who triggered
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DisclosureVersion extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'disclosure_versions';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_disclosure_id',
        'company_id',
        'disclosure_module_id',
        'version_number',
        'version_hash',
        'disclosure_data',
        'attachments',
        'changes_summary',
        'change_reason',
        'is_locked',
        'locked_at',
        'approved_at',
        'approved_by',
        'approval_notes',
        'was_investor_visible',
        'first_investor_view_at',
        'investor_view_count',
        'linked_transactions',
        'sebi_filing_reference',
        'sebi_filed_at',
        'certification',
        'created_by_ip',
        'created_by_user_agent',
        'created_by_type',  // Polymorphic: User or CompanyUser
        'created_by_id',    // Polymorphic ID
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'version_number' => 'integer',
        'disclosure_data' => 'array',
        'attachments' => 'array',
        'changes_summary' => 'array',
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
        'approved_at' => 'datetime',
        'was_investor_visible' => 'boolean',
        'first_investor_view_at' => 'datetime',
        'investor_view_count' => 'integer',
        'linked_transactions' => 'array',
        'sebi_filed_at' => 'datetime',
        'certification' => 'array',
    ];

    // =========================================================================
    // MODEL CONFIGURATION
    // =========================================================================

    /**
     * Indicates if the model should use timestamps.
     * We keep timestamps but prevent updates via Observer
     */
    public $timestamps = true;

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Parent disclosure (current state)
     */
    public function companyDisclosure()
    {
        return $this->belongsTo(CompanyDisclosure::class);
    }

    /**
     * Company that owns this disclosure
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Disclosure module template
     */
    public function disclosureModule()
    {
        return $this->belongsTo(DisclosureModule::class);
    }

    /**
     * Admin who approved this version
     * Note: approved_by remains FK to users (only admins approve)
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Polymorphic: CompanyUser or User who triggered this version creation
     * Usually: CompanyUser creates (by submitting), Admin may also trigger
     */
    public function createdBy()
    {
        return $this->morphTo(__FUNCTION__, 'created_by_type', 'created_by_id');
    }

    /**
     * DEPRECATED: Use createdBy() instead (polymorphic)
     * Kept for backwards compatibility
     */
    public function creator()
    {
        return $this->createdBy();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to versions by company
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to versions that were investor-visible
     */
    public function scopeInvestorVisible($query)
    {
        return $query->where('was_investor_visible', true);
    }

    /**
     * Scope to versions with SEBI filing
     */
    public function scopeSebiFiled($query)
    {
        return $query->whereNotNull('sebi_filing_reference');
    }

    /**
     * Scope to versions by approval date range
     */
    public function scopeApprovedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('approved_at', [$startDate, $endDate]);
    }

    // =========================================================================
    // STATIC FACTORY METHODS
    // =========================================================================

    /**
     * Create immutable version snapshot from CompanyDisclosure
     *
     * @param CompanyDisclosure $disclosure Parent disclosure
     * @param int $adminId Admin approving
     * @param string|null $approvalNotes Admin notes
     * @return self
     */
    public static function createFromDisclosure(
        CompanyDisclosure $disclosure,
        int $adminId,
        ?string $approvalNotes = null
    ): self {
        // Calculate changes from previous version
        $changesSummary = null;
        $previousVersion = $disclosure->versions()->latest('version_number')->first();

        if ($previousVersion) {
            $changesSummary = self::calculateChangesSummary(
                $previousVersion->disclosure_data,
                $disclosure->disclosure_data
            );
        }

        // Generate hash for tamper detection
        $hash = hash('sha256', json_encode($disclosure->disclosure_data));

        return self::create([
            'company_disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'disclosure_module_id' => $disclosure->disclosure_module_id,
            'version_number' => $disclosure->version_number,
            'version_hash' => $hash,
            'disclosure_data' => $disclosure->disclosure_data,
            'attachments' => $disclosure->attachments,
            'changes_summary' => $changesSummary,
            'change_reason' => null, // Can be added later if needed
            'is_locked' => true,
            'locked_at' => now(),
            'approved_at' => now(),
            'approved_by' => $adminId,
            'approval_notes' => $approvalNotes,
            'was_investor_visible' => false, // Set true when published
            'investor_view_count' => 0,
            'created_by' => $disclosure->last_modified_by ?? $disclosure->submitted_by,
            'created_by_ip' => $disclosure->last_modified_ip,
            'created_by_user_agent' => $disclosure->last_modified_user_agent,
        ]);
    }

    /**
     * Calculate summary of changes between two disclosure data arrays
     *
     * @param array $oldData Previous version data
     * @param array $newData Current version data
     * @return array Summary of changes
     */
    protected static function calculateChangesSummary(array $oldData, array $newData): array
    {
        $changes = [];

        // Compare top-level keys
        foreach ($newData as $key => $value) {
            if (!isset($oldData[$key])) {
                $changes[$key] = 'Added';
            } elseif ($oldData[$key] !== $value) {
                $changes[$key] = 'Modified';
            }
        }

        // Check for removed keys
        foreach ($oldData as $key => $value) {
            if (!isset($newData[$key])) {
                $changes[$key] = 'Removed';
            }
        }

        return $changes;
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Mark this version as investor-visible (when company goes live)
     */
    public function markAsInvestorVisible(): void
    {
        if (!$this->was_investor_visible) {
            $this->update([
                'was_investor_visible' => true,
                'first_investor_view_at' => now(),
            ]);
        }
    }

    /**
     * Increment investor view count
     */
    public function incrementViewCount(): void
    {
        $this->increment('investor_view_count');

        // Mark as investor-visible on first view
        if (!$this->was_investor_visible) {
            $this->markAsInvestorVisible();
        }
    }

    /**
     * Link investor transaction to this version (for liability tracking)
     *
     * @param int $transactionId
     * @param array $additionalData
     */
    public function linkTransaction(int $transactionId, array $additionalData = []): void
    {
        $linkedTransactions = $this->linked_transactions ?? [];

        $linkedTransactions[] = array_merge([
            'transaction_id' => $transactionId,
            'linked_at' => now()->toIso8601String(),
        ], $additionalData);

        $this->update(['linked_transactions' => $linkedTransactions]);
    }

    /**
     * Verify integrity of version data using hash
     *
     * @return bool True if data matches hash
     */
    public function verifyIntegrity(): bool
    {
        $currentHash = hash('sha256', json_encode($this->disclosure_data));
        return $currentHash === $this->version_hash;
    }

    /**
     * Check if this version has linked investor transactions
     *
     * @return bool
     */
    public function hasLinkedTransactions(): bool
    {
        return !empty($this->linked_transactions);
    }

    /**
     * Get count of linked transactions
     *
     * @return int
     */
    public function getLinkedTransactionCount(): int
    {
        return count($this->linked_transactions ?? []);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get human-readable version label
     */
    public function getVersionLabelAttribute(): string
    {
        return "Version {$this->version_number}";
    }

    /**
     * Check if version is SEBI-filed
     */
    public function getIsSebifiledAttribute(): bool
    {
        return !empty($this->sebi_filing_reference);
    }

    /**
     * Check if version has certification
     */
    public function getIsCertifiedAttribute(): bool
    {
        return !empty($this->certification);
    }
}
