<?php

namespace App\Console\Commands;

use App\Models\WebhookLog;
use App\Jobs\ProcessWebhookRetryJob;
use Illuminate\Console\Command;

class RetryFailedWebhooks extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'webhooks:retry
                            {--all : Retry all failed webhooks}
                            {--id= : Retry specific webhook by ID}
                            {--hours= : Retry webhooks failed in last X hours}';

    /**
     * The console command description.
     */
    protected $description = 'Retry failed webhook processing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = WebhookLog::query();

        if ($this->option('id')) {
            $query->where('id', $this->option('id'));
        } elseif ($this->option('hours')) {
            $hours = (int) $this->option('hours');
            $query->where('created_at', '>=', now()->subHours($hours))
                ->whereIn('status', ['failed', 'max_retries_reached']);
        } elseif ($this->option('all')) {
            $query->whereIn('status', ['failed', 'max_retries_reached']);
        } else {
            // Default: retry pending webhooks that are due
            $query->pendingRetries();
        }

        $webhooks = $query->get();

        if ($webhooks->isEmpty()) {
            $this->info('No webhooks found to retry.');
            return self::SUCCESS;
        }

        $this->info("Found {$webhooks->count()} webhook(s) to retry.");

        $progressBar = $this->output->createProgressBar($webhooks->count());
        $progressBar->start();

        $queued = 0;
        foreach ($webhooks as $webhook) {
            // Reset retry count if manually retrying failed webhooks
            if ($this->option('all') || $this->option('id') || $this->option('hours')) {
                $webhook->update([
                    'status' => 'pending',
                    'retry_count' => 0,
                    'next_retry_at' => now(),
                ]);
            }

            // Dispatch retry job
            ProcessWebhookRetryJob::dispatch($webhook);
            $queued++;

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);
        $this->info("Successfully queued {$queued} webhook(s) for retry.");

        return self::SUCCESS;
    }
}
