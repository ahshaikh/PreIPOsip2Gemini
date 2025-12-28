<?php

namespace App\Services\Orchestration\Operations;

use App\Services\Orchestration\Saga\SagaContext;

/**
 * OperationInterface - Contract for Saga Operations
 *
 * Every operation MUST:
 * 1. Execute atomically (within DB transaction)
 * 2. Return success/failure result
 * 3. Define compensation logic (how to undo)
 * 4. Be idempotent (safe to retry)
 */
interface OperationInterface
{
    /**
     * Execute Operation
     *
     * PROTOCOL:
     * - Must be idempotent (safe to call multiple times)
     * - Must be atomic (use DB::transaction internally if needed)
     * - Must return OperationResult (success or failure)
     * - Should NOT throw exceptions (return failure instead)
     *   Exception thrown = unrecoverable error triggering compensation
     *
     * @param SagaContext $context
     * @return OperationResult
     * @throws \Exception Only for unrecoverable errors
     */
    public function execute(SagaContext $context): OperationResult;

    /**
     * Compensate (Undo) Operation
     *
     * PROTOCOL:
     * - Called if saga fails AFTER this operation completed
     * - Must restore system to state BEFORE execute() was called
     * - Must be idempotent (safe to call even if already compensated)
     * - Should NOT throw exceptions (log errors instead)
     * - Best effort (if compensation fails, log and continue)
     *
     * @param SagaContext $context
     * @return void
     */
    public function compensate(SagaContext $context): void;

    /**
     * Get Operation Name (for logging/audit)
     *
     * @return string
     */
    public function getName(): string;
}
