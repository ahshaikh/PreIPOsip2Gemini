<?php

namespace App\Console\Commands;

use App\Jobs\ProcessWebhookJob;
use App\Models\WebhookDeadLetter;
use App\Models\WebhookEventLedger;
use App\Models\WebhookLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WebhookReplay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:replay {event_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replay a webhook event from the Dead Letter Queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventId = $this->argument('event_id');

        // 1. Locate the event in webhook_dead_letters
        $dlqEntry = WebhookDeadLetter::where('event_id', $eventId)->latest()->first();

        if (!$dlqEntry) {
            $this->error("Event {$eventId} not found in Dead Letter Queue.");
            return 1;
        }

        // 2. Locate ledger entry
        $ledgerEntry = WebhookEventLedger::where('event_id', $eventId)
            ->where('provider', $dlqEntry->provider)
            ->first();

        if (!$ledgerEntry) {
            $this->error("Ledger entry not found for event {$eventId}. Cannot safely replay without original metadata.");
            return 1;
        }

        // 3. Locate WebhookLog - Problem 1 Fix: Lookup by reliable columns
        $webhookLog = WebhookLog::where('webhook_id', $eventId)
            ->where('provider', $dlqEntry->provider)
            ->first();

        if (!$webhookLog) {
            $this->error("Original WebhookLog not found for event {$eventId}. (Provider: {$dlqEntry->provider})");
            return 1;
        }

        $this->info("Replaying event {$eventId} (Provider: {$dlqEntry->provider})...");

        // 4. Update ledger status: DEAD_LETTER → ENQUEUED
        $ledgerEntry->update(['processing_status' => 'ENQUEUED']);

        // 5. Reset retry count if we want a fresh cycle, or just let it process
        $webhookLog->update(['status' => 'pending', 'retry_count' => 0]);

        // 6. Resolve isolation queue - Problem 3 Fix: Using config
        $queues = config('webhooks.queues');
        $queue = $queues[$ledgerEntry->resource_type] ?? $queues['default'];

        // 7. Re-dispatch job
        ProcessWebhookJob::dispatch($webhookLog, $ledgerEntry->id)->onQueue($queue);

        $this->info("Event {$eventId} re-enqueued to {$queue} for processing.");
        Log::info("DLQ REPLAY: Webhook {$eventId} manually replayed from DLQ.", [
            'event_id' => $eventId,
            'provider' => $dlqEntry->provider,
            'original_error' => $dlqEntry->error_message,
        ]);

        return 0;
    }
}
