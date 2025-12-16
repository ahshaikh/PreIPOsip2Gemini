<?php
// V-AUDIT-MODULE10-001 (HIGH): Created async calculation job to prevent HTTP timeouts

namespace App\Jobs;

use App\Models\ProfitShare;
use App\Models\User;
use App\Services\ProfitShareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * V-AUDIT-MODULE10-001 (HIGH): Background job for profit share calculation.
 *
 * Scalability Fix:
 * - Previous: calculateDistribution was called synchronously in HTTP request
 * - Problem: For 50,000+ investors, even with cursor optimization, calculation
 *   can take 30-60+ seconds, exceeding load balancer timeouts (Nginx/AWS ALB)
 * - Solution: Dispatch calculation as a background job
 *
 * Benefits:
 * - Admin gets immediate response (job queued successfully)
 * - Calculation happens in background without HTTP timeout risk
 * - Can process millions of subscriptions without blocking web server
 * - Failed calculations can be retried automatically
 * - Progress can be tracked via queue monitoring
 */
class CalculateProfitShareJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The timeout in seconds.
     * Allow 2 hours for extremely large datasets (100k+ users).
     *
     * @var int
     */
    public $timeout = 7200;

    /**
     * Maximum retry attempts
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ProfitShare $profitShare,
        public User $admin
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ProfitShareService $service): void
    {
        Log::info("Starting async profit share calculation for Period #{$this->profitShare->id} initiated by Admin #{$this->admin->id}");

        try {
            // Call the service method which uses cursor() for memory efficiency
            $result = $service->calculateDistribution($this->profitShare, false);

            Log::info("Async calculation completed successfully for Period #{$this->profitShare->id}", [
                'eligible_users' => $result['eligible_users'],
                'total_distributed' => $result['total_distributed']
            ]);

            // TODO: Optionally send notification to admin when complete
            // $this->admin->notify(new ProfitShareCalculationComplete($this->profitShare));

        } catch (\Exception $e) {
            Log::error("Async calculation failed for Period #{$this->profitShare->id}: " . $e->getMessage(), [
                'admin_id' => $this->admin->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mark job as failed (will be retried based on $tries)
            $this->fail($e);
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
        Log::critical("CalculateProfitShareJob failed permanently for Period #{$this->profitShare->id}", [
            'profit_share_id' => $this->profitShare->id,
            'admin_id' => $this->admin->id,
            'error' => $exception->getMessage()
        ]);

        // TODO: Send alert to admin about failed calculation
        // $this->admin->notify(new ProfitShareCalculationFailed($this->profitShare, $exception));
    }
}
