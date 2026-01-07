<?php
/**
 * FIX 49: Deal Approval Workflow
 *
 * Tracks the multi-step approval process for investment deals.
 *
 * Workflow stages:
 * 1. draft → Deal created but not submitted for review
 * 2. pending_review → Submitted for compliance review
 * 3. under_review → Being actively reviewed by compliance team
 * 4. approved → Approved and ready to publish
 * 5. rejected → Rejected with feedback
 * 6. published → Live and available to investors
 *
 * Why this matters:
 * - Regulatory compliance (SEBI/regulatory approval trail)
 * - Risk management (multi-level approval gates)
 * - Audit trail (who approved, when, why)
 * - Investor protection (ensures only vetted deals go live)
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealApproval extends Model
{
    protected $fillable = [
        'deal_id',
        'status',               // draft, pending_review, under_review, approved, rejected, published
        'submitted_by',         // User who submitted for approval
        'reviewed_by',          // User conducting the review
        'approved_by',          // User who gave final approval
        'rejected_by',          // User who rejected
        'submitted_at',
        'review_started_at',
        'approved_at',
        'rejected_at',
        'published_at',
        'rejection_reason',     // Why the deal was rejected
        'approval_notes',       // Notes from approver
        'compliance_checklist', // JSON checklist of compliance items
        'risk_assessment',      // JSON risk assessment data
        'reviewer_comments',    // Comments from reviewer
        'required_approvals',   // Number of approvals needed
        'current_approvals',    // Number of approvals received
        'metadata',             // Additional context
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'review_started_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'published_at' => 'datetime',
        'compliance_checklist' => 'array',
        'risk_assessment' => 'array',
        'metadata' => 'array',
    ];

    /**
     * State transitions are tracked, cannot modify past approvals
     */
    protected static function booted()
    {
        static::creating(function ($approval) {
            // Set default status if not provided
            if (!$approval->status) {
                $approval->status = 'draft';
            }
        });

        static::updating(function ($approval) {
            // Validate state transitions
            $validTransitions = [
                'draft' => ['pending_review'],
                'pending_review' => ['under_review', 'rejected'],
                'under_review' => ['approved', 'rejected', 'pending_review'],
                'approved' => ['published', 'rejected'],
                'rejected' => ['pending_review'], // Can resubmit after fixing issues
                'published' => [], // Terminal state
            ];

            if ($approval->isDirty('status')) {
                $oldStatus = $approval->getOriginal('status');
                $newStatus = $approval->status;

                if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
                    throw new \RuntimeException(
                        "Invalid status transition from '{$oldStatus}' to '{$newStatus}'. " .
                        "Allowed transitions: " . implode(', ', $validTransitions[$oldStatus] ?? ['none'])
                    );
                }

                \Log::info('Deal approval status changed', [
                    'approval_id' => $approval->id,
                    'deal_id' => $approval->deal_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'user_id' => auth()->id(),
                ]);
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // --- WORKFLOW METHODS ---

    /**
     * Submit deal for review
     */
    public function submit(?string $notes = null): void
    {
        $this->update([
            'status' => 'pending_review',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], [
                'submission_notes' => $notes,
                'submitted_ip' => request()->ip(),
            ]),
        ]);

        \Log::info('Deal submitted for approval', [
            'approval_id' => $this->id,
            'deal_id' => $this->deal_id,
        ]);
    }

    /**
     * Start review process
     */
    public function startReview(): void
    {
        $this->update([
            'status' => 'under_review',
            'reviewed_by' => auth()->id(),
            'review_started_at' => now(),
        ]);

        \Log::info('Deal review started', [
            'approval_id' => $this->id,
            'deal_id' => $this->deal_id,
            'reviewer_id' => auth()->id(),
        ]);
    }

    /**
     * Approve the deal
     */
    public function approve(array $complianceChecklist, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $notes,
            'compliance_checklist' => $complianceChecklist,
            'current_approvals' => ($this->current_approvals ?? 0) + 1,
        ]);

        \Log::info('Deal approved', [
            'approval_id' => $this->id,
            'deal_id' => $this->deal_id,
            'approved_by' => auth()->id(),
        ]);

        // Create company approval snapshot if not already created
        $deal = $this->deal;
        if ($deal && $deal->company) {
            try {
                $deal->company->createApprovalSnapshot($this->id, 'deal');
            } catch (\Exception $e) {
                \Log::error('Failed to create company approval snapshot', [
                    'deal_id' => $deal->id,
                    'company_id' => $deal->company_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Reject the deal
     */
    public function reject(string $reason, ?array $feedback = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'reviewer_comments' => $feedback,
        ]);

        \Log::warning('Deal rejected', [
            'approval_id' => $this->id,
            'deal_id' => $this->deal_id,
            'rejected_by' => auth()->id(),
            'reason' => $reason,
        ]);
    }

    /**
     * Publish the approved deal
     */
    public function publish(): void
    {
        if ($this->status !== 'approved') {
            throw new \RuntimeException('Only approved deals can be published');
        }

        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        // Update deal status to active
        $this->deal->update(['status' => 'active']);

        \Log::info('Deal published', [
            'approval_id' => $this->id,
            'deal_id' => $this->deal_id,
        ]);
    }

    // --- HELPER METHODS ---

    /**
     * Check if approval is pending
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['draft', 'pending_review', 'under_review']);
    }

    /**
     * Check if approval is complete
     */
    public function isComplete(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'published']);
    }

    /**
     * Check if deal can be approved
     */
    public function canApprove(): bool
    {
        return $this->status === 'under_review';
    }

    /**
     * Check if deal can be rejected
     */
    public function canReject(): bool
    {
        return in_array($this->status, ['pending_review', 'under_review']);
    }

    /**
     * Check if deal can be published
     */
    public function canPublish(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Get approval duration in days
     */
    public function getApprovalDuration(): ?int
    {
        if (!$this->submitted_at || !$this->approved_at) {
            return null;
        }

        return $this->submitted_at->diffInDays($this->approved_at);
    }

    /**
     * Get review duration in days
     */
    public function getReviewDuration(): ?int
    {
        if (!$this->review_started_at) {
            return null;
        }

        $endTime = $this->approved_at ?? $this->rejected_at ?? now();
        return $this->review_started_at->diffInDays($endTime);
    }

    /**
     * Check if approval is overdue (based on SLA)
     */
    public function isOverdue(int $slaDays = 7): bool
    {
        if (!$this->submitted_at || $this->isComplete()) {
            return false;
        }

        return $this->submitted_at->addDays($slaDays)->isPast();
    }
}
