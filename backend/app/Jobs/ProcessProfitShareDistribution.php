<?php
// V-AUDIT-FIX-MODULE11 (Async Distribution Job)

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

class ProcessProfitShareDistribution implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The timeout in seconds.
     * Allow 1 hour for large batches (50k+ users).
     *
     * @var int
     */
    public $timeout = 3600;

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
        Log::info("Starting async profit share distribution for Period #{$this->profitShare->id} initiated by Admin #{$this->admin->id}");

        try {
            // Call the service method which now includes Pessimistic Locking
            $service->distributeToWallets($this->profitShare, $this->admin);
            
            Log::info("Async distribution completed successfully for Period #{$this->profitShare->id}");
            
        } catch (\Exception $e) {
            Log::error("Async distribution failed for Period #{$this->profitShare->id}: " . $e->getMessage());
            
            // Mark job as failed
            $this->fail($e);
        }
    }
}