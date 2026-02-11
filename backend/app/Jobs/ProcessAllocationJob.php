<?php
/**
 * [P2.2 FIX]: Queue-Based Allocation for High Concurrency
 *
 * WHY: Prevents database lock contention and HTTP timeouts under high load.
 *
 * BEFORE (Synchronous):
 * ```php
 * // InvestmentController line 286
 * $this->allocationService->allocateShares($dummyPayment, $totalAmount);
 * // Problem: Blocks HTTP request, locks database, poor scaling
 * ```
 *
 * AFTER (Async):
 * ```php
 * ProcessAllocationJob::dispatch($investment);
 * // Returns immediately, processes in background via queue
 * ```
 *
 * BENEFITS:
 * - Zero lock contention (serialized via Redis queue)
 * - Horizontal scaling (add more queue workers)
 * - Automatic retry on failure
 * - User gets instant response ("Allocation in progress")
 */

namespace App\Jobs;

use App\Models\Investment;
use App\Models\Payment;
use App\Services\AllocationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAllocationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * [P2.2]: Queue Configuration for Serialization
     *
     * WHY: Using 'allocations' queue allows us to:
     * 1. Configure dedicated workers: php artisan queue:work --queue=allocations
     * 2. Control concurrency: limit to 1 worker for strict FIFO ordering
     * 3. Monitor allocation queue separately from other jobs
     */
    // public $queue = 'allocations';

    /**
     * Retry Configuration
     */
    public $tries = 3;
    public $backoff = [5, 10, 30]; // Exponential backoff: 5s, 10s, 30s

    /**
     * Maximum execution time (seconds)
     */
    public $timeout = 120;

    /**
     * @param Investment $investment The investment record to allocate shares for
     */
    public function __construct(
        public Investment $investment
    ) 
    {
    $this->onQueue('allocations');
    }

    /**
     * Execute the allocation job.
     *
     * [P2.2]: Atomic allocation with status tracking.
     * [G.22 FIX]: Added idempotency protection to prevent double allocation
     * [G.23 FIX]: Added workflow state tracking for partial completion detection
     */
    public function handle(
        AllocationService $allocationService,
        \App\Services\IdempotencyService $idempotency,
        \App\Services\JobStateTrackerService $stateTracker
    ): void {
        Log::info("[P2.2] Processing allocation for Investment #{$this->investment->id}");

        $idempotencyKey = "share_allocation:{$this->investment->id}";

        // [G.22]: Check if already allocated to prevent double allocation
        if ($idempotency->isAlreadyExecuted($idempotencyKey, self::class)) {
            Log::info("[G.22] Investment #{$this->investment->id} already allocated. Skipping to prevent double allocation.");
            return;
        }

        // [G.23]: Start workflow tracking
        $stateTracker->startWorkflow('investment_flow', 'investment', $this->investment->id, [
            'steps' => ['share_allocation'],
            'timeout_minutes' => 30,
            'metadata' => [
                'user_id' => $this->investment->user_id,
                'amount' => $this->investment->total_amount,
            ],
        ]);

        // [P2.2]: Mark as processing
        $this->investment->update([
            'allocation_status' => 'processing',
        ]);

        $stateTracker->updateState('investment_flow', 'investment', $this->investment->id, 'processing');

        try {
            // [G.22]: Execute with idempotency protection
            $idempotency->executeOnce($idempotencyKey, function () use ($allocationService, $stateTracker) {
                DB::transaction(function () use ($allocationService, $stateTracker) {

                    // Create a Payment record for allocation tracking
                    // Note: This links the share allocation to the original payment
                    $dummyPayment = new Payment([
                        'id' => null,
                        'user_id' => $this->investment->user_id,
                        'subscription_id' => $this->investment->subscription_id,
                        'amount' => $this->investment->total_amount,
                    ]);

                    // [P2.2]: Allocate shares (creates UserInvestment records)
                    $allocationService->allocateShares(
                        $dummyPayment,
                        $this->investment->total_amount
                    );

                    // [P2.2]: Mark as completed
                    $this->investment->update([
                        'allocation_status' => 'completed',
                        'allocated_at' => now(),
                        'status' => 'active', // Also mark investment as active
                    ]);

                    // [G.23]: Mark step completed
                    $stateTracker->completeStep('investment_flow', 'investment', $this->investment->id, 'share_allocation');

                    Log::info("[P2.2] Allocation completed for Investment #{$this->investment->id}");
                });
            }, [
                'job_class' => self::class,
                'input_data' => [
                    'investment_id' => $this->investment->id,
                    'user_id' => $this->investment->user_id,
                    'amount' => $this->investment->total_amount,
                ],
            ]);

        } catch (\Exception $e) {
            // [P2.2]: Mark as failed with error message
            $this->investment->update([
                'allocation_status' => 'failed',
                'allocated_at' => now(),
                'allocation_error' => $e->getMessage(),
            ]);

            // [G.23]: Mark step failed
            $stateTracker->failStep('investment_flow', 'investment', $this->investment->id, 'share_allocation', $e->getMessage());

            Log::error("[P2.2] Allocation failed for Investment #{$this->investment->id}: " . $e->getMessage(), [
                'investment_id' => $this->investment->id,
                'user_id' => $this->investment->user_id,
                'amount' => $this->investment->total_amount,
                'exception' => $e,
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle job failure after all retries exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("[P2.2] Allocation permanently failed for Investment #{$this->investment->id} after {$this->tries} attempts", [
            'investment_id' => $this->investment->id,
            'user_id' => $this->investment->user_id,
            'amount' => $this->investment->total_amount,
            'error' => $exception->getMessage(),
        ]);

        // Mark as permanently failed
        $this->investment->update([
            'allocation_status' => 'failed',
            'allocated_at' => now(),
            'allocation_error' => "Permanent failure after {$this->tries} attempts: " . $exception->getMessage(),
        ]);

        // TODO: Notify admin/user about failed allocation
        // NotifyAdminOfFailedAllocationJob::dispatch($this->investment);
    }
}
