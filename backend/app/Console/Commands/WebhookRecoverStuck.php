<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWebhookJob;
use App\Models\WebhookEventLedger;
use App\Models\WebhookLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WebhookRecoverStuck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:recover-stuck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recover webhook events stuck in PROCESSING state for more than 10 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stuckTime = now()->subMinutes(10);

        $stuckEvents = WebhookEventLedger::where('processing_status', 'PROCESSING')
            ->where('updated_at', '<', $stuckTime)
            ->get();

        if ($stuckEvents->isEmpty()) {
            $this->info('No stuck webhook events found.');
            return 0;
        }

        $this->info("Found {$stuckEvents->count()} stuck events. Recovering...");

        $queues = config('webhooks.queues');

        foreach ($stuckEvents as $event) {
            // Problem 2 Fix: Atomic state transition to prevent duplicate dispatch
            $affectedRows = WebhookEventLedger::where('id', $event->id)
                ->where('processing_status', 'PROCESSING') // Ensure it hasn't changed
                ->update(['processing_status' => 'ENQUEUED', 'updated_at' => now()]);

            if ($affectedRows === 0) {
                // Another worker or recovery run already picked it up
                continue;
            }

            $this->warn("Recovering event {$event->event_id} (Provider: {$event->provider})");

            // Problem 1 Fix: Lookup by reliable columns instead of JSON headers
            $webhookLog = WebhookLog::where('webhook_id', $event->event_id)
                ->where('provider', $event->provider)
                ->first();

            if (!$webhookLog) {
                $this->error("Could not find WebhookLog for event {$event->event_id}. Skipping.");
                // Rollback status to PROCESSING or mark as error? 
                // Let's leave as ENQUEUED for now, the Job will fail/handle it.
                continue;
            }

            // Resolve isolation queue from config
            $queue = $queues[$event->resource_type] ?? $queues['default'];

            // Re-dispatch job
            ProcessWebhookJob::dispatch($webhookLog, $event->id)->onQueue($queue);

            Log::info("RECOVERY EVENT: Webhook {$event->event_id} recovered from stuck PROCESSING state and re-enqueued.", [
                'event_id' => $event->event_id,
                'provider' => $event->provider,
                'stuck_since' => $event->updated_at,
            ]);
        }

        $this->info('Recovery complete.');
        return 0;
    }
}
