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
        
        // 1. Institutional-Grade Anti-Double Execution Lock (Distributed)
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

            // 3. Resource-Level Transaction & Ordering Protection (Layer 2)
            // Fix: Use Cache::lock on the resource itself to prevent "gap locking" problems and ensure serial processing for specific resources.
            $resourceLock = null;
            if ($ledgerEntry->resource_id && $ledgerEntry->resource_type) {
                $resourceLock = Cache::lock("webhook-resource:{$ledgerEntry->resource_type}:{$ledgerEntry->resource_id}", 30);
                if (!$resourceLock->get()) {
                    Log::warning("ProcessWebhookJob: Could not acquire lock for resource {$ledgerEntry->resource_type} {$ledgerEntry->resource_id}. Re-queueing.");
                    $this->release(5); // Release back to queue to retry later
                    return;
                }
            }

            try {
                DB::transaction(function () use ($ledgerEntry, $eventRouter) {
                    if ($ledgerEntry->resource_id && $ledgerEntry->resource_type) {
                        $latest = ProcessedWebhookEvent::where('resource_type', $ledgerEntry->resource_type)
                            ->where('resource_id', $ledgerEntry->resource_id)
                            ->orderBy('event_timestamp', 'desc')
                            ->lockForUpdate()
                            ->first();

                        if ($latest && $ledgerEntry->event_timestamp < $latest->event_timestamp) {
                            Log::warning("ORDERING PROTECTION: Out-of-order event ignored for {$ledgerEntry->resource_type} {$ledgerEntry->resource_id}. [Incoming Timestamp: {$ledgerEntry->event_timestamp}] < [Latest Processed: {$latest->event_timestamp}]. Provider: {$ledgerEntry->provider}, Event: {$ledgerEntry->event_id}. Skipping processing to prevent state regression.", [
                                'provider' => $ledgerEntry->provider,
                                'event_id' => $ledgerEntry->event_id,
                                'incoming_ts' => $ledgerEntry->event_timestamp,
                                'latest_ts' => $latest->event_timestamp
                            ]);
                            
                            $ledgerEntry->update([
                                'processing_status' => 'success',
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
                });
            } finally {
                if ($resourceLock) {
                    $resourceLock->release();
                }
            }

        } catch (\Exception $e) {
            Log::error("ProcessWebhookJob Error: {$e->getMessage()}", [
                'provider' => $ledgerEntry->provider,
                'event' => $ledgerEntry->event_id,
                'attempt' => $this->attempts()
            ]);

            // DLQ Handling (Layer 4 Reliability)
            if ($this->attempts() >= 5) {
                // Fix: Atomic insertion to prevent race conditions in DLQ.
                // Do not use json_encode here if the model casts 'payload' to array/json.
                // Even for insertOrIgnore, Laravel's Query Builder handles the array if the DB supports JSON.
                \App\Models\WebhookDeadLetter::insertOrIgnore([
                    'provider' => $ledgerEntry->provider,
                    'event_id' => $ledgerEntry->event_id,
                    'resource_type' => $ledgerEntry->resource_type,
                    'resource_id' => $ledgerEntry->resource_id,
                    'payload' => json_encode($this->webhookLog->payload), // Query builder needs string for direct insert
                    'error_message' => $e->getMessage(),
                    'attempts' => $this->attempts(),
                    'failed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $ledgerEntry->update(['processing_status' => 'dead_letter']);
                $this->webhookLog->markAsFailed($e->getMessage() . " (Moved to DLQ)", 500);
                
                // Do not re-throw if moved to DLQ (stops the retry cycle)
                return;
            }

            $ledgerEntry->update(['processing_status' => 'failed']);
            $this->webhookLog->markAsFailed($e->getMessage(), 500);

            // Re-throw to trigger queue retry if needed
            throw $e;
        } finally {
            $lock->release();
        }
    }
}
