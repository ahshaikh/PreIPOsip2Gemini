<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PHASE 1 - MODEL 2/5: CompanyDisclosure
 *
 * PURPOSE:
 * Represents a company's instance of a specific disclosure module.
 * Each record tracks the current state of one disclosure type (business, financials, etc.)
 * for one company. Historical versions are stored separately in DisclosureVersion.
 *
 * KEY RESPONSIBILITIES:
 * - Store current disclosure data (JSON)
 * - Track lifecycle status (draft → submitted → approved)
 * - Calculate completion percentage
 * - Enforce locking after approval
 * - Link to current approved version
 *
 * LIFECYCLE STATES:
 * draft → submitted → under_review → (clarification_required → resubmitted)* → approved
 *                                  → rejected
 *
 * @property int $id
 * @property int $company_id Company that owns this disclosure
 * @property int $disclosure_module_id Template module
 * @property array $disclosure_data Company-provided disclosure data
 * @property array|null $attachments Supporting documents
 * @property string $status Current lifecycle status
 * @property int $completion_percentage Auto-calculated (0-100)
 * @property bool $is_locked Whether disclosure is locked
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property int|null $submitted_by CompanyUser who submitted
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property int|null $approved_by Admin who approved
 * @property string|null $rejection_reason Admin rejection reason
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property int|null $rejected_by Admin who rejected
 * @property int $version_number Current version number
 * @property int|null $current_version_id FK to disclosure_versions
 * @property \Illuminate\Support\Carbon|null $last_modified_at
 * @property int|null $last_modified_by
 * @property string|null $last_modified_ip
 * @property string|null $last_modified_user_agent
 * @property string|null $internal_notes Admin-only notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class CompanyDisclosure extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'company_disclosures';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'disclosure_module_id',
        'disclosure_data',
        'attachments',
        'status',
        'completion_percentage',
        'is_locked',
        'submitted_at',
        'submitted_by',
        'approved_at',
        'approved_by',
        'rejection_reason',
        'rejected_at',
        'rejected_by',
        'version_number',
        'current_version_id',
        'last_modified_at',
        'last_modified_by',
        'last_modified_ip',
        'last_modified_user_agent',
        'internal_notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'disclosure_data' => 'array',
        'attachments' => 'array',
        'completion_percentage' => 'integer',
        'is_locked' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'version_number' => 'integer',
        'last_modified_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

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
     * CompanyUser who submitted this disclosure
     */
    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Admin who approved this disclosure
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Admin who rejected this disclosure
     */
    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * User who last modified the disclosure data
     */
    public function lastModifier()
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    /**
     * Current approved version snapshot
     */
    public function currentVersion()
    {
        return $this->belongsTo(DisclosureVersion::class, 'current_version_id');
    }

    /**
     * All historical versions of this disclosure
     */
    public function versions()
    {
        return $this->hasMany(DisclosureVersion::class)->orderBy('version_number', 'desc');
    }

    /**
     * Clarifications requested for this disclosure
     */
    public function clarifications()
    {
        return $this->hasMany(DisclosureClarification::class);
    }

    /**
     * Approval workflow records
     */
    public function approvals()
    {
        return $this->hasMany(DisclosureApproval::class)->orderBy('created_at', 'desc');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to disclosures by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to draft disclosures
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to submitted disclosures (pending admin review)
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Scope to approved disclosures
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to locked disclosures
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope to disclosures requiring clarification
     */
    public function scopeNeedsClarification($query)
    {
        return $query->where('status', 'clarification_required');
    }

    /**
     * Scope to incomplete disclosures (completion < 100%)
     */
    public function scopeIncomplete($query)
    {
        return $query->where('completion_percentage', '<', 100);
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Submit disclosure for admin review
     *
     * @param int $userId CompanyUser ID submitting
     * @throws \RuntimeException If disclosure is locked or incomplete
     */
    public function submit(int $userId): void
    {
        if ($this->is_locked) {
            throw new \RuntimeException('Cannot submit locked disclosure');
        }

        if ($this->completion_percentage < 100) {
            throw new \RuntimeException('Cannot submit incomplete disclosure. Completion: ' . $this->completion_percentage . '%');
        }

        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => $userId,
        ]);

        // Create approval record
        DisclosureApproval::create([
            'company_disclosure_id' => $this->id,
            'company_id' => $this->company_id,
            'disclosure_module_id' => $this->disclosure_module_id,
            'request_type' => $this->version_number > 1 ? 'resubmission' : 'initial_submission',
            'requested_by' => $userId,
            'requested_at' => now(),
            'disclosure_version_number' => $this->version_number,
            'status' => 'pending',
        ]);
    }

    /**
     * Request clarifications (admin action)
     *
     * @param int $adminId Admin user ID
     * @param array $clarifications Array of clarification data
     */
    public function requestClarifications(int $adminId, array $clarifications): void
    {
        $this->update([
            'status' => 'clarification_required',
        ]);

        // Create clarification records
        foreach ($clarifications as $clarificationData) {
            DisclosureClarification::create(array_merge($clarificationData, [
                'company_disclosure_id' => $this->id,
                'company_id' => $this->company_id,
                'disclosure_module_id' => $this->disclosure_module_id,
                'asked_by' => $adminId,
                'asked_at' => now(),
            ]));
        }
    }

    /**
     * Approve disclosure (admin action)
     *
     * @param int $adminId Admin user ID
     * @param string|null $notes Admin approval notes
     * @throws \RuntimeException If disclosure not submitted
     */
    public function approve(int $adminId, ?string $notes = null): void
    {
        if (!in_array($this->status, ['submitted', 'resubmitted', 'under_review'])) {
            throw new \RuntimeException('Can only approve submitted disclosures');
        }

        // Create immutable version snapshot
        $version = DisclosureVersion::createFromDisclosure($this, $adminId, $notes);

        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $adminId,
            'current_version_id' => $version->id,
            'is_locked' => true, // Lock after approval
        ]);

        // Update latest approval record
        $this->approvals()->latest()->first()?->update([
            'status' => 'approved',
            'reviewed_by' => $adminId,
            'review_completed_at' => now(),
            'disclosure_version_id' => $version->id,
        ]);
    }

    /**
     * Reject disclosure (admin action)
     *
     * @param int $adminId Admin user ID
     * @param string $reason Rejection reason
     */
    public function reject(int $adminId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $adminId,
            'rejection_reason' => $reason,
        ]);

        // Update latest approval record
        $this->approvals()->latest()->first()?->update([
            'status' => 'rejected',
            'reviewed_by' => $adminId,
            'review_completed_at' => now(),
            'decision_notes' => $reason,
        ]);
    }

    /**
     * Update disclosure data and recalculate completion
     *
     * @param array $data New disclosure data
     * @param int $userId User making the update
     * @throws \RuntimeException If disclosure is locked
     */
    public function updateDisclosureData(array $data, int $userId): void
    {
        if ($this->is_locked) {
            throw new \RuntimeException('Cannot update locked disclosure');
        }

        $this->update([
            'disclosure_data' => $data,
            'completion_percentage' => $this->calculateCompletionPercentage($data),
            'last_modified_at' => now(),
            'last_modified_by' => $userId,
            'last_modified_ip' => request()->ip(),
            'last_modified_user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Calculate completion percentage based on module schema
     *
     * @param array $data Disclosure data
     * @return int Percentage (0-100)
     */
    protected function calculateCompletionPercentage(array $data): int
    {
        return $this->disclosureModule->calculateCompletionPercentage($data);
    }

    /**
     * Check if disclosure has pending clarifications
     *
     * @return bool
     */
    public function hasPendingClarifications(): bool
    {
        return $this->clarifications()
            ->where('status', 'open')
            ->exists();
    }

    /**
     * Check if all clarifications are answered
     *
     * @return bool
     */
    public function allClarificationsAnswered(): bool
    {
        $total = $this->clarifications()->count();
        $answered = $this->clarifications()
            ->whereIn('status', ['answered', 'accepted'])
            ->count();

        return $total > 0 && $total === $answered;
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get human-readable status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted for Review',
            'under_review' => 'Under Review',
            'clarification_required' => 'Clarification Required',
            'resubmitted' => 'Resubmitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Check if disclosure is editable
     */
    public function getIsEditableAttribute(): bool
    {
        return !$this->is_locked && in_array($this->status, ['draft', 'rejected']);
    }
}
