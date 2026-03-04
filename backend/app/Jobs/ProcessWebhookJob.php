<?php

namespace App\Jobs;

use App\Models\WebhookEventLedger;
use App\Models\WebhookLog;
use App\Services\Webhooks\EventRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use App\Models\ProcessedWebhookEvent;
use Illuminate\Support\Facades\DB;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected WebhookLog $webhookLog,
        protected int $ledgerId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EventRouter $eventRouter): void
    {
        $ledgerEntry = WebhookEventLedger::findOrFail($this->ledgerId);
        
        // 1. Institutional-Grade Anti-Double Execution Lock
        $lock = Cache::lock("webhook-event:{$ledgerEntry->provider}:{$ledgerEntry->event_id}", 30);

        try {
            if (!$lock->get()) {
                Log::warning("ProcessWebhookJob: Could not acquire lock for event {$ledgerEntry->event_id}. Possibly already processing.");
                return;
            }

            // 2. Check current status in ledger (Audit Protection)
            if ($ledgerEntry->processing_status === 'success') {
                Log::info("ProcessWebhookJob: Event {$ledgerEntry->event_id} already processed successfully. Skipping.");
                return;
            }

            // 3. Event Ordering Protection (Stripe Model)
            if ($ledgerEntry->resource_id && $ledgerEntry->event_timestamp) {
                $latestProcessedTimestamp = ProcessedWebhookEvent::where('resource_type', $ledgerEntry->resource_type)
                    ->where('resource_id', $ledgerEntry->resource_id)
                    ->max('event_timestamp');

                if ($latestProcessedTimestamp && $ledgerEntry->event_timestamp < $latestProcessedTimestamp) {
                    Log::warning("ORDERING PROTECTION: Out-of-order event ignored for {$ledgerEntry->resource_type} {$ledgerEntry->resource_id}. [Incoming Timestamp: {$ledgerEntry->event_timestamp}] < [Latest Processed: {$latestProcessedTimestamp}]. Provider: {$ledgerEntry->provider}, Event: {$ledgerEntry->event_id}. Skipping processing to prevent state regression.", [
                        'provider' => $ledgerEntry->provider,
                        'event_id' => $ledgerEntry->event_id,
                        'incoming_ts' => $ledgerEntry->event_timestamp,
                        'latest_ts' => $latestProcessedTimestamp
                    ]);
                    
                    $ledgerEntry->update([
                        'processing_status' => 'success', // Marking as success because we intentionally ignore it to maintain order
                        'processed_at' => now(),
                    ]);
                    $this->webhookLog->markAsSuccess(['message' => 'Ignored due to event ordering protection (Layer 2)'], 200);
                    return;
                }
            }

            // 4. Update status to processing
            $ledgerEntry->update(['processing_status' => 'processing']);
            $this->webhookLog->markAsProcessing();

            // 5. Resolve provider and process
            $provider = $ledgerEntry->provider;
            $event = $this->webhookLog->event_type;
            $data = $this->webhookLog->payload;

            // Metadata for handlers
            $metadata = [
                'provider' => $provider,
                'event_id' => $ledgerEntry->event_id,
                'event_type' => $event,
                'resource_id' => $ledgerEntry->resource_id,
                'resource_type' => $ledgerEntry->resource_type,
                'timestamp' => $ledgerEntry->event_timestamp,
                'payload_hash' => $ledgerEntry->payload_hash,
                'ledger_id' => $ledgerEntry->id,
            ];

            // Use the EventRouter for decoupled processing
            $eventRouter->dispatch($provider, $event, $data, $metadata);

            // 6. Success Tracking
            $ledgerEntry->update([
                'processing_status' => 'success',
                'processed_at' => now(),
            ]);
            $this->webhookLog->markAsSuccess(['message' => 'Institutional processing complete'], 200);

        } catch (\Exception $e) {
            Log::error("ProcessWebhookJob Error: {$e->getMessage()}", [
                'provider' => $ledgerEntry->provider,
                'event' => $ledgerEntry->event_id
            ]);

            $ledgerEntry->update(['processing_status' => 'failed']);
            $this->webhookLog->markAsFailed($e->getMessage(), 500);

            // Re-throw to trigger queue retry if needed, or handle based on business rules
            throw $e;
        } finally {
            $lock->release();
        }
    }
}
