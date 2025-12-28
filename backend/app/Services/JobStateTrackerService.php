<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * JobStateTrackerService - Track Workflow State (G.23)
 *
 * PURPOSE:
 * - Detect and surface partial completion in workflows
 * - Track multi-step async operations (payment → wallet → allocation → bonuses)
 * - Identify which steps completed, which are pending, which failed
 * - Enable resumption of interrupted workflows
 *
 * WORKFLOWS:
 * - payment_processing: Payment → Wallet Credit → Email
 * - investment_flow: Payment → Wallet Credit → Share Allocation → Bonus Calculation
 * - bonus_processing: Calculate → Credit Wallet → Allocate Bonus Shares
 * - referral_processing: Verify → Credit Referrer → Notify
 *
 * STATE TRANSITIONS:
 * pending → processing → completed
 * pending → processing → failed
 *
 * USAGE:
 * ```php
 * // Start workflow
 * $tracker->startWorkflow('payment_processing', 'payment', $payment->id, [
 *     'steps' => ['wallet_credit', 'email_notification'],
 * ]);
 *
 * // Mark step completed
 * $tracker->completeStep('payment_processing', 'payment', $payment->id, 'wallet_credit');
 *
 * // Check if workflow stuck
 * $isStuck = $tracker->isStuck('payment_processing', 'payment', $payment->id);
 * ```
 */
class JobStateTrackerService
{
    /**
     * Start a new workflow
     *
     * @param string $workflowType 'payment_processing', 'investment_flow', etc.
     * @param string $workflowId Entity type ('payment', 'investment')
     * @param int $entityId Entity ID
     * @param array $options ['steps' => [...], 'timeout_minutes' => 30]
     * @return int Workflow tracking ID
     */
    public function startWorkflow(
        string $workflowType,
        string $workflowId,
        int $entityId,
        array $options = []
    ): int {
        $steps = $options['steps'] ?? [];
        $timeoutMinutes = $options['timeout_minutes'] ?? 30;

        $trackingId = DB::table('job_state_tracking')->insertGetId([
            'workflow_type' => $workflowType,
            'workflow_id' => $workflowId,
            'entity_id' => $entityId,
            'current_state' => 'pending',
            'completed_steps' => json_encode([]),
            'pending_steps' => json_encode($steps),
            'failed_steps' => json_encode([]),
            'total_steps' => count($steps),
            'completed_steps_count' => 0,
            'completion_percentage' => 0,
            'started_at' => now(),
            'last_updated_at' => now(),
            'expected_completion_at' => now()->addMinutes($timeoutMinutes),
            'metadata' => isset($options['metadata']) ? json_encode($options['metadata']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info("WORKFLOW STARTED", [
            'workflow_type' => $workflowType,
            'workflow_id' => $workflowId,
            'entity_id' => $entityId,
            'tracking_id' => $trackingId,
            'total_steps' => count($steps),
        ]);

        return $trackingId;
    }

    /**
     * Update workflow state
     *
     * @param string $workflowType
     * @param string $workflowId
     * @param int $entityId
     * @param string $newState
     * @return bool
     */
    public function updateState(
        string $workflowType,
        string $workflowId,
        int $entityId,
        string $newState
    ): bool {
        $tracking = $this->getTracking($workflowType, $workflowId, $entityId);

        if (!$tracking) {
            Log::warning("WORKFLOW NOT FOUND", [
                'workflow_type' => $workflowType,
                'entity_id' => $entityId,
            ]);
            return false;
        }

        DB::table('job_state_tracking')
            ->where('id', $tracking->id)
            ->update([
                'previous_state' => $tracking->current_state,
                'current_state' => $newState,
                'last_updated_at' => now(),
                'updated_at' => now(),
            ]);

        Log::info("WORKFLOW STATE UPDATED", [
            'workflow_type' => $workflowType,
            'entity_id' => $entityId,
            'previous_state' => $tracking->current_state,
            'new_state' => $newState,
        ]);

        return true;
    }

    /**
     * Mark a step as completed
     *
     * @param string $workflowType
     * @param string $workflowId
     * @param int $entityId
     * @param string $stepName
     * @return bool
     */
    public function completeStep(
        string $workflowType,
        string $workflowId,
        int $entityId,
        string $stepName
    ): bool {
        $tracking = $this->getTracking($workflowType, $workflowId, $entityId);

        if (!$tracking) {
            Log::warning("WORKFLOW NOT FOUND for step completion", [
                'workflow_type' => $workflowType,
                'entity_id' => $entityId,
                'step' => $stepName,
            ]);
            return false;
        }

        $completedSteps = json_decode($tracking->completed_steps, true) ?: [];
        $pendingSteps = json_decode($tracking->pending_steps, true) ?: [];

        // Add to completed, remove from pending
        if (!in_array($stepName, $completedSteps)) {
            $completedSteps[] = $stepName;
        }
        $pendingSteps = array_diff($pendingSteps, [$stepName]);

        $completedCount = count($completedSteps);
        $totalSteps = $tracking->total_steps;
        $completionPercentage = $totalSteps > 0 ? ($completedCount / $totalSteps) * 100 : 0;

        // Determine new state
        $newState = $tracking->current_state;
        if (empty($pendingSteps) && empty(json_decode($tracking->failed_steps, true) ?: [])) {
            $newState = 'completed';
        } elseif ($newState === 'pending') {
            $newState = 'processing';
        }

        DB::table('job_state_tracking')
            ->where('id', $tracking->id)
            ->update([
                'current_state' => $newState,
                'completed_steps' => json_encode($completedSteps),
                'pending_steps' => json_encode(array_values($pendingSteps)),
                'completed_steps_count' => $completedCount,
                'completion_percentage' => round($completionPercentage, 2),
                'last_updated_at' => now(),
                'completed_at' => $newState === 'completed' ? now() : null,
                'updated_at' => now(),
            ]);

        Log::info("WORKFLOW STEP COMPLETED", [
            'workflow_type' => $workflowType,
            'entity_id' => $entityId,
            'step' => $stepName,
            'completion_percentage' => round($completionPercentage, 2),
            'state' => $newState,
        ]);

        return true;
    }

    /**
     * Mark a step as failed
     *
     * @param string $workflowType
     * @param string $workflowId
     * @param int $entityId
     * @param string $stepName
     * @param string|null $errorMessage
     * @return bool
     */
    public function failStep(
        string $workflowType,
        string $workflowId,
        int $entityId,
        string $stepName,
        ?string $errorMessage = null
    ): bool {
        $tracking = $this->getTracking($workflowType, $workflowId, $entityId);

        if (!$tracking) {
            return false;
        }

        $failedSteps = json_decode($tracking->failed_steps, true) ?: [];
        $pendingSteps = json_decode($tracking->pending_steps, true) ?: [];

        // Add to failed, remove from pending
        if (!in_array($stepName, $failedSteps)) {
            $failedSteps[] = $stepName;
        }
        $pendingSteps = array_diff($pendingSteps, [$stepName]);

        DB::table('job_state_tracking')
            ->where('id', $tracking->id)
            ->update([
                'current_state' => 'failed',
                'failed_steps' => json_encode($failedSteps),
                'pending_steps' => json_encode(array_values($pendingSteps)),
                'last_updated_at' => now(),
                'updated_at' => now(),
            ]);

        Log::error("WORKFLOW STEP FAILED", [
            'workflow_type' => $workflowType,
            'entity_id' => $entityId,
            'step' => $stepName,
            'error' => $errorMessage,
        ]);

        return true;
    }

    /**
     * Check if workflow is stuck
     *
     * @param string $workflowType
     * @param string $workflowId
     * @param int $entityId
     * @return bool
     */
    public function isStuck(string $workflowType, string $workflowId, int $entityId): bool
    {
        $tracking = $this->getTracking($workflowType, $workflowId, $entityId);

        if (!$tracking) {
            return false;
        }

        // Already marked as stuck
        if ($tracking->is_stuck) {
            return true;
        }

        // Check if exceeded expected completion time
        if ($tracking->expected_completion_at && now()->isAfter($tracking->expected_completion_at)) {
            $this->markAsStuck($tracking->id, "Exceeded expected completion time");
            return true;
        }

        // Check if stuck in processing state for too long (default: 1 hour)
        if ($tracking->current_state === 'processing') {
            $stuckThreshold = now()->subHour();
            if (Carbon::parse($tracking->last_updated_at)->isBefore($stuckThreshold)) {
                $this->markAsStuck($tracking->id, "Stuck in processing state for over 1 hour");
                return true;
            }
        }

        return false;
    }

    /**
     * Mark workflow as stuck
     *
     * @param int $trackingId
     * @param string $reason
     * @return void
     */
    private function markAsStuck(int $trackingId, string $reason): void
    {
        DB::table('job_state_tracking')
            ->where('id', $trackingId)
            ->update([
                'is_stuck' => true,
                'stuck_reason' => $reason,
                'stuck_detected_at' => now(),
                'updated_at' => now(),
            ]);

        Log::warning("WORKFLOW STUCK", [
            'tracking_id' => $trackingId,
            'reason' => $reason,
        ]);
    }

    /**
     * Get tracking record
     *
     * @param string $workflowType
     * @param string $workflowId
     * @param int $entityId
     * @return object|null
     */
    public function getTracking(string $workflowType, string $workflowId, int $entityId)
    {
        return DB::table('job_state_tracking')
            ->where('workflow_type', $workflowType)
            ->where('workflow_id', $workflowId)
            ->where('entity_id', $entityId)
            ->first();
    }

    /**
     * Get workflow status for user-facing display
     *
     * @param string $workflowType
     * @param string $workflowId
     * @param int $entityId
     * @return array
     */
    public function getWorkflowStatus(string $workflowType, string $workflowId, int $entityId): array
    {
        $tracking = $this->getTracking($workflowType, $workflowId, $entityId);

        if (!$tracking) {
            return [
                'status' => 'not_found',
                'message' => 'Workflow not found',
            ];
        }

        return [
            'status' => $tracking->current_state,
            'completion_percentage' => $tracking->completion_percentage,
            'completed_steps' => json_decode($tracking->completed_steps, true),
            'pending_steps' => json_decode($tracking->pending_steps, true),
            'failed_steps' => json_decode($tracking->failed_steps, true),
            'is_stuck' => (bool) $tracking->is_stuck,
            'stuck_reason' => $tracking->stuck_reason,
            'started_at' => $tracking->started_at,
            'last_updated_at' => $tracking->last_updated_at,
            'completed_at' => $tracking->completed_at,
        ];
    }

    /**
     * Get all stuck workflows
     *
     * @return array
     */
    public function getStuckWorkflows(): array
    {
        $stuck = DB::table('job_state_tracking')
            ->where('is_stuck', true)
            ->where('current_state', '!=', 'completed')
            ->orderBy('stuck_detected_at', 'asc')
            ->get();

        return $stuck->map(function ($tracking) {
            return [
                'id' => $tracking->id,
                'workflow_type' => $tracking->workflow_type,
                'entity_id' => $tracking->entity_id,
                'current_state' => $tracking->current_state,
                'completion_percentage' => $tracking->completion_percentage,
                'stuck_reason' => $tracking->stuck_reason,
                'stuck_since' => $tracking->stuck_detected_at,
                'stuck_duration' => Carbon::parse($tracking->stuck_detected_at)->diffForHumans(),
            ];
        })->toArray();
    }

    /**
     * Get partially completed workflows (for admin dashboard)
     *
     * @return array
     */
    public function getPartiallyCompletedWorkflows(): array
    {
        $partial = DB::table('job_state_tracking')
            ->where('current_state', 'processing')
            ->where('completion_percentage', '>', 0)
            ->where('completion_percentage', '<', 100)
            ->orderBy('last_updated_at', 'desc')
            ->limit(50)
            ->get();

        return $partial->map(function ($tracking) {
            return [
                'id' => $tracking->id,
                'workflow_type' => $tracking->workflow_type,
                'entity_id' => $tracking->entity_id,
                'completion_percentage' => $tracking->completion_percentage,
                'completed_steps' => json_decode($tracking->completed_steps, true),
                'pending_steps' => json_decode($tracking->pending_steps, true),
                'failed_steps' => json_decode($tracking->failed_steps, true),
                'last_updated_at' => $tracking->last_updated_at,
            ];
        })->toArray();
    }
}
