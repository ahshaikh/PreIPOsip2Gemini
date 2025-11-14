<?php
// V-FINAL-1730-341 (Service Integrated)

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AutoDebitService;

class ProcessAutoDebits extends Command
{
    protected $signature = 'app:process-auto-debits';
    protected $description = 'Process automated SIP payments and retries.';

    public function handle(AutoDebitService $service)
    {
        $this->info('Starting auto-debit process...');

        // 1. Process New Debits
        $dueSubs = $service->getDueSubscriptions();
        $this->info("Found {$dueSubs->count()} subscriptions due.");

        foreach ($dueSubs as $sub) {
            $service->attemptAutoDebit($sub);
        }

        // 2. Send Reminders
        $reminders = $service->sendReminders();
        $this->info("Sent {$reminders} payment reminders.");
        
        return 0;
    }
}