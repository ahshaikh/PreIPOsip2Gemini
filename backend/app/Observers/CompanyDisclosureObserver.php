<?php

namespace App\Observers;

use App\Models\CompanyDisclosure;
use App\Models\AuditLog;
use App\Services\CompanyMetricsService;
use App\Services\RiskFlaggingService;
use App\Services\ChangeTrackingService;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 4 - OBSERVER: CompanyDisclosureObserver
 * PHASE 1 AUDIT FIX: Added immutability enforcement for approved disclosures
 *
 * PURPOSE:
 * 1. Auto-trigger platform analysis when disclosures are approved/updated
 * 2. ENFORCE IMMUTABILITY: Block unauthorized modifications to approved disclosures
 *
 * TRIGGERS:
 * - When disclosure is approved → Recalculate company metrics and risk flags
 * - When disclosure is updated → Log change for "what's new" feature
 * - When disclosure is submitted → Log submission
 *
 * IMMUTABILITY ENFORCEMENT (Phase 1 Audit Fix):
 * - Block ALL updates to disclosures with status='approved' or is_locked=true
 * - Exception: Only status transition TO 'approved' is allowed (via approve() method)
 * - Exception: Allowed fields that don't affect disclosure data (view counts, etc.)
 *
 * INVARIANT:
 * Once a disclosure is approved, its disclosure_data MUST NOT change.
 * Any modification requires the error reporting flow which creates a NEW version.
 *
 * PERFORMANCE:
 * - Dispatches jobs to queue (doesn't block HTTP request)
 * - Only triggers for approved disclosures (not drafts)
 */
class CompanyDisclosureObserver
{
    /**
     * Fields that CAN be updated on approved disclosures (non-data fields)
     * These do NOT affect what investors see.
     */
    protected const ALLOWED_UPDATES_ON_APPROVED = [
        'internal_notes',           // Admin notes (never shown to investors)
        'current_version_id',       // Set when creating new version
        'version_number',           // Incremented for new version
        'is_locked',                // Locking mechanism
    ];

    /**
     * Fields that trigger immutability violation if changed on approved disclosure
     */
    protected const IMMUTABLE_FIELDS = [
        'disclosure_data',          // CRITICAL: The actual disclosure content
        'attachments',              // Supporting documents
        'status',                   // Cannot change from approved (except via formal process)
        'visibility',               // Visibility level
        'is_visible',               // Master visibility toggle
        'completion_percentage',    // Calculated from disclosure_data
    ];

    protected CompanyMetricsService $metricsService;
    protected RiskFlaggingService $riskFlaggingService;
    protected ChangeTrackingService $changeTrackingService;

    public function __construct(
        CompanyMetricsService $metricsService,
        RiskFlaggingService $riskFlaggingService,
        ChangeTrackingService $changeTrackingService
    ) {
        $this->metricsService = $metricsService;
        $this->riskFlaggingService = $riskFlaggingService;
        $this->changeTrackingService = $changeTrackingService;
    }

    /**
     * PHASE 1 AUDIT FIX: Block updates to approved/locked disclosures
     *
     * INVARIANT:
     * Approved disclosures are immutable. Any attempt to modify disclosure_data
     * or status on an approved disclosure MUST fail hard.
     *
     * EXCEPTION:
     * The ONLY allowed transition is FROM non-approved TO approved (handled specially).
     *
     * @param CompanyDisclosure $disclosure
     * @return bool False to abort the update
     */
    public function updating(CompanyDisclosure $disclosure): bool
    {
        $dirty = $disclosure->getDirty();
        $original = $disclosure->getOriginal();

        // Case 1: Disclosure is being approved (transition TO approved)
        // This is the ONLY allowed path - handled by approve() method
        if (isset($dirty['status']) && $dirty['status'] === 'approved' && $original['status'] !== 'approved') {
            // Allow the approval to proceed
            return true;
        }

        // Case 2: Already approved disclosure - enforce immutability
        if ($original['status'] === 'approved' || $original['is_locked']) {
            return $this->enforceImmutability($disclosure, $dirty, $original);
        }

        // Case 3: Non-approved disclosure - allow updates
        return true;
    }

    /**
     * Enforce immutability on approved/locked disclosures
     *
     * @param CompanyDisclosure $disclosure
     * @param array $dirty Changed fields
     * @param array $original Original values
     * @return bool False to block update
     */
    protected function enforceImmutability(CompanyDisclosure $disclosure, array $dirty, array $original): bool
    {
        // Check if any immutable field is being modified
        $immutableViolations = array_intersect(array_keys($dirty), self::IMMUTABLE_FIELDS);

        if (!empty($immutableViolations)) {
            // CRITICAL: Block this update
            $this->logImmutabilityViolation($disclosure, $dirty, $original, $immutableViolations);
            return false;
        }

        // Check if ONLY allowed fields are being modified
        $disallowedChanges = array_diff(array_keys($dirty), self::ALLOWED_UPDATES_ON_APPROVED);

        if (!empty($disallowedChanges)) {
            // Fields being changed are not in the allowed list
            $this->logImmutabilityViolation($disclosure, $dirty, $original, $disallowedChanges);
            return false;
        }

        // Only allowed fields are being updated - permit
        return true;
    }

    /**
     * Log immutability violation with full audit trail
     */
    protected function logImmutabilityViolation(
        CompanyDisclosure $disclosure,
        array $dirty,
        array $original,
        array $violatedFields
    ): void {
        Log::critical('PHASE 1 AUDIT: IMMUTABILITY VIOLATION on approved disclosure', [
            'disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'module_id' => $disclosure->disclosure_module_id,
            'original_status' => $original['status'],
            'is_locked' => $original['is_locked'],
            'violated_fields' => $violatedFields,
            'attempted_changes' => array_keys($dirty),
            'old_values' => array_intersect_key($original, array_flip(array_keys($dirty))),
            'new_values' => $dirty,
            'attempted_by' => auth()->id() ?? 'unauthenticated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'stack_trace' => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10))
                ->map(fn($frame) => ($frame['class'] ?? '') . '::' . ($frame['function'] ?? ''))
                ->filter()
                ->values()
                ->toArray(),
        ]);

        // Create audit log entry
        AuditLog::create([
            'action' => 'disclosure.immutability_violation',
            'actor_id' => auth()->id(),
            'description' => 'BLOCKED: Attempted to modify approved/locked disclosure',
            'metadata' => [
                'disclosure_id' => $disclosure->id,
                'company_id' => $disclosure->company_id,
                'violated_fields' => $violatedFields,
                'attempted_changes' => array_keys($dirty),
                'severity' => 'critical',
                'audit_phase' => 'phase_1',
            ],
        ]);
    }

    /**
     * Handle disclosure created event
     *
     * @param CompanyDisclosure $disclosure
     * @return void
     */
    public function created(CompanyDisclosure $disclosure): void
    {
        // Log change for "what's new" feature
        $this->changeTrackingService->logChange(
            $disclosure,
            'created',
            $disclosure->created_by ?? auth()->id() ?? 1,
            [],
            'Disclosure created'
        );

        Log::info('Disclosure created', [
            'disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'module' => $disclosure->module->name ?? 'unknown',
        ]);
    }

    /**
     * Handle disclosure updated event
     *
     * @param CompanyDisclosure $disclosure
     * @return void
     */
    public function updated(CompanyDisclosure $disclosure): void
    {
        // Check if status changed to 'approved'
        if ($disclosure->isDirty('status') && $disclosure->status === 'approved') {
            $this->handleDisclosureApproved($disclosure);
        }

        // Check if status changed to 'submitted'
        if ($disclosure->isDirty('status') && $disclosure->status === 'submitted') {
            $this->handleDisclosureSubmitted($disclosure);
        }

        // Check if status changed to 'rejected'
        if ($disclosure->isDirty('status') && $disclosure->status === 'rejected') {
            $this->handleDisclosureRejected($disclosure);
        }

        // Log change for audit trail
        $changedFields = array_keys($disclosure->getDirty());
        if (!empty($changedFields)) {
            $this->changeTrackingService->logChange(
                $disclosure,
                'draft_updated',
                $disclosure->last_modified_by ?? auth()->id() ?? 1,
                $changedFields,
                'Disclosure updated'
            );
        }
    }

    /**
     * Handle disclosure approval
     *
     * TRIGGERS:
     * - Recalculate company metrics
     * - Detect risk flags
     * - Log approval in change history
     *
     * PERFORMANCE NOTE: Dispatched to queue (async)
     */
    protected function handleDisclosureApproved(CompanyDisclosure $disclosure): void
    {
        Log::info('Disclosure approved - triggering platform analysis', [
            'disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'module' => $disclosure->module->name ?? 'unknown',
        ]);

        // Dispatch async job to recalculate metrics
        // This doesn't block the HTTP request
        dispatch(function () use ($disclosure) {
            try {
                // Recalculate company metrics
                $metrics = $this->metricsService->calculateMetrics($disclosure->company);

                Log::info('Company metrics recalculated after disclosure approval', [
                    'disclosure_id' => $disclosure->id,
                    'company_id' => $disclosure->company_id,
                    'completeness' => $metrics->disclosure_completeness_score,
                    'financial_band' => $metrics->financial_health_band,
                ]);

                // Detect risk flags
                $flags = $this->riskFlaggingService->detectRisks($disclosure->company);

                Log::info('Risk flags detected after disclosure approval', [
                    'disclosure_id' => $disclosure->id,
                    'company_id' => $disclosure->company_id,
                    'flags_created' => count($flags),
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to recalculate platform analysis after approval', [
                    'disclosure_id' => $disclosure->id,
                    'company_id' => $disclosure->company_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Don't throw - we don't want to fail the disclosure approval
                // Metrics can be recalculated later by scheduled job
            }
        })->afterResponse();

        // Log approval in change history
        $this->changeTrackingService->logChange(
            $disclosure,
            'approved',
            $disclosure->last_modified_by ?? auth()->id() ?? 1,
            [],
            'Disclosure approved by admin'
        );
    }

    /**
     * Handle disclosure submission
     */
    protected function handleDisclosureSubmitted(CompanyDisclosure $disclosure): void
    {
        Log::info('Disclosure submitted for review', [
            'disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'module' => $disclosure->module->name ?? 'unknown',
        ]);

        // Log submission in change history
        $this->changeTrackingService->logChange(
            $disclosure,
            'submitted',
            $disclosure->last_modified_by ?? auth()->id() ?? 1,
            [],
            'Disclosure submitted for admin review'
        );
    }

    /**
     * Handle disclosure rejection
     */
    protected function handleDisclosureRejected(CompanyDisclosure $disclosure): void
    {
        Log::info('Disclosure rejected', [
            'disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'module' => $disclosure->module->name ?? 'unknown',
        ]);

        // Log rejection in change history (material change - high priority)
        $this->changeTrackingService->logChange(
            $disclosure,
            'rejected',
            $disclosure->last_modified_by ?? auth()->id() ?? 1,
            [],
            'Disclosure rejected - requires revision'
        );
    }

    /**
     * Handle disclosure deleted event
     *
     * NOTE: Disclosures should NOT be hard-deleted in production
     * Use soft deletes for audit trail
     *
     * @param CompanyDisclosure $disclosure
     * @return void
     */
    public function deleted(CompanyDisclosure $disclosure): void
    {
        Log::warning('Disclosure deleted', [
            'disclosure_id' => $disclosure->id,
            'company_id' => $disclosure->company_id,
            'module' => $disclosure->module->name ?? 'unknown',
        ]);

        // If a disclosure is deleted, recalculate company metrics
        // (completeness score will decrease)
        if ($disclosure->company) {
            dispatch(function () use ($disclosure) {
                try {
                    $this->metricsService->calculateMetrics($disclosure->company);
                } catch (\Exception $e) {
                    Log::error('Failed to recalculate metrics after deletion', [
                        'company_id' => $disclosure->company_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse();
        }
    }
}
