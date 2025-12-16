<?php
// V-AUDIT-MODULE7-004 (Created): Parallel processing for auto-debit charges

namespace App\Jobs;

use App\Models\Subscription;
use App\Services\AutoDebitService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * V-AUDIT-MODULE7-004 (HIGH): Process a single subscription charge in parallel.
 *
 * Scalability Fix:
 * - ProcessAutoDebits command dispatches this job for EACH due subscription
 * - Enables parallel processing via queue workers instead of serial blocking
 * - If one payment gateway call hangs, it doesn't block the entire batch
 *
 * Benefits:
 * - Faster processing: 1000 subscriptions can be processed concurrently
 * - Fault isolation: One failing payment doesn't crash the entire cron job
 * - Better resource utilization: Multiple queue workers share the load
 */
class ProcessSubscriptionChargeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum execution time: 2 minutes per charge attempt.
     * Prevents infinite hangs on gateway timeouts.
     */
    public $timeout = 120;

    /**
     * Maximum retry attempts for failed job execution.
     * Note: This is different from payment retry logic (handled by AutoDebitService).
     */
    public $tries = 3;

    public function __construct(public Subscription $subscription)
    {
    }

    /**
     * Process the subscription charge by delegating to AutoDebitService.
     *
     * @param AutoDebitService $service
     * @return void
     */
    public function handle(AutoDebitService $service): void
    {
        $sub = $this->subscription;

        Log::info("Processing charge for Subscription #{$sub->id} (User: {$sub->user_id})");

        try {
            // Attempt to charge the subscription
            $success = $service->attemptAutoDebit($sub);

            if ($success) {
                Log::info("Charge successful for Subscription #{$sub->id}");
            } else {
                Log::warning("Charge failed for Subscription #{$sub->id}. Retry scheduled by AutoDebitService.");
            }

        } catch (\Exception $e) {
            // Log exception and let Laravel's queue failure handling take over
            Log::error("Exception while processing Subscription #{$sub->id}: " . $e->getMessage(), [
                'subscription_id' => $sub->id,
                'user_id' => $sub->user_id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to mark job as failed (will be retried based on $tries)
            throw $e;
        }
    }

    /**
     * Handle job failure after max retries.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("ProcessSubscriptionChargeJob failed permanently for Subscription #{$this->subscription->id}", [
            'subscription_id' => $this->subscription->id,
            'user_id' => $this->subscription->user_id,
            'error' => $exception->getMessage()
        ]);

        // Optionally: Send alert to admin, mark subscription for manual review, etc.
    }
}
