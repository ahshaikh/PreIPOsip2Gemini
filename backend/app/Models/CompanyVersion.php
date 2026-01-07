<?php
/**
 * FIX 33, 34, 35: Company Versioning System
 *
 * Maintains historical snapshots of company data for:
 * - Audit compliance (track all changes)
 * - Regulatory requirements (immutable approved data)
 * - Investor transparency (show what was displayed at investment time)
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyVersion extends Model
{
    protected $fillable = [
        'company_id',
        'version_number',
        'snapshot_data',        // Full company data at this version
        'changed_fields',       // Array of field names that changed
        'change_summary',       // Human-readable description of changes
        'created_by',           // User who made the change
        'reason',               // Reason for the change (manual edit, approval, etc.)
        'is_approval_snapshot', // FIX 35: Mark snapshots taken at listing approval
        'approval_id',          // Reference to approval event (CompanyShareListing, etc.)
        'metadata',             // Additional context
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
        'is_approval_snapshot' => 'boolean',
    ];

    /**
     * Disable updates - versions are immutable once created
     */
    protected static function booted()
    {
        static::updating(function () {
            throw new \RuntimeException(
                'Company versions are immutable. Create a new version instead.'
            );
        });
    }

    // --- RELATIONSHIPS ---

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // --- HELPERS ---

    /**
     * Get the fields that changed in this version
     */
    public function getChangedFieldsList(): array
    {
        return $this->changed_fields ?? [];
    }

    /**
     * Check if a specific field changed in this version
     */
    public function hasFieldChanged(string $field): bool
    {
        return in_array($field, $this->getChangedFieldsList());
    }

    /**
     * Get the value of a field from this version's snapshot
     */
    public function getSnapshotValue(string $field)
    {
        return $this->snapshot_data[$field] ?? null;
    }

    /**
     * Create a new version from a Company model
     */
    public static function createFromCompany(
        Company $company,
        ?array $changedFields = null,
        ?string $reason = null,
        bool $isApprovalSnapshot = false,
        ?int $approvalId = null
    ): self {
        // Get the latest version number
        $latestVersion = self::where('company_id', $company->id)
            ->max('version_number');

        $versionNumber = ($latestVersion ?? 0) + 1;

        // If changed fields not provided, detect them
        if ($changedFields === null) {
            $changedFields = $company->getDirty() ? array_keys($company->getDirty()) : [];
        }

        // Create change summary
        $changeSummary = self::generateChangeSummary($changedFields, $reason);

        return self::create([
            'company_id' => $company->id,
            'version_number' => $versionNumber,
            'snapshot_data' => $company->toArray(),
            'changed_fields' => $changedFields,
            'change_summary' => $changeSummary,
            'created_by' => auth()->id(),
            'reason' => $reason ?? 'Company data updated',
            'is_approval_snapshot' => $isApprovalSnapshot,
            'approval_id' => $approvalId,
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Generate human-readable change summary
     */
    private static function generateChangeSummary(array $changedFields, ?string $reason): string
    {
        if (empty($changedFields)) {
            return $reason ?? 'No changes';
        }

        $fieldLabels = [
            'name' => 'Company Name',
            'description' => 'Description',
            'logo' => 'Logo',
            'website' => 'Website',
            'sector' => 'Sector',
            'latest_valuation' => 'Valuation',
            'funding_stage' => 'Funding Stage',
            'total_funding' => 'Total Funding',
            'ceo_name' => 'CEO Name',
            'headquarters' => 'Headquarters',
        ];

        $readableFields = array_map(
            fn($field) => $fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field)),
            $changedFields
        );

        $summary = 'Updated: ' . implode(', ', $readableFields);

        if ($reason) {
            $summary .= " ({$reason})";
        }

        return $summary;
    }

    /**
     * Get the previous version
     */
    public function getPreviousVersion(): ?self
    {
        return self::where('company_id', $this->company_id)
            ->where('version_number', '<', $this->version_number)
            ->orderBy('version_number', 'desc')
            ->first();
    }

    /**
     * Get the next version
     */
    public function getNextVersion(): ?self
    {
        return self::where('company_id', $this->company_id)
            ->where('version_number', '>', $this->version_number)
            ->orderBy('version_number', 'asc')
            ->first();
    }
}
