<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PHASE 1 - MODEL 5/5: DisclosureApproval
 * 
 * PURPOSE:
 * Represents workflow tracking for disclosure approval processes.
 * Records every approval request, decision, and state change for
 * regulatory audit and compliance reporting.
 * 
 * KEY RESPONSIBILITIES:
 * - Track approval lifecycle (pending → approved/rejected)
 * - Record admin review timeline (SLA tracking)
 * - Support revocation of approvals (emergency override)
 * - Link to approved disclosure versions
 * - Track clarification workflow integration
 * 
 * WORKFLOW EXAMPLE:
 * 1. Company submits disclosure (request_type: initial_submission, status: pending)
 * 2. Admin requests clarifications (status: clarification_required)
 * 3. Company resubmits (new approval record: request_type: resubmission)
 * 4. Admin approves (status: approved, links to disclosure_version)
 * 5. Later discovers issue, revokes (is_revoked: true)
 *
 * @property int $id
 * @property int $company_disclosure_id Disclosure being approved
 * @property int $company_id Denormalized company ID
 * @property int $disclosure_module_id Denormalized module ID
 * @property string $request_type Type: initial_submission, resubmission, etc.
 * @property int $requested_by CompanyUser who requested
 * @property \Illuminate\Support\Carbon $requested_at
 * @property string|null $submission_notes Company notes
 * @property int $disclosure_version_number Version number at request time
 * @property int|null $disclosure_version_id Approved version (if approved)
 * @property string $status Current status
 * @property int|null $reviewed_by Admin who reviewed
 * @property \Illuminate\Support\Carbon|null $review_started_at
 * @property \Illuminate\Support\Carbon|null $review_completed_at
 * @property int|null $review_duration_minutes How long review took
 * @property string|null $decision_notes Admin explanation
 * @property array|null $checklist_completed Approval checklist
 * @property array|null $identified_issues Issues found
 * @property int $clarifications_requested Number of clarifications
 * @property \Illuminate\Support\Carbon|null $clarifications_due_date
 * @property bool $all_clarifications_answered
 * @property array|null $approval_conditions Conditional approval terms
 * @property \Illuminate\Support\Carbon|null $conditional_approval_expires_at
 * @property bool $is_revoked Whether approval revoked
 * @property int|null $revoked_by Admin who revoked
 * @property \Illuminate\Support\Carbon|null $revoked_at
 * @property string|null $revocation_reason Required reason
 * @property bool $investor_notification_required
 * @property \Illuminate\Support\Carbon|null $sla_due_date SLA deadline
 * @property bool $sla_breached Whether SLA breached
 * @property int|null $business_days_to_review Business days metric
 * @property string|null $sebi_compliance_status SEBI compliance flag
 * @property int $approval_stage Stage number (multi-approver support)
 * @property array|null $approval_chain Multi-approver chain
 * @property string|null $internal_notes Admin-only notes
 * @property int $reminder_count Reminders sent to admin
 * @property \Illuminate\Support\Carbon|null $last_reminder_at
 * @property string|null $requested_by_ip
 * @property string|null $reviewed_by_ip
 * @property string|null $requested_by_user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @mixin IdeHelperDisclosureApproval
 */
class DisclosureApproval extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'disclosure_approvals';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_disclosure_id',
        'company_id',
        'disclosure_module_id',
        'request_type',
        'requested_by',
        'requested_at',
        'submission_notes',
        'disclosure_version_number',
        'disclosure_version_id',
        'status',
        'reviewed_by',
        'review_started_at',
        'review_completed_at',
        'review_duration_minutes',
        'decision_notes',
        'checklist_completed',
        'identified_issues',
        'clarifications_requested',
        'clarifications_due_date',
        'all_clarifications_answered',
        'approval_conditions',
        'conditional_approval_expires_at',
        'is_revoked',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'investor_notification_required',
        'sla_due_date',
        'sla_breached',
        'business_days_to_review',
        'sebi_compliance_status',
        'approval_stage',
        'approval_chain',
        'internal_notes',
        'reminder_count',
        'last_reminder_at',
        'requested_by_ip',
        'reviewed_by_ip',
        'requested_by_user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'requested_at' => 'datetime',
        'disclosure_version_number' => 'integer',
        'review_started_at' => 'datetime',
        'review_completed_at' => 'datetime',
        'review_duration_minutes' => 'integer',
        'checklist_completed' => 'array',
        'identified_issues' => 'array',
        'clarifications_requested' => 'integer',
        'clarifications_due_date' => 'datetime',
        'all_clarifications_answered' => 'boolean',
        'approval_conditions' => 'array',
        'conditional_approval_expires_at' => 'datetime',
        'is_revoked' => 'boolean',
        'revoked_at' => 'datetime',
        'investor_notification_required' => 'boolean',
        'sla_due_date' => 'datetime',
        'sla_breached' => 'boolean',
        'business_days_to_review' => 'integer',
        'approval_stage' => 'integer',
        'approval_chain' => 'array',
        'reminder_count' => 'integer',
        'last_reminder_at' => 'datetime',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    /**
     * Disclosure being approved
     */
    public function companyDisclosure()
    {
        return $this->belongsTo(CompanyDisclosure::class);
    }

    /**
     * Company that owns the disclosure
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Disclosure module
     */
    public function disclosureModule()
    {
        return $this->belongsTo(DisclosureModule::class);
    }

    /**
     * CompanyUser who requested approval
     */
    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Admin who reviewed
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Admin who revoked
     */
    public function revoker()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Approved disclosure version (if approved)
     */
    public function disclosureVersion()
    {
        return $this->belongsTo(DisclosureVersion::class, 'disclosure_version_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope to approvals by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to pending approvals
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to under review approvals
     */
    public function scopeUnderReview($query)
    {
        return $query->where('status', 'under_review');
    }

    /**
     * Scope to approved approvals
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to rejected approvals
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to revoked approvals
     */
    public function scopeRevoked($query)
    {
        return $query->where('is_revoked', true);
    }

    /**
     * Scope to SLA breached approvals
     */
    public function scopeSlaBreached($query)
    {
        return $query->where('sla_breached', true);
    }

    /**
     * Scope to overdue approvals (SLA due date passed)
     */
    public function scopeOverdue($query)
    {
        return $query->where('sla_due_date', '<', now())
            ->whereIn('status', ['pending', 'under_review']);
    }

    /**
     * Scope to approvals by reviewer
     */
    public function scopeByReviewer($query, int $adminId)
    {
        return $query->where('reviewed_by', $adminId);
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Start admin review
     *
     * @param int $adminId Admin user ID
     * @throws \RuntimeException If not pending
     */
    public function startReview(int $adminId): void
    {
        if ($this->status !== 'pending') {
            throw new \RuntimeException('Can only start review on pending approvals');
        }

        $this->update([
            'status' => 'under_review',
            'reviewed_by' => $adminId,
            'review_started_at' => now(),
        ]);
    }

    /**
     * Complete review with approval
     *
     * @param int $adminId Admin user ID
     * @param int $versionId Approved version ID
     * @param array|null $checklist Checklist items
     * @param string|null $notes Admin notes
     * @throws \RuntimeException If not under review
     */
    public function approve(
        int $adminId,
        int $versionId,
        ?array $checklist = null,
        ?string $notes = null
    ): void {
        if (!in_array($this->status, ['pending', 'under_review', 'clarification_required'])) {
            throw new \RuntimeException('Invalid status for approval');
        }

        $reviewDuration = $this->review_started_at
            ? $this->review_started_at->diffInMinutes(now())
            : null;

        $businessDays = $this->calculateBusinessDays($this->requested_at, now());

        $this->update([
            'status' => 'approved',
            'reviewed_by' => $adminId,
            'review_completed_at' => now(),
            'review_duration_minutes' => $reviewDuration,
            'disclosure_version_id' => $versionId,
            'decision_notes' => $notes,
            'checklist_completed' => $checklist,
            'business_days_to_review' => $businessDays,
            'sla_breached' => $this->sla_due_date && now()->gt($this->sla_due_date),
            'reviewed_by_ip' => request()->ip(),
        ]);
    }

    /**
     * Complete review with rejection
     *
     * @param int $adminId Admin user ID
     * @param string $reason Rejection reason
     * @param array|null $issues Identified issues
     * @throws \RuntimeException If not under review
     */
    public function reject(int $adminId, string $reason, ?array $issues = null): void
    {
        if (!in_array($this->status, ['pending', 'under_review'])) {
            throw new \RuntimeException('Invalid status for rejection');
        }

        $reviewDuration = $this->review_started_at
            ? $this->review_started_at->diffInMinutes(now())
            : null;

        $businessDays = $this->calculateBusinessDays($this->requested_at, now());

        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $adminId,
            'review_completed_at' => now(),
            'review_duration_minutes' => $reviewDuration,
            'decision_notes' => $reason,
            'identified_issues' => $issues,
            'business_days_to_review' => $businessDays,
            'sla_breached' => $this->sla_due_date && now()->gt($this->sla_due_date),
            'reviewed_by_ip' => request()->ip(),
        ]);
    }

    /**
     * Request clarifications
     *
     * @param int $adminId Admin user ID
     * @param int $clarificationCount Number of clarifications
     * @param \Carbon\Carbon|null $dueDate Deadline for answers
     */
    public function requestClarifications(
        int $adminId,
        int $clarificationCount,
        ?\Carbon\Carbon $dueDate = null
    ): void {
        $this->update([
            'status' => 'clarification_required',
            'reviewed_by' => $adminId,
            'clarifications_requested' => $clarificationCount,
            'clarifications_due_date' => $dueDate ?? now()->addDays(7),
            'all_clarifications_answered' => false,
        ]);
    }

    /**
     * Mark all clarifications as answered
     */
    public function markClarificationsAnswered(): void
    {
        $this->update([
            'all_clarifications_answered' => true,
            'status' => 'pending', // Move back to pending for re-review
        ]);
    }

    /**
     * Revoke approval (emergency override)
     *
     * @param int $adminId Admin user ID
     * @param string $reason REQUIRED revocation reason
     * @param bool $notifyInvestors Whether to notify investors
     * @throws \RuntimeException If not approved or already revoked
     */
    public function revoke(int $adminId, string $reason, bool $notifyInvestors = false): void
    {
        if ($this->status !== 'approved') {
            throw new \RuntimeException('Can only revoke approved approvals');
        }

        if ($this->is_revoked) {
            throw new \RuntimeException('Approval already revoked');
        }

        if (empty($reason)) {
            throw new \RuntimeException('Revocation reason is required for regulatory compliance');
        }

        $this->update([
            'is_revoked' => true,
            'revoked_by' => $adminId,
            'revoked_at' => now(),
            'revocation_reason' => $reason,
            'investor_notification_required' => $notifyInvestors,
        ]);
    }

    /**
     * Calculate business days between two dates
     *
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return int
     */
    protected function calculateBusinessDays(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        $businessDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            // Count weekdays (Monday=1 to Friday=5)
            if ($current->isWeekday()) {
                $businessDays++;
            }
            $current->addDay();
        }

        return $businessDays;
    }

    /**
     * Check if SLA is at risk (75% of time elapsed)
     *
     * @return bool
     */
    public function isSlaAtRisk(): bool
    {
        if (!$this->sla_due_date || !in_array($this->status, ['pending', 'under_review'])) {
            return false;
        }

        $totalTime = $this->requested_at->diffInMinutes($this->sla_due_date);
        $elapsedTime = $this->requested_at->diffInMinutes(now());

        return ($elapsedTime / $totalTime) >= 0.75;
    }

    /**
     * Get hours until SLA deadline
     *
     * @return int|null
     */
    public function getHoursUntilSla(): ?int
    {
        if (!$this->sla_due_date) {
            return null;
        }

        return (int) now()->diffInHours($this->sla_due_date, false);
    }

    /**
     * Send reminder to admin
     */
    public function sendReminderToAdmin(): void
    {
        // TODO: Queue email notification to admin

        $this->increment('reminder_count');
        $this->update(['last_reminder_at' => now()]);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Get human-readable status label
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->is_revoked) {
            return 'Approved (Revoked)';
        }

        return match($this->status) {
            'pending' => 'Pending Review',
            'under_review' => 'Under Review',
            'clarification_required' => 'Clarification Required',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get human-readable request type label
     */
    public function getRequestTypeLabelAttribute(): string
    {
        return match($this->request_type) {
            'initial_submission' => 'Initial Submission',
            'resubmission' => 'Resubmission',
            'revision' => 'Revision',
            'correction' => 'Correction',
            default => ucfirst($this->request_type),
        };
    }

    /**
     * Check if approval is in progress
     */
    public function getIsInProgressAttribute(): bool
    {
        return in_array($this->status, ['pending', 'under_review', 'clarification_required']);
    }

    /**
     * Check if approval is complete
     */
    public function getIsCompleteAttribute(): bool
    {
        return in_array($this->status, ['approved', 'rejected']);
    }
}
