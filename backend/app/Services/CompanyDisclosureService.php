<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDisclosure;
use App\Models\DisclosureClarification;
use App\Models\DisclosureModule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 3 - SERVICE: CompanyDisclosureService
 *
 * PURPOSE:
 * Manages issuer-side disclosure submission workflows with transparency and auditability.
 *
 * CORE PRINCIPLES:
 * 1. NEVER allow silent edits - all changes logged
 * 2. Treat issuer honesty as first-class signal
 * 3. Correction-friendly (errors can be reported without penalty)
 * 4. Draft-friendly (save anytime, submit when ready)
 * 5. Clear next-actions (no confusion about what to do)
 *
 * WORKFLOWS:
 * - Draft creation and editing
 * - Structured field submission
 * - Document attachment
 * - Submit for review
 * - Answer clarifications
 * - Report errors in approved disclosures
 *
 * SAFEGUARDS:
 * - Approved data cannot be silently edited
 * - All edits logged with actor, timestamp, reason
 * - Error reports create new version, don't overwrite
 * - Clarification answers are threaded, versioned
 */
class CompanyDisclosureService
{
    /**
     * Get issuer dashboard summary
     *
     * SHOWS:
     * - Progress by tier (% complete)
     * - Blockers (rejected, clarifications needed)
     * - Next actions
     * - Status visibility
     *
     * @param Company $company
     * @return array Dashboard data
     */
    public function getDashboardSummary(Company $company): array
    {
        $modules = DisclosureModule::where('is_active', true)
            ->orderBy('tier', 'asc')
            ->orderBy('display_order', 'asc')
            ->get();

        $disclosures = $company->disclosures()
            ->with(['module', 'clarifications'])
            ->get()
            ->keyBy('disclosure_module_id');

        $tierProgress = [];
        $blockers = [];
        $nextActions = [];

        foreach ([1, 2, 3] as $tier) {
            $tierModules = $modules->where('tier', $tier);
            $requiredModules = $tierModules->where('is_required', true);

            $completed = 0;
            $total = $requiredModules->count();

            foreach ($tierModules as $module) {
                $disclosure = $disclosures->get($module->id);

                if (!$disclosure) {
                    // Not started
                    if ($module->is_required) {
                        $nextActions[] = [
                            'type' => 'start_disclosure',
                            'priority' => $tier === 1 ? 'high' : 'medium',
                            'module_id' => $module->id,
                            'module_name' => $module->name,
                            'tier' => $tier,
                            'message' => "Start {$module->name} disclosure",
                        ];
                    }
                    continue;
                }

                // Check status
                if ($disclosure->status === 'approved') {
                    if ($module->is_required) {
                        $completed++;
                    }
                } elseif ($disclosure->status === 'rejected') {
                    $blockers[] = [
                        'type' => 'rejected',
                        'severity' => 'high',
                        'disclosure_id' => $disclosure->id,
                        'module_name' => $module->name,
                        'reason' => $disclosure->rejection_reason,
                        'rejected_at' => $disclosure->rejected_at,
                    ];

                    $nextActions[] = [
                        'type' => 'fix_rejected',
                        'priority' => 'high',
                        'disclosure_id' => $disclosure->id,
                        'module_name' => $module->name,
                        'message' => "Fix rejected disclosure: {$module->name}",
                    ];
                } elseif ($disclosure->status === 'clarification_required') {
                    $openClarifications = $disclosure->clarifications()
                        ->whereIn('status', ['open', 'disputed'])
                        ->count();

                    if ($openClarifications > 0) {
                        $blockers[] = [
                            'type' => 'clarifications_needed',
                            'severity' => 'medium',
                            'disclosure_id' => $disclosure->id,
                            'module_name' => $module->name,
                            'clarification_count' => $openClarifications,
                        ];

                        $nextActions[] = [
                            'type' => 'answer_clarifications',
                            'priority' => 'high',
                            'disclosure_id' => $disclosure->id,
                            'module_name' => $module->name,
                            'count' => $openClarifications,
                            'message' => "Answer {$openClarifications} clarification(s) for {$module->name}",
                        ];
                    }
                } elseif ($disclosure->status === 'draft') {
                    if ($disclosure->completion_percentage < 100) {
                        $nextActions[] = [
                            'type' => 'complete_draft',
                            'priority' => $tier === 1 ? 'high' : 'medium',
                            'disclosure_id' => $disclosure->id,
                            'module_name' => $module->name,
                            'completion' => $disclosure->completion_percentage,
                            'message' => "Complete {$module->name} ({$disclosure->completion_percentage}% done)",
                        ];
                    } else {
                        $nextActions[] = [
                            'type' => 'submit_for_review',
                            'priority' => 'high',
                            'disclosure_id' => $disclosure->id,
                            'module_name' => $module->name,
                            'message' => "Submit {$module->name} for review",
                        ];
                    }
                }
            }

            $tierProgress[$tier] = [
                'tier' => $tier,
                'tier_label' => $this->getTierLabel($tier),
                'completed' => $completed,
                'total' => $total,
                'percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
                'is_complete' => $completed === $total && $total > 0,
                'approved_at' => $company->{"tier_{$tier}_approved_at"},
            ];
        }

        return [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'lifecycle_state' => $company->lifecycle_state,
                'buying_enabled' => $company->buying_enabled,
            ],
            'tier_progress' => array_values($tierProgress),
            'overall_progress' => [
                'tier_1_complete' => $tierProgress[1]['is_complete'],
                'tier_2_complete' => $tierProgress[2]['is_complete'],
                'tier_3_complete' => $tierProgress[3]['is_complete'],
                'current_tier' => $this->getCurrentTier($tierProgress),
                'can_go_live' => $tierProgress[1]['is_complete'],
                'can_accept_investments' => $tierProgress[2]['is_complete'],
            ],
            'blockers' => $blockers,
            'next_actions' => $nextActions,
            'statistics' => [
                'total_modules' => $modules->count(),
                'completed_modules' => $disclosures->where('status', 'approved')->count(),
                'in_progress' => $disclosures->whereIn('status', ['draft', 'submitted', 'under_review'])->count(),
                'blocked' => count($blockers),
            ],
        ];
    }

    /**
     * Create or update disclosure draft
     *
     * SAFEGUARD: Can only edit disclosures in draft/rejected/clarification_required states
     * TRANSPARENCY: All edits logged
     *
     * @param Company $company
     * @param int $moduleId
     * @param array $disclosureData
     * @param int $userId
     * @param string|null $editReason Optional reason for edit
     * @return CompanyDisclosure
     */
    public function saveDraft(
        Company $company,
        int $moduleId,
        array $disclosureData,
        int $userId,
        ?string $editReason = null
    ): CompanyDisclosure {
        DB::beginTransaction();

        try {
            $module = DisclosureModule::findOrFail($moduleId);

            // Find or create disclosure
            $disclosure = CompanyDisclosure::firstOrNew([
                'company_id' => $company->id,
                'disclosure_module_id' => $moduleId,
            ]);

            // SAFEGUARD: Check if editable
            if ($disclosure->exists && !$this->isEditable($disclosure)) {
                throw new \RuntimeException(
                    "Cannot edit disclosure in '{$disclosure->status}' status. " .
                    "Use 'Report Error' if you need to correct approved data."
                );
            }

            // Store old data for audit
            $oldData = $disclosure->disclosure_data ?? [];

            // Update disclosure
            $disclosure->disclosure_data = $disclosureData;
            $disclosure->last_modified_by = $userId;
            $disclosure->last_modified_at = now();

            if (!$disclosure->exists) {
                $disclosure->status = 'draft';
            }

            // Calculate completion percentage based on schema
            $disclosure->completion_percentage = $this->calculateCompletionPercentage(
                $disclosureData,
                $module->json_schema
            );

            $disclosure->save();

            // TRANSPARENCY: Log the edit
            $this->logDraftEdit($disclosure, $oldData, $disclosureData, $userId, $editReason);

            DB::commit();

            Log::info('Disclosure draft saved', [
                'company_id' => $company->id,
                'disclosure_id' => $disclosure->id,
                'module_code' => $module->code,
                'user_id' => $userId,
                'completion' => $disclosure->completion_percentage,
                'edit_reason' => $editReason,
            ]);

            return $disclosure;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save disclosure draft', [
                'company_id' => $company->id,
                'module_id' => $moduleId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Submit disclosure for admin review
     *
     * SAFEGUARD: Must be 100% complete
     * TRANSPARENCY: Creates approval record with submission context
     *
     * @param CompanyDisclosure $disclosure
     * @param int $userId
     * @param string|null $submissionNotes Optional notes for reviewer
     * @return void
     */
    public function submitForReview(
        CompanyDisclosure $disclosure,
        int $userId,
        ?string $submissionNotes = null
    ): void {
        DB::beginTransaction();

        try {
            // SAFEGUARD: Validate can submit
            if (!$this->canSubmit($disclosure)) {
                throw new \RuntimeException(
                    "Cannot submit disclosure. " .
                    "Status: {$disclosure->status}, " .
                    "Completion: {$disclosure->completion_percentage}%"
                );
            }

            // Use model method (Phase 1)
            $disclosure->submit($userId);

            // Add submission notes if provided
            if ($submissionNotes) {
                $disclosure->submission_notes = $submissionNotes;
                $disclosure->save();
            }

            DB::commit();

            Log::info('Disclosure submitted for review', [
                'company_id' => $disclosure->company_id,
                'disclosure_id' => $disclosure->id,
                'module_code' => $disclosure->module->code,
                'user_id' => $userId,
                'has_notes' => !empty($submissionNotes),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit disclosure', [
                'disclosure_id' => $disclosure->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Answer admin clarification
     *
     * SAFEGUARD: Can only answer clarifications in 'open' or 'disputed' status
     * PHASE 2 HARDENING: Must verify disclosure is in valid review state
     * TRANSPARENCY: Full answer history with attachments
     *
     * @param DisclosureClarification $clarification
     * @param int $userId
     * @param string $answerBody
     * @param array|null $supportingDocuments
     * @return void
     */
    public function answerClarification(
        DisclosureClarification $clarification,
        int $userId,
        string $answerBody,
        ?array $supportingDocuments = null
    ): void {
        // SAFEGUARD: Check if answerable
        if (!in_array($clarification->status, ['open', 'disputed'])) {
            throw new \RuntimeException(
                "Cannot answer clarification in '{$clarification->status}' status"
            );
        }

        // PHASE 2 HARDENING - Issue 4: Clarification State Guard
        // CRITICAL: Block clarification answers unless disclosure is in valid review state
        // Prevents orphaned compliance actions when disclosure has been approved/rejected
        $disclosure = $clarification->companyDisclosure;
        $validReviewStates = ['under_review', 'clarification_required'];

        if (!in_array($disclosure->status, $validReviewStates)) {
            Log::warning('CLARIFICATION STATE GUARD: Answer blocked - disclosure not in valid review state', [
                'clarification_id' => $clarification->id,
                'disclosure_id' => $disclosure->id,
                'disclosure_status' => $disclosure->status,
                'valid_states' => $validReviewStates,
                'company_id' => $clarification->company_id,
                'user_id' => $userId,
            ]);

            throw new \RuntimeException(
                "Cannot answer clarification: Disclosure is in '{$disclosure->status}' status. " .
                "Clarifications can only be answered when disclosure is under_review or clarification_required. " .
                "This clarification is orphaned - please contact admin if you need to address this issue."
            );
        }

        // Use model method (Phase 1)
        $clarification->submitAnswer($userId, $answerBody, $supportingDocuments);

        Log::info('Clarification answered', [
            'company_id' => $clarification->company_id,
            'clarification_id' => $clarification->id,
            'disclosure_id' => $clarification->company_disclosure_id,
            'disclosure_status' => $disclosure->status,
            'user_id' => $userId,
            'has_documents' => !empty($supportingDocuments),
        ]);
    }

    /**
     * Report error or omission in approved disclosure
     *
     * CRITICAL SAFEGUARD:
     * - Does NOT overwrite approved data
     * - Creates new draft version
     * - Notifies admin
     * - Logs self-reported correction
     *
     * This treats issuer honesty as a positive signal, not a penalty.
     *
     * @param CompanyDisclosure $disclosure
     * @param int $userId
     * @param string $errorDescription
     * @param array $correctedData
     * @param string $correctionReason
     * @return CompanyDisclosure New draft disclosure
     */
    public function reportErrorInApprovedDisclosure(
        CompanyDisclosure $disclosure,
        int $userId,
        string $errorDescription,
        array $correctedData,
        string $correctionReason
    ): CompanyDisclosure {
        // SAFEGUARD: Can only report errors in approved disclosures
        if ($disclosure->status !== 'approved') {
            throw new \RuntimeException('Can only report errors in approved disclosures');
        }

        DB::beginTransaction();

        try {
            // Create error report record for audit trail
            $errorReport = \App\Models\DisclosureErrorReport::create([
                'company_disclosure_id' => $disclosure->id,
                'company_id' => $disclosure->company_id,
                'reported_by' => $userId,
                'reported_at' => now(),
                'error_description' => $errorDescription,
                'correction_reason' => $correctionReason,
                'original_data' => $disclosure->disclosure_data,
                'corrected_data' => $correctedData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Create NEW disclosure draft (do NOT modify approved one)
            $newDraft = new CompanyDisclosure([
                'company_id' => $disclosure->company_id,
                'disclosure_module_id' => $disclosure->disclosure_module_id,
                'disclosure_data' => $correctedData,
                'status' => 'draft',
                'version_number' => $disclosure->version_number + 1,
                'supersedes_disclosure_id' => $disclosure->id,
                'created_from_error_report' => true,
                'error_report_id' => $errorReport->id,
                'last_modified_by' => $userId,
                'last_modified_at' => now(),
            ]);

            $newDraft->completion_percentage = $this->calculateCompletionPercentage(
                $correctedData,
                $disclosure->module->json_schema
            );

            $newDraft->save();

            // Notify admin about self-reported correction
            $this->notifyAdminOfErrorReport($disclosure, $errorReport, $newDraft);

            DB::commit();

            Log::warning('Self-reported error in approved disclosure', [
                'company_id' => $disclosure->company_id,
                'original_disclosure_id' => $disclosure->id,
                'new_draft_id' => $newDraft->id,
                'error_report_id' => $errorReport->id,
                'user_id' => $userId,
                'error_description' => $errorDescription,
            ]);

            return $newDraft;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to report error', [
                'disclosure_id' => $disclosure->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Attach documents to disclosure
     *
     * TRANSPARENCY: All attachments logged with uploader and timestamp
     *
     * @param CompanyDisclosure $disclosure
     * @param array $documents
     * @param int $userId
     * @return void
     */
    public function attachDocuments(
        CompanyDisclosure $disclosure,
        array $documents,
        int $userId
    ): void {
        // SAFEGUARD: Cannot attach to locked disclosure
        if ($disclosure->is_locked) {
            throw new \RuntimeException('Cannot attach documents to locked disclosure');
        }

        $existingAttachments = $disclosure->attachments ?? [];

        foreach ($documents as $document) {
            $existingAttachments[] = [
                'file_path' => $document['file_path'],
                'file_name' => $document['file_name'],
                'file_type' => $document['file_type'] ?? null,
                'file_size' => $document['file_size'] ?? null,
                'description' => $document['description'] ?? null,
                'uploaded_by' => $userId,
                'uploaded_at' => now()->toIso8601String(),
            ];
        }

        $disclosure->attachments = $existingAttachments;
        $disclosure->save();

        Log::info('Documents attached to disclosure', [
            'disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'user_id' => $userId,
            'document_count' => count($documents),
        ]);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if disclosure is editable
     */
    protected function isEditable(CompanyDisclosure $disclosure): bool
    {
        return in_array($disclosure->status, ['draft', 'rejected', 'clarification_required']);
    }

    /**
     * Check if disclosure can be submitted
     */
    protected function canSubmit(CompanyDisclosure $disclosure): bool
    {
        return $disclosure->status === 'draft'
            && $disclosure->completion_percentage === 100
            && !$disclosure->is_locked;
    }

    /**
     * Calculate completion percentage based on schema requirements
     */
    protected function calculateCompletionPercentage(array $data, ?array $schema): int
    {
        if (!$schema || !isset($schema['required'])) {
            return 100; // No schema validation
        }

        $required = $schema['required'] ?? [];
        if (empty($required)) {
            return 100;
        }

        $completed = 0;
        foreach ($required as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $completed++;
            }
        }

        return round(($completed / count($required)) * 100);
    }

    /**
     * Log draft edit for transparency
     */
    protected function logDraftEdit(
        CompanyDisclosure $disclosure,
        array $oldData,
        array $newData,
        int $userId,
        ?string $reason
    ): void {
        $changes = $this->calculateChanges($oldData, $newData);

        if (empty($changes)) {
            return; // No changes
        }

        $editLog = [
            'edited_at' => now()->toIso8601String(),
            'edited_by' => $userId,
            'edit_reason' => $reason,
            'fields_changed' => array_keys($changes),
            'change_count' => count($changes),
        ];

        $disclosure->draft_edit_history = array_merge(
            $disclosure->draft_edit_history ?? [],
            [$editLog]
        );
        $disclosure->save();
    }

    /**
     * Calculate changes between old and new data
     */
    protected function calculateChanges(array $oldData, array $newData): array
    {
        $changes = [];

        foreach ($newData as $key => $value) {
            if (!isset($oldData[$key]) || $oldData[$key] !== $value) {
                $changes[$key] = [
                    'old' => $oldData[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }

    /**
     * Get tier label for display
     */
    protected function getTierLabel(int $tier): string
    {
        return match($tier) {
            1 => 'Basic Information',
            2 => 'Financial & Offering',
            3 => 'Advanced Disclosures',
            default => "Tier {$tier}",
        };
    }

    /**
     * Get current tier from progress
     */
    protected function getCurrentTier(array $tierProgress): int
    {
        if ($tierProgress[3]['is_complete']) return 3;
        if ($tierProgress[2]['is_complete']) return 2;
        if ($tierProgress[1]['is_complete']) return 1;
        return 0;
    }

    /**
     * Notify admin of self-reported error
     */
    protected function notifyAdminOfErrorReport(
        CompanyDisclosure $originalDisclosure,
        $errorReport,
        CompanyDisclosure $newDraft
    ): void {
        // TODO: Implement notification system
        // For now, just log
        Log::info('Admin notification: Self-reported error', [
            'company_id' => $originalDisclosure->company_id,
            'company_name' => $originalDisclosure->company->name,
            'module_name' => $originalDisclosure->module->name,
            'error_report_id' => $errorReport->id,
            'new_draft_id' => $newDraft->id,
        ]);
    }
}
