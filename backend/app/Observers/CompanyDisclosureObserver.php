<?php

namespace App\Observers;

use App\Models\CompanyDisclosure;
use App\Services\CompanyMetricsService;
use App\Services\RiskFlaggingService;
use App\Services\ChangeTrackingService;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 4 - OBSERVER: CompanyDisclosureObserver
 *
 * PURPOSE:
 * Auto-trigger platform analysis when disclosures are approved/updated.
 *
 * TRIGGERS:
 * - When disclosure is approved → Recalculate company metrics and risk flags
 * - When disclosure is updated → Log change for "what's new" feature
 * - When disclosure is submitted → Log submission
 *
 * PERFORMANCE:
 * - Dispatches jobs to queue (doesn't block HTTP request)
 * - Only triggers for approved disclosures (not drafts)
 */
class CompanyDisclosureObserver
{
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
