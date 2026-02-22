<?php
// V-FINAL-1730-341 (Created) | V-FINAL-1730-480 (Custom Amount) | V-AUDIT-MODULE7-004 (Parallel Processing)

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AutoDebitService;
use App\Jobs\ProcessSubscriptionChargeJob; // V-AUDIT-MODULE7-004: Dispatch jobs for parallel processing

class ProcessAutoDebits extends Command
{
    protected $signature = 'app:process-auto-debits';
    protected $description = 'Process automated SIP payments and retries.';

    /**
     * V-AUDIT-MODULE7-004 (HIGH): Process auto-debits with parallel job dispatching.
     *
     * Scalability Fix:
     * - Previous: Processed subscriptions serially in a blocking loop
     * - Problem: If one gateway call hangs for 30s, entire batch is delayed
     * - Solution: Dispatch a job for EACH subscription, enabling parallel processing
     *
     * Benefits:
     * - 1000 subscriptions can be processed concurrently by multiple queue workers
     * - Command completes quickly, actual processing happens in background
     * - Failed charges don't block other subscriptions
     */
    public function handle(AutoDebitService $service)
    {
        $this->info('Starting auto-debit process...');

        // 1. Process New Debits - V-AUDIT-MODULE7-004: Dispatch jobs for parallel processing
        $dueSubs = $service->getDueSubscriptions();
        $count = $dueSubs->count();
        $this->info("Found {$count} subscriptions due for payment.");

        // V-AUDIT-MODULE7-004: Dispatch a job for EACH subscription instead of processing inline
        // Queue workers will process these in parallel, improving throughput significantly
        if ($count > 0) {
            foreach ($dueSubs as $sub) {
                ProcessSubscriptionChargeJob::dispatch($sub);
            }
            $this->info("Dispatched {$count} subscription charge jobs to queue.");
        }

        // 2. Send Reminders - V-HARDENING-PHASE: Always send reminders regardless of due count.
        // Reminders are for subscriptions due in 3 days (independent of currently-due subs).
        // A user may have 0 due today but 5 due in 3 days - reminders must still be sent.
        $reminders = $service->sendReminders();
        $this->info("Sent {$reminders} payment reminders.");

        $this->info('Auto-debit process completed.');
        return 0;
    }
}