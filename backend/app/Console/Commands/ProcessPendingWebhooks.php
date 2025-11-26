<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use App\Jobs\ProcessWebhookRetryJob;
use Illuminate\Console\Command;

class ProcessPendingWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'webhooks:process-pending';

    /**
     * The console command description.
     */
    protected $description = 'Process pending webhook retries that are due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $webhooks = WebhookLog::pendingRetries()->get();

        if ($webhooks->isEmpty()) {
            $this->info('No pending webhooks to process.');
            return self::SUCCESS;
        }

        $this->info("Processing {$webhooks->count()} pending webhook(s)...");

        $processed = 0;
        foreach ($webhooks as $webhook) {
            ProcessWebhookRetryJob::dispatch($webhook);
            $processed++;
        }

        $this->info("Successfully queued {$processed} webhook(s) for processing.");

        return self::SUCCESS;
    }
}
