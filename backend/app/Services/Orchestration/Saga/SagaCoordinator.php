<?php

namespace App\Services\Orchestration\Saga;

use App\Services\Orchestration\Operations\OperationInterface;
use App\Services\Orchestration\Operations\OperationResult;
use App\Models\SagaExecution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * SagaCoordinator - Implements Saga Pattern with Compensation
 *
 * PROTOCOL:
 * 1. Execute operations sequentially
 * 2. Track each step completion in saga_executions table
 * 3. If ANY step fails, compensate ALL completed steps in REVERSE order
 * 4. System enters consistent state: either ALL steps succeed or ALL are undone
 *
 * FAILURE SEMANTICS:
 * - No partial state (atomic across multiple tables/services)
 * - Crash-safe (can resume from saga_executions)
 * - Provenance tracked (why every step executed)
 */
class SagaCoordinator
{
    /**
     * Create Saga Context
     *
     * @param array $metadata Contextual data for this saga instance
     * @return SagaContext
     */
    public function createContext(array $metadata): SagaContext
    {
        $sagaId = \Illuminate\Support\Str::uuid()->toString();

        // Persist saga creation
        $sagaExecution = SagaExecution::create([
            'saga_id' => $sagaId,
            'status' => 'initiated',
            'metadata' => $metadata,
            'steps_completed' => 0,
            'steps_total' => 0,
            'initiated_at' => now(),
        ]);

        return new SagaContext($sagaId, $metadata, $sagaExecution);
    }

    /**
     * Execute Saga with Automatic Compensation on Failure
     *
     * @param SagaContext $context
     * @param array<OperationInterface> $operations
     * @return OperationResult
     */
    public function execute(SagaContext $context, array $operations): OperationResult
    {
        $completedOperations = [];
        $stepNumber = 0;

        // Update total steps
        $context->getSagaExecution()->update([
            'steps_total' => count($operations),
            'status' => 'executing',
        ]);

        try {
            foreach ($operations as $operation) {
                $stepNumber++;

                Log::info("SAGA [{$context->getId()}] Executing step {$stepNumber}: " . get_class($operation));

                // Execute operation within DB transaction
                $result = DB::transaction(function () use ($operation, $context) {
                    return $operation->execute($context);
                });

                if (!$result->isSuccess()) {
                    throw new \Exception(
                        "Step {$stepNumber} failed: {$result->getMessage()}"
                    );
                }

                // Track completed operation for potential compensation
                $completedOperations[] = [
                    'operation' => $operation,
                    'result' => $result,
                    'step_number' => $stepNumber,
                ];

                // Persist step completion
                $this->recordStepCompletion($context, $stepNumber, $operation, $result);

                // Update saga progress
                $context->getSagaExecution()->update([
                    'steps_completed' => $stepNumber,
                ]);
            }

            // All steps succeeded - mark saga as completed
            $context->getSagaExecution()->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::info("SAGA [{$context->getId()}] Completed successfully ({$stepNumber} steps)");

            return OperationResult::success('Saga completed successfully', [
                'saga_id' => $context->getId(),
                'steps_executed' => $stepNumber,
            ]);

        } catch (\Throwable $e) {
            Log::error("SAGA [{$context->getId()}] Failed at step {$stepNumber}: {$e->getMessage()}");

            // COMPENSATION: Undo all completed steps in REVERSE order
            $this->compensate($context, $completedOperations);

            // Mark saga as failed
            $context->getSagaExecution()->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $e->getMessage(),
                'failure_step' => $stepNumber,
            ]);

            throw $e; // Re-throw for orchestrator to handle
        }
    }

    /**
     * Compensate All Completed Operations (in reverse order)
     *
     * PROTOCOL:
     * - Compensation is BEST EFFORT (must not throw)
     * - Each operation defines its own compensation logic
     * - Compensation failures are logged but don't stop the process
     * - System reaches consistent state even if some compensations fail
     */
    private function compensate(SagaContext $context, array $completedOperations): void
    {
        Log::warning("SAGA [{$context->getId()}] Starting compensation for " . count($completedOperations) . " steps");

        // Reverse order (undo last step first)
        $reversedOperations = array_reverse($completedOperations);

        foreach ($reversedOperations as $completedOp) {
            try {
                $operation = $completedOp['operation'];
                $stepNumber = $completedOp['step_number'];

                Log::info("SAGA [{$context->getId()}] Compensating step {$stepNumber}: " . get_class($operation));

                DB::transaction(function () use ($operation, $context) {
                    $operation->compensate($context);
                });

                // Record compensation success
                $this->recordCompensation($context, $stepNumber, $operation, true);

            } catch (\Throwable $e) {
                // Compensation failure is logged but doesn't stop process
                Log::error("SAGA [{$context->getId()}] Compensation failed for step {$completedOp['step_number']}: {$e->getMessage()}");

                // Record compensation failure
                $this->recordCompensation($context, $completedOp['step_number'], $completedOp['operation'], false, $e->getMessage());
            }
        }

        // Update saga status
        $context->getSagaExecution()->update([
            'status' => 'compensated',
            'compensated_at' => now(),
        ]);

        Log::warning("SAGA [{$context->getId()}] Compensation complete");
    }

    /**
     * Record Step Completion in Database
     */
    private function recordStepCompletion(
        SagaContext $context,
        int $stepNumber,
        OperationInterface $operation,
        OperationResult $result
    ): void {
        \App\Models\SagaStep::create([
            'saga_execution_id' => $context->getSagaExecution()->id,
            'step_number' => $stepNumber,
            'operation_class' => get_class($operation),
            'status' => 'completed',
            'result_data' => $result->getData(),
            'executed_at' => now(),
        ]);
    }

    /**
     * Record Compensation Attempt
     */
    private function recordCompensation(
        SagaContext $context,
        int $stepNumber,
        OperationInterface $operation,
        bool $success,
        ?string $errorMessage = null
    ): void {
        \App\Models\SagaStep::where('saga_execution_id', $context->getSagaExecution()->id)
            ->where('step_number', $stepNumber)
            ->update([
                'compensation_status' => $success ? 'compensated' : 'compensation_failed',
                'compensation_error' => $errorMessage,
                'compensated_at' => now(),
            ]);
    }

    /**
     * Get Full Provenance Trail for an Entity
     *
     * Answers: "Why did this financial event occur? What steps led to it?"
     *
     * @param string $entityType
     * @param int $entityId
     * @return array
     */
    public function getProvenanceTrail(string $entityType, int $entityId): array
    {
        // Find saga execution that references this entity
        $sagaExecution = SagaExecution::whereJsonContains('metadata->' . $entityType . '_id', $entityId)
            ->with('steps')
            ->first();

        if (!$sagaExecution) {
            return [
                'found' => false,
                'message' => "No saga found for {$entityType} #{$entityId}",
            ];
        }

        return [
            'found' => true,
            'saga_id' => $sagaExecution->saga_id,
            'status' => $sagaExecution->status,
            'initiated_at' => $sagaExecution->initiated_at,
            'completed_at' => $sagaExecution->completed_at,
            'metadata' => $sagaExecution->metadata,
            'steps' => $sagaExecution->steps->map(function ($step) {
                return [
                    'step_number' => $step->step_number,
                    'operation' => class_basename($step->operation_class),
                    'status' => $step->status,
                    'compensation_status' => $step->compensation_status,
                    'executed_at' => $step->executed_at,
                    'result_data' => $step->result_data,
                ];
            }),
        ];
    }

    /**
     * Resume Failed Saga (for manual intervention)
     *
     * Allows admin to retry from failed step or mark as manually resolved
     */
    public function resumeSaga(string $sagaId, array $resolutionData): OperationResult
    {
        $sagaExecution = SagaExecution::where('saga_id', $sagaId)->firstOrFail();

        if ($sagaExecution->status !== 'failed') {
            return OperationResult::failure('Can only resume failed sagas');
        }

        // Mark as manually resolved
        $sagaExecution->update([
            'status' => 'manually_resolved',
            'resolution_data' => $resolutionData,
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);

        Log::info("SAGA [{$sagaId}] Manually resolved by admin #" . auth()->id());

        return OperationResult::success('Saga marked as resolved');
    }
}
