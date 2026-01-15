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

        // PHASE 3 HARDENING - Issue 1: Platform Context Injection
        // CRITICAL: Make issuer dashboard explicitly platform-aware
        // Issuer cannot infer authority from status alone - platform state is injected
        $platformContext = $this->getPlatformContextForIssuer($company);

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
            // PHASE 3 HARDENING: Platform context injection
            'platform_context' => $platformContext,
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
     * PHASE 3 HARDENING - Issue 2: Platform-Aware Error Reporting
     * - Classifies error severity (minor, moderate, major, critical)
     * - Determines platform reaction (log, review, pause buying, suspend)
     * - Executes platform reaction automatically
     *
     * This treats issuer honesty as a positive signal, not a penalty.
     *
     * @param CompanyDisclosure $disclosure
     * @param int $userId
     * @param string $errorDescription
     * @param array $correctedData
     * @param string $correctionReason
     * @param string|null $issuerProvidedSeverity Optional severity classification from issuer
     * @return CompanyDisclosure New draft disclosure
     */
    public function reportErrorInApprovedDisclosure(
        CompanyDisclosure $disclosure,
        int $userId,
        string $errorDescription,
        array $correctedData,
        string $correctionReason,
        ?string $issuerProvidedSeverity = null
    ): CompanyDisclosure {
        // SAFEGUARD: Can only report errors in approved disclosures
        if ($disclosure->status !== 'approved') {
            throw new \RuntimeException('Can only report errors in approved disclosures');
        }

        DB::beginTransaction();

        try {
            // PHASE 3 HARDENING - Issue 2: Classify error severity and determine platform reaction
            $classificationService = new ErrorReportClassificationService();
            $classification = $classificationService->classifyErrorReport(
                $disclosure,
                $errorDescription,
                $disclosure->disclosure_data,
                $correctedData,
                $issuerProvidedSeverity
            );

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
                // PHASE 3 HARDENING: Store severity and platform reaction
                'severity' => $classification['severity'],
                'auto_classification' => $classification['auto_classification'],
                'issuer_provided_severity' => $issuerProvidedSeverity,
                'platform_reaction' => $classification['platform_reaction'],
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

            // PHASE 3 HARDENING - Issue 2: Execute platform reaction
            $classificationService->executePlatformReaction(
                $disclosure->company,
                $classification['platform_reaction'],
                $errorReport->id
            );

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

    /**
     * PHASE 3 HARDENING - Issue 1: Platform Context Injection
     *
     * Get platform context that affects issuer actions
     *
     * PURPOSE:
     * - Make it explicit that issuer dashboards are platform-mediated
     * - Issuer cannot infer authority from disclosure status alone
     * - Platform state can override issuer editability and actions
     *
     * RETURNS:
     * - Platform governance state (lifecycle, suspension, buying)
     * - Platform overrides (freeze, investigation, manual restrictions)
     * - Effective permissions (what issuer can actually do given platform state)
     * - Platform messages (why actions are restricted)
     *
     * @param Company $company
     * @return array Platform context data
     */
    protected function getPlatformContextForIssuer(Company $company): array
    {
        // Check for active platform restrictions
        $isSuspended = $company->lifecycle_state === 'suspended' || ($company->is_suspended ?? false);
        $isFrozen = $company->disclosure_freeze ?? false;
        $isUnderInvestigation = $company->under_investigation ?? false;
        $buyingPaused = !($company->buying_enabled ?? true);

        // Determine if platform has restricted issuer actions
        $hasActiveRestrictions = $isSuspended || $isFrozen || $isUnderInvestigation || $buyingPaused;

        // Build platform override messages
        $overrideMessages = [];
        if ($isSuspended) {
            $overrideMessages[] = [
                'type' => 'suspension',
                'severity' => 'critical',
                'message' => 'Company is suspended. All disclosure editing is disabled.',
                'reason' => $company->suspension_reason ?? 'Under platform review',
                'blocks_editing' => true,
                'blocks_submission' => true,
            ];
        }

        if ($isFrozen && !$isSuspended) {
            $overrideMessages[] = [
                'type' => 'disclosure_freeze',
                'severity' => 'high',
                'message' => 'Disclosures are frozen by platform. No edits allowed until freeze is lifted.',
                'reason' => $company->freeze_reason ?? 'Platform investigation in progress',
                'blocks_editing' => true,
                'blocks_submission' => false, // Can submit existing drafts, but not edit
            ];
        }

        if ($isUnderInvestigation && !$isSuspended && !$isFrozen) {
            $overrideMessages[] = [
                'type' => 'under_investigation',
                'severity' => 'medium',
                'message' => 'Company is under platform investigation. New submissions require additional review.',
                'reason' => 'Compliance review in progress',
                'blocks_editing' => false,
                'blocks_submission' => false,
                'adds_review_delay' => true,
            ];
        }

        if ($buyingPaused && !$isSuspended) {
            $overrideMessages[] = [
                'type' => 'buying_paused',
                'severity' => 'high',
                'message' => 'Investment buying is paused by platform.',
                'reason' => $company->buying_pause_reason ?? 'Platform risk assessment',
                'blocks_editing' => false,
                'blocks_submission' => false,
                'affects_go_live' => true,
            ];
        }

        // Calculate effective permissions (role permissions + platform overrides)
        $effectivePermissions = [
            'can_edit_disclosures' => !$isSuspended && !$isFrozen,
            'can_submit_disclosures' => !$isSuspended,
            'can_answer_clarifications' => !$isSuspended && !$isFrozen,
            'can_report_errors' => !$isSuspended, // Error reports always allowed (transparency)
            'can_go_live' => !$isSuspended && !$buyingPaused,
            'platform_review_required' => $isUnderInvestigation,
        ];

        return [
            'governance_state' => [
                'lifecycle_state' => $company->lifecycle_state,
                'lifecycle_state_changed_at' => $company->lifecycle_state_changed_at,
                'lifecycle_state_changed_by' => $company->lifecycle_state_changed_by,
                'governance_state_version' => $company->governance_state_version ?? 1,
            ],
            'platform_restrictions' => [
                'is_suspended' => $isSuspended,
                'is_frozen' => $isFrozen,
                'is_under_investigation' => $isUnderInvestigation,
                'buying_paused' => $buyingPaused,
                'has_active_restrictions' => $hasActiveRestrictions,
            ],
            'suspension_details' => $isSuspended ? [
                'suspended_at' => $company->suspended_at,
                'suspended_by' => $company->suspended_by,
                'suspension_reason' => $company->suspension_reason,
                'suspension_internal_notes' => null, // Internal only, not shown to issuer
                'show_warning_banner' => $company->show_warning_banner ?? false,
                'warning_banner_message' => $company->warning_banner_message,
            ] : null,
            'tier_approvals' => [
                'tier_1_approved' => $company->tier_1_approved_at !== null,
                'tier_1_approved_at' => $company->tier_1_approved_at,
                'tier_2_approved' => $company->tier_2_approved_at !== null,
                'tier_2_approved_at' => $company->tier_2_approved_at,
                'tier_3_approved' => $company->tier_3_approved_at !== null,
                'tier_3_approved_at' => $company->tier_3_approved_at,
            ],
            'platform_overrides' => $overrideMessages,
            'effective_permissions' => $effectivePermissions,
            'platform_message' => $hasActiveRestrictions
                ? 'Platform has restricted some actions for this company. See platform_overrides for details.'
                : 'No active platform restrictions. Normal issuer permissions apply.',
        ];
    }
}
