<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * IdempotencyService - Ensure Jobs Run Only Once (G.22)
 *
 * PURPOSE:
 * - Make all async jobs idempotent (safe to run twice without double effects)
 * - Prevent duplicate financial operations (double credits, double allocations)
 * - Track job execution history for debugging
 *
 * PROTOCOL:
 * 1. Before executing job, check if already executed via idempotency key
 * 2. If already completed: Return cached result, skip execution
 * 3. If already processing: Wait or fail (avoid concurrent execution)
 * 4. If not started: Record execution, proceed with job
 *
 * IDEMPOTENCY KEY FORMAT:
 * - Payment processing: "payment_processing:{payment_id}"
 * - Bonus calculation: "bonus_calculation:{payment_id}"
 * - Share allocation: "share_allocation:{investment_id}"
 * - Referral processing: "referral_processing:{user_id}"
 *
 * USAGE:
 * ```php
 * public function handle(IdempotencyService $idempotency)
 * {
 *     $key = "payment_processing:{$this->payment->id}";
 *
 *     if ($idempotency->isAlreadyExecuted($key)) {
 *         Log::info("Job already executed, skipping");
 *         return;
 *     }
 *
 *     $result = $idempotency->executeOnce($key, function () {
 *         // Your job logic here
 *         return ['wallet_balance' => 1000];
 *     });
 * }
 * ```
 */
class IdempotencyService
{
    /**
     * Check if job was already executed successfully
     *
     * @param string $idempotencyKey Unique key for this operation
     * @param string|null $jobClass Optional job class for additional context
     * @return bool True if already executed
     */
    public function isAlreadyExecuted(string $idempotencyKey, ?string $jobClass = null): bool
    {
        $query = DB::table('job_executions')
            ->where('idempotency_key', $idempotencyKey)
            ->where('status', 'completed');

        if ($jobClass) {
            $query->where('job_class', $jobClass);
        }

        $execution = $query->first();

        if ($execution) {
            Log::info("IDEMPOTENCY: Job already executed", [
                'idempotency_key' => $idempotencyKey,
                'job_class' => $jobClass,
                'completed_at' => $execution->completed_at,
            ]);
            return true;
        }

        return false;
    }

    /**
     * Check if job is currently being processed
     *
     * @param string $idempotencyKey
     * @param string|null $jobClass
     * @return bool True if currently processing
     */
    public function isCurrentlyProcessing(string $idempotencyKey, ?string $jobClass = null): bool
    {
        $query = DB::table('job_executions')
            ->where('idempotency_key', $idempotencyKey)
            ->where('status', 'processing');

        if ($jobClass) {
            $query->where('job_class', $jobClass);
        }

        return $query->exists();
    }

    /**
     * Get cached result from previous execution
     *
     * @param string $idempotencyKey
     * @param string|null $jobClass
     * @return mixed|null Cached result or null
     */
    public function getCachedResult(string $idempotencyKey, ?string $jobClass = null)
    {
        $query = DB::table('job_executions')
            ->where('idempotency_key', $idempotencyKey)
            ->where('status', 'completed');

        if ($jobClass) {
            $query->where('job_class', $jobClass);
        }

        $execution = $query->first();

        if ($execution && $execution->result) {
            return json_decode($execution->result, true);
        }

        return null;
    }

    /**
     * Execute operation only once, with idempotency protection
     *
     * CRITICAL: This ensures the operation runs exactly once
     *
     * @param string $idempotencyKey Unique key for this operation
     * @param callable $operation Function to execute
     * @param array $options ['job_class' => '...', 'max_attempts' => 3, 'input_data' => [...]]
     * @return mixed Result from operation
     * @throws \RuntimeException If already processing or failed
     */
    public function executeOnce(string $idempotencyKey, callable $operation, array $options = [])
    {
        $jobClass = $options['job_class'] ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? 'Unknown';
        $maxAttempts = $options['max_attempts'] ?? 3;
        $inputData = $options['input_data'] ?? null;

        // Check 1: Already completed?
        if ($this->isAlreadyExecuted($idempotencyKey, $jobClass)) {
            Log::info("IDEMPOTENCY: Returning cached result", [
                'idempotency_key' => $idempotencyKey,
            ]);
            return $this->getCachedResult($idempotencyKey, $jobClass);
        }

        // Check 2: Currently processing?
        if ($this->isCurrentlyProcessing($idempotencyKey, $jobClass)) {
            throw new \RuntimeException(
                "IDEMPOTENCY VIOLATION: Job is already being processed. " .
                "Idempotency key: {$idempotencyKey}. " .
                "This indicates concurrent execution or incomplete cleanup."
            );
        }

        // Check 3: Get or create execution record
        $execution = DB::table('job_executions')
            ->where('idempotency_key', $idempotencyKey)
            ->where('job_class', $jobClass)
            ->first();

        if (!$execution) {
            // Create new execution record
            $executionId = DB::table('job_executions')->insertGetId([
                'job_class' => $jobClass,
                'idempotency_key' => $idempotencyKey,
                'status' => 'pending',
                'max_attempts' => $maxAttempts,
                'input_data' => $inputData ? json_encode($inputData) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $executionId = $execution->id;

            // Check if already failed too many times
            if ($execution->status === 'failed' && $execution->attempt_number >= $maxAttempts) {
                throw new \RuntimeException(
                    "IDEMPOTENCY: Job permanently failed after {$maxAttempts} attempts. " .
                    "Idempotency key: {$idempotencyKey}. " .
                    "Error: {$execution->error_message}"
                );
            }

            // Increment attempt number
            DB::table('job_executions')
                ->where('id', $executionId)
                ->increment('attempt_number');
        }

        // Mark as processing
        DB::table('job_executions')
            ->where('id', $executionId)
            ->update([
                'status' => 'processing',
                'started_at' => now(),
                'updated_at' => now(),
            ]);

        try {
            // Execute the operation
            $result = $operation();

            // Mark as completed
            DB::table('job_executions')
                ->where('id', $executionId)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'result' => is_array($result) || is_object($result) ? json_encode($result) : $result,
                    'updated_at' => now(),
                ]);

            Log::info("IDEMPOTENCY: Job executed successfully", [
                'idempotency_key' => $idempotencyKey,
                'job_class' => $jobClass,
                'execution_id' => $executionId,
            ]);

            return $result;

        } catch (\Throwable $e) {
            // Mark as failed
            DB::table('job_executions')
                ->where('id', $executionId)
                ->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'error_message' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    'updated_at' => now(),
                ]);

            Log::error("IDEMPOTENCY: Job execution failed", [
                'idempotency_key' => $idempotencyKey,
                'job_class' => $jobClass,
                'execution_id' => $executionId,
                'error' => $e->getMessage(),
            ]);

            // Re-throw for normal error handling
            throw $e;
        }
    }

    /**
     * Mark job as completed (for manual tracking)
     *
     * @param string $idempotencyKey
     * @param string $jobClass
     * @param mixed $result
     * @return void
     */
    public function markCompleted(string $idempotencyKey, string $jobClass, $result = null): void
    {
        DB::table('job_executions')->updateOrInsert(
            [
                'idempotency_key' => $idempotencyKey,
                'job_class' => $jobClass,
            ],
            [
                'status' => 'completed',
                'completed_at' => now(),
                'result' => is_array($result) || is_object($result) ? json_encode($result) : $result,
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Clean up old execution records (for maintenance)
     *
     * @param int $daysToKeep Keep records for this many days
     * @return int Number of records deleted
     */
    public function cleanupOldRecords(int $daysToKeep = 90): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);

        $deleted = DB::table('job_executions')
            ->where('status', 'completed')
            ->where('completed_at', '<', $cutoffDate)
            ->delete();

        Log::info("IDEMPOTENCY: Cleaned up old execution records", [
            'days_to_keep' => $daysToKeep,
            'records_deleted' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * Get execution history for debugging
     *
     * @param string $idempotencyKey
     * @return array
     */
    public function getExecutionHistory(string $idempotencyKey): array
    {
        $executions = DB::table('job_executions')
            ->where('idempotency_key', $idempotencyKey)
            ->orderBy('created_at', 'desc')
            ->get();

        return $executions->map(function ($execution) {
            return [
                'id' => $execution->id,
                'job_class' => $execution->job_class,
                'status' => $execution->status,
                'attempt_number' => $execution->attempt_number,
                'started_at' => $execution->started_at,
                'completed_at' => $execution->completed_at,
                'failed_at' => $execution->failed_at,
                'error_message' => $execution->error_message,
            ];
        })->toArray();
    }
}
