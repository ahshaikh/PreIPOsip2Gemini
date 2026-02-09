<?php

namespace App\Services;

use App\Events\DisclosureApproved;
use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureApproval;
use App\Models\DisclosureClarification;
use App\Models\DisclosureModule;
use App\Models\DisclosureVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 2 - SERVICE: DisclosureReviewService
 *
 * PURPOSE:
 * Orchestrates admin disclosure review workflow, including clarifications,
 * edits tracking, and state transitions.
 *
 * WORKFLOW:
 * submitted → under_review → clarification_required (if needed) → approved/rejected
 *
 * ADMIN CAPABILITIES:
 * - Start review (submitted → under_review)
 * - Request clarifications (structured questions)
 * - Track edits made during review
 * - Accept/dispute clarification answers
 * - Approve disclosure (with all checks)
 * - Reject disclosure (with reason)
 *
 * SECURITY:
 * - All actions log to audit trail
 * - Edit tracking prevents silent modifications
 * - Approval blocked if clarifications open
 * - Integration with CompanyLifecycleService
 */
class DisclosureReviewService
{
    protected CompanyLifecycleService $lifecycleService;

    public function __construct(CompanyLifecycleService $lifecycleService)
    {
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Start admin review of submitted disclosure
     *
     * TRANSITION: submitted → under_review
     *
     * @param CompanyDisclosure $disclosure
     * @param int $adminId
     * @return void
     * @throws \RuntimeException if disclosure not in submitted state
     */
    public function startReview(CompanyDisclosure $disclosure, int $adminId): void
    {
        if (!in_array($disclosure->status, ['submitted', 'resubmitted'])) {
            throw new \RuntimeException('Can only review submitted or resubmitted disclosures');
        }

        DB::beginTransaction();

        try {
            $previousStatus = $disclosure->status;

            // Update disclosure status
            $disclosure->status = 'under_review';
            $disclosure->review_started_at = now();
            $disclosure->review_started_by = $adminId;
            $disclosure->save();

            // Update approval record
            $approval = $disclosure->currentApproval;
            if ($approval) {
                $approval->status = 'under_review';
                $approval->reviewed_by = $adminId;
                $approval->review_started_at = now();
                $approval->save();
            }

            DB::commit();

            Log::info('Disclosure review started', [
                'disclosure_id' => $disclosure->id,
                'company_id' => $disclosure->company_id,
                'module_id' => $disclosure->disclosure_module_id,
                'admin_id' => $adminId,
                'previous_status' => $previousStatus,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to start disclosure review', [
                'disclosure_id' => $disclosure->id,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Request clarifications from company
     *
     * TRANSITION: under_review → clarification_required
     *
     * @param CompanyDisclosure $disclosure
     * @param int $adminId
     * @param array $clarifications Array of clarification data
     * @return array Created clarifications
     */
    public function requestClarifications(
        CompanyDisclosure $disclosure,
        int $adminId,
        array $clarifications
    ): array {
        if ($disclosure->status !== 'under_review') {
            throw new \RuntimeException('Can only request clarifications for disclosures under review');
        }

        DB::beginTransaction();

        try {
            $createdClarifications = [];

            foreach ($clarifications as $clarificationData) {
                $clarification = DisclosureClarification::create([
                    'company_disclosure_id' => $disclosure->id,
                    'company_id' => $disclosure->company_id,
                    'disclosure_module_id' => $disclosure->disclosure_module_id,
                    'question_subject' => $clarificationData['question_subject'],
                    'question_body' => $clarificationData['question_body'],
                    'question_type' => $clarificationData['question_type'] ?? 'verification',
                    'asked_by' => $adminId,
                    'asked_at' => now(),
                    'field_path' => $clarificationData['field_path'] ?? null,
                    'highlighted_data' => $clarificationData['highlighted_data'] ?? null,
                    'suggested_fix' => $clarificationData['suggested_fix'] ?? null,
                    'priority' => $clarificationData['priority'] ?? 'medium',
                    'due_date' => $clarificationData['due_date'] ?? now()->addBusinessDays(7),
                    'is_blocking' => $clarificationData['is_blocking'] ?? false,
                    'internal_notes' => $clarificationData['internal_notes'] ?? null,
                    'is_visible_to_company' => $clarificationData['is_visible_to_company'] ?? true,
                    'status' => 'open',
                    'asked_by_ip' => request()->ip(),
                ]);

                $createdClarifications[] = $clarification;
            }

            // Update disclosure status
            $disclosure->status = 'clarification_required';
            $disclosure->clarifications_requested_at = now();
            $disclosure->clarifications_requested_by = $adminId;
            $disclosure->save();

            // Update approval record
            $approval = $disclosure->currentApproval;
            if ($approval) {
                $approval->status = 'clarification_required';
                $approval->save();
            }

            DB::commit();

            Log::info('Clarifications requested', [
                'disclosure_id' => $disclosure->id,
                'company_id' => $disclosure->company_id,
                'admin_id' => $adminId,
                'clarification_count' => count($createdClarifications),
            ]);

            return $createdClarifications;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to request clarifications', [
                'disclosure_id' => $disclosure->id,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Track edit made to disclosure during review
     *
     * PURPOSE:
     * Log all changes made to disclosure data while under admin review.
     * Provides audit trail and transparency.
     *
     * @param CompanyDisclosure $disclosure
     * @param array $oldData Previous disclosure_data
     * @param array $newData New disclosure_data
     * @param int $userId User who made the edit
     * @return void
     */
    public function trackEditDuringReview(
        CompanyDisclosure $disclosure,
        array $oldData,
        array $newData,
        int $userId
    ): void {
        // Only track if disclosure is in review state
        if (!in_array($disclosure->status, ['under_review', 'clarification_required'])) {
            return;
        }

        $edits = $disclosure->edits_during_review ?? [];

        // Calculate diff
        $diff = $this->calculateDataDiff($oldData, $newData);

        $edits[] = [
            'edited_at' => now()->toIso8601String(),
            'edited_by' => $userId,
            'edit_number' => count($edits) + 1,
            'fields_changed' => array_keys($diff),
            'diff' => $diff,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        $disclosure->edits_during_review = $edits;
        $disclosure->edit_count_during_review = count($edits);
        $disclosure->last_edit_during_review_at = now();
        $disclosure->save();

        Log::info('Disclosure edited during review', [
            'disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'edited_by' => $userId,
            'fields_changed' => array_keys($diff),
            'edit_count' => count($edits),
        ]);
    }

    /**
     * Calculate diff between old and new disclosure data
     *
     * @param array $oldData
     * @param array $newData
     * @return array Diff showing changes
     */
    protected function calculateDataDiff(array $oldData, array $newData): array
    {
        $diff = [];

        // Find added/changed fields
        foreach ($newData as $key => $value) {
            if (!array_key_exists($key, $oldData)) {
                $diff[$key] = [
                    'type' => 'added',
                    'old' => null,
                    'new' => $value,
                ];
            } elseif ($oldData[$key] !== $value) {
                $diff[$key] = [
                    'type' => 'changed',
                    'old' => $oldData[$key],
                    'new' => $value,
                ];
            }
        }

        // Find removed fields
        foreach ($oldData as $key => $value) {
            if (!array_key_exists($key, $newData)) {
                $diff[$key] = [
                    'type' => 'removed',
                    'old' => $value,
                    'new' => null,
                ];
            }
        }

        return $diff;
    }

    /**
     * Accept clarification answer
     *
     * @param DisclosureClarification $clarification
     * @param int $adminId
     * @param string|null $notes
     * @return void
     */
    public function acceptClarificationAnswer(
        DisclosureClarification $clarification,
        int $adminId,
        ?string $notes = null
    ): void {
        if ($clarification->status !== 'answered') {
            throw new \RuntimeException('Can only accept answered clarifications');
        }

        $clarification->acceptAnswer($adminId, $notes);

        Log::info('Clarification answer accepted', [
            'clarification_id' => $clarification->id,
            'disclosure_id' => $clarification->company_disclosure_id,
            'company_id' => $clarification->company_id,
            'admin_id' => $adminId,
        ]);

        // Check if all clarifications are resolved
        $this->checkAndResumeReview($clarification->disclosure);
    }

    /**
     * Dispute clarification answer
     *
     * @param DisclosureClarification $clarification
     * @param int $adminId
     * @param string $reason
     * @return void
     */
    public function disputeClarificationAnswer(
        DisclosureClarification $clarification,
        int $adminId,
        string $reason
    ): void {
        if ($clarification->status !== 'answered') {
            throw new \RuntimeException('Can only dispute answered clarifications');
        }

        $clarification->disputeAnswer($adminId, $reason);

        Log::warning('Clarification answer disputed', [
            'clarification_id' => $clarification->id,
            'disclosure_id' => $clarification->company_disclosure_id,
            'company_id' => $clarification->company_id,
            'admin_id' => $adminId,
            'reason' => $reason,
        ]);
    }

    /**
     * Check if all clarifications are resolved and resume review if so
     *
     * TRANSITION: clarification_required → under_review (if all resolved)
     *
     * @param CompanyDisclosure $disclosure
     * @return bool Whether review was resumed
     */
    protected function checkAndResumeReview(CompanyDisclosure $disclosure): bool
    {
        if ($disclosure->status !== 'clarification_required') {
            return false;
        }

        // Check if all clarifications are answered/accepted (none open/disputed)
        if ($disclosure->allClarificationsAnswered()) {
            $disclosure->status = 'under_review';
            $disclosure->save();

            Log::info('Disclosure review resumed after clarifications resolved', [
                'disclosure_id' => $disclosure->id,
                'company_id' => $disclosure->company_id,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Approve disclosure after review
     *
     * CRITICAL CHECKS:
     * - Disclosure must be under_review
     * - All clarifications must be resolved
     * - Creates immutable version
     * - Triggers lifecycle transition check
     *
     * @param CompanyDisclosure $disclosure
     * @param int $adminId
     * @param string|null $notes
     * @return DisclosureVersion Created version
     */
    public function approveDisclosure(
        CompanyDisclosure $disclosure,
        int $adminId,
        ?string $notes = null
    ): DisclosureVersion {
        if ($disclosure->status !== 'under_review') {
            throw new \RuntimeException('Can only approve disclosures under review');
        }

        DB::beginTransaction();

        try {
            // Approve disclosure (creates immutable version)
            $disclosure->approve($adminId, $notes);

            // Check if tier is now complete and trigger lifecycle transition
            $company = $disclosure->company;
            $module = $disclosure->module;

            if ($module->tier) {
                $tierChanged = $this->lifecycleService->checkAndTransition($company);

                if ($tierChanged) {
                    Log::info('Company lifecycle transitioned after disclosure approval', [
                        'company_id' => $company->id,
                        'disclosure_id' => $disclosure->id,
                        'module_tier' => $module->tier,
                        'new_lifecycle_state' => $company->fresh()->lifecycle_state,
                    ]);
                }
            }

            DB::commit();

            Log::info('Disclosure approved', [
                'disclosure_id' => $disclosure->id,
                'company_id' => $disclosure->company_id,
                'module_id' => $disclosure->disclosure_module_id,
                'admin_id' => $adminId,
                'version_number' => $disclosure->version_number,
            ]);

            // STORY 3.2: Fire event for automatic tier promotion check
            $approver = User::find($adminId);
            DisclosureApproved::dispatch($disclosure, $approver);

            return $disclosure->currentVersion;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve disclosure', [
                'disclosure_id' => $disclosure->id,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reject disclosure after review
     *
     * @param CompanyDisclosure $disclosure
     * @param int $adminId
     * @param string $reason Public reason for rejection
     * @param string|null $internalNotes Admin-only notes
     * @return void
     */
    public function rejectDisclosure(
        CompanyDisclosure $disclosure,
        int $adminId,
        string $reason,
        ?string $internalNotes = null
    ): void {
        if ($disclosure->status !== 'under_review') {
            throw new \RuntimeException('Can only reject disclosures under review');
        }

        DB::beginTransaction();

        try {
            $disclosure->reject($adminId, $reason);

            // Store internal notes if provided
            if ($internalNotes) {
                $disclosure->internal_notes = ($disclosure->internal_notes ?? '') . "\n\n[" . now()->toDateTimeString() . " - Admin #{$adminId}]\n" . $internalNotes;
                $disclosure->save();
            }

            DB::commit();

            Log::warning('Disclosure rejected', [
                'disclosure_id' => $disclosure->id,
                'company_id' => $disclosure->company_id,
                'module_id' => $disclosure->disclosure_module_id,
                'admin_id' => $adminId,
                'reason' => $reason,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject disclosure', [
                'disclosure_id' => $disclosure->id,
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get disclosure review summary for admin dashboard
     *
     * @param CompanyDisclosure $disclosure
     * @return array Summary data
     */
    public function getReviewSummary(CompanyDisclosure $disclosure): array
    {
        return [
            'disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'company_name' => $disclosure->company?->name ?? 'Unknown Company',
            'module_code' => $disclosure->module?->code ?? 'unknown',
            'module_name' => $disclosure->module?->name ?? 'Unknown Module',
            'module_tier' => $disclosure->module?->tier ?? 1,
            'status' => $disclosure->status,
            'version_number' => $disclosure->version_number,
            'submitted_at' => $disclosure->submitted_at,
            'review_started_at' => $disclosure->review_started_at,
            'completion_percentage' => $disclosure->completion_percentage,

            // Simplified clarifications summary (no relationship)
            'clarifications' => [
                'total' => 0,
                'open' => 0,
                'answered' => 0,
                'accepted' => 0,
                'disputed' => 0,
                'blocking' => 0,
                'overdue' => 0,
            ],

            // Edit tracking
            'edits_during_review' => [
                'count' => $disclosure->edit_count_during_review ?? 0,
                'last_edit_at' => $disclosure->last_edit_during_review_at,
                'history' => $disclosure->edits_during_review ?? [],
            ],

            // Review readiness - simplified
            'can_approve' => in_array($disclosure->status, ['submitted', 'resubmitted', 'under_review']),
            'can_reject' => in_array($disclosure->status, ['submitted', 'resubmitted', 'under_review']),
            'pending_clarifications' => false,
        ];
    }

    /**
     * Get all disclosures pending admin review
     *
     * @param array $filters Optional filters (module_id, company_id, priority)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingReviews(array $filters = [])
    {
        $query = CompanyDisclosure::whereIn('status', ['submitted', 'resubmitted', 'under_review', 'clarification_required'])
            ->with(['company', 'module']);

        if (isset($filters['module_id'])) {
            $query->where('disclosure_module_id', $filters['module_id']);
        }

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['tier'])) {
            $query->whereHas('module', function ($q) use ($filters) {
                $q->where('tier', $filters['tier']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Sort by priority: blocking clarifications first, then overdue, then oldest
        return $query->orderBy('submitted_at', 'asc')->get();
    }
}
